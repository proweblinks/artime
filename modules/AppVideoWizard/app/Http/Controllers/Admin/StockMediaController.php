<?php

namespace Modules\AppVideoWizard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\AppVideoWizard\Models\StockMedia;

class StockMediaController extends Controller
{
    protected array $supportedMimes = [
        'image/jpeg', 'image/png', 'image/webp',
        'video/mp4', 'video/quicktime', 'video/webm',
    ];

    protected array $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    protected array $videoExtensions = ['mp4', 'mov', 'webm'];

    /**
     * Dashboard with stats overview.
     */
    public function dashboard()
    {
        $totalItems = StockMedia::count();
        $totalImages = StockMedia::where('type', 'image')->count();
        $totalVideos = StockMedia::where('type', 'video')->count();
        $categories = StockMedia::select('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $categoryStats = StockMedia::select(
                'category',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END) as images"),
                DB::raw("SUM(CASE WHEN type = 'video' THEN 1 ELSE 0 END) as videos"),
                DB::raw('SUM(file_size) as storage_size')
            )
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        $recentItems = StockMedia::orderByDesc('created_at')->limit(10)->get();

        $activeCount = StockMedia::where('is_active', true)->count();
        $inactiveCount = $totalItems - $activeCount;

        return view('appvideowizard::admin.stock.dashboard', compact(
            'totalItems', 'totalImages', 'totalVideos', 'categories',
            'categoryStats', 'recentItems', 'activeCount', 'inactiveCount'
        ));
    }

    /**
     * Browse/manage media with filtering and pagination.
     */
    public function index(Request $request)
    {
        $viewMode = $request->get('view', 'grid');
        $perPage = $viewMode === 'grid' ? 24 : 50;

        $query = StockMedia::query();

        if ($search = $request->get('search')) {
            $query->search($search);
        }
        if ($category = $request->get('category')) {
            $query->inCategory($category);
        }
        if ($type = $request->get('type')) {
            $query->ofType($type);
        }
        if ($orientation = $request->get('orientation')) {
            $query->orientation($orientation);
        }
        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        // Default sort: newest first (unless search applied, which sorts by relevance)
        if (!$search) {
            $query->orderByDesc('created_at');
        }

        $items = $query->paginate($perPage)->appends($request->query());

        $categories = StockMedia::select('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return view('appvideowizard::admin.stock.index', compact(
            'items', 'categories', 'viewMode'
        ));
    }

    /**
     * Edit form for a single media item.
     */
    public function edit(StockMedia $stockMedia)
    {
        $categories = StockMedia::select('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return view('appvideowizard::admin.stock.edit', compact('stockMedia', 'categories'));
    }

    /**
     * Update a media item.
     */
    public function update(Request $request, StockMedia $stockMedia)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string|max:500',
            'category' => 'required|string|max:100',
            'orientation' => 'required|in:landscape,portrait,square',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $stockMedia->update($validated);

        session()->flash('success', 'Media item updated successfully.');
        return redirect()->route('admin.stock-media.edit', $stockMedia);
    }

    /**
     * Delete a media item.
     */
    public function destroy(StockMedia $stockMedia)
    {
        $filePath = public_path('stock-media/' . $stockMedia->path);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Delete thumbnail if exists
        if ($stockMedia->thumbnail_path) {
            $thumbPath = public_path('stock-media/' . $stockMedia->thumbnail_path);
            if (file_exists($thumbPath)) {
                @unlink($thumbPath);
            }
        }

        $stockMedia->delete();

        session()->flash('success', 'Media item deleted successfully.');
        return redirect()->route('admin.stock-media.browse');
    }

    /**
     * Toggle active status.
     */
    public function toggle(StockMedia $stockMedia)
    {
        $stockMedia->update(['is_active' => !$stockMedia->is_active]);

        $status = $stockMedia->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Media item {$status}.");

        return back();
    }

    /**
     * Upload form.
     */
    public function uploadForm()
    {
        $categories = StockMedia::select('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return view('appvideowizard::admin.stock.upload', compact('categories'));
    }

    /**
     * Handle file uploads.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:204800', // 200MB max
        ]);

        $category = $request->input('category');
        $newCategory = $request->input('new_category');
        if ($newCategory) {
            $category = str_replace(' ', '-', strtolower(trim($newCategory)));
        }

        $stockRoot = public_path('stock-media');
        $targetDir = $stockRoot . '/' . $category;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $indexed = 0;
        $errors = [];

        foreach ($request->file('files') as $file) {
            try {
                $mime = $file->getMimeType();
                if (!in_array($mime, $this->supportedMimes)) {
                    $errors[] = $file->getClientOriginalName() . ': Unsupported file type (' . $mime . ')';
                    continue;
                }

                $filename = $this->sanitizeFilename($file->getClientOriginalName());
                $file->move($targetDir, $filename);

                $fullPath = $targetDir . '/' . $filename;
                $relativePath = $category . '/' . $filename;
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                // Checksum duplicate check
                $checksum = hash_file('sha256', $fullPath);
                $existing = StockMedia::where('checksum', $checksum)->first();
                if ($existing) {
                    @unlink($fullPath);
                    $errors[] = $file->getClientOriginalName() . ': Duplicate file (already exists as ' . $existing->filename . ')';
                    continue;
                }

                $isVideo = in_array($ext, $this->videoExtensions);
                $type = $isVideo ? 'video' : 'image';

                // Extract metadata
                $metadata = $isVideo
                    ? $this->extractVideoMetadata($fullPath)
                    : $this->extractImageMetadata($fullPath);

                $width = $metadata['width'] ?? 0;
                $height = $metadata['height'] ?? 0;
                $orientation = 'landscape';
                if ($width > 0 && $height > 0) {
                    $ratio = $width / $height;
                    if ($ratio < 0.9) {
                        $orientation = 'portrait';
                    } elseif ($ratio <= 1.1) {
                        $orientation = 'square';
                    }
                }

                // Generate thumbnail for videos
                $thumbnailPath = null;
                if ($isVideo) {
                    $thumbnailPath = $this->generateVideoThumbnail($fullPath, $relativePath, $stockRoot);
                }

                // Derive title and tags
                $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
                $title = ucwords(str_replace(['-', '_'], ' ', $nameWithoutExt));
                $tags = strtolower($category) . ',' . strtolower(str_replace(['-', '_'], ',', $nameWithoutExt)) . ',stock';

                $mimeMap = [
                    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png', 'webp' => 'image/webp',
                    'mp4' => 'video/mp4', 'mov' => 'video/quicktime',
                    'webm' => 'video/webm',
                ];

                StockMedia::create([
                    'filename' => $filename,
                    'path' => $relativePath,
                    'disk_path' => $fullPath,
                    'checksum' => $checksum,
                    'type' => $type,
                    'mime_type' => $mimeMap[$ext] ?? $mime,
                    'file_size' => filesize($fullPath),
                    'width' => $width,
                    'height' => $height,
                    'duration' => $metadata['duration'] ?? null,
                    'fps' => $metadata['fps'] ?? null,
                    'category' => $category,
                    'title' => $title,
                    'tags' => $tags,
                    'thumbnail_path' => $thumbnailPath,
                    'orientation' => $orientation,
                    'is_active' => true,
                ]);

                $indexed++;
            } catch (\Exception $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        $message = "{$indexed} file(s) uploaded successfully.";
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' error(s): ' . implode('; ', $errors);
            session()->flash('warning', $message);
        } else {
            session()->flash('success', $message);
        }

        return redirect()->route('admin.stock-media.browse');
    }

    /**
     * Categories management page.
     */
    public function categories()
    {
        $categoryStats = StockMedia::select(
                'category',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END) as images"),
                DB::raw("SUM(CASE WHEN type = 'video' THEN 1 ELSE 0 END) as videos"),
                DB::raw('SUM(file_size) as storage_size')
            )
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        // Also check for empty folders on disk that aren't in DB
        $stockRoot = public_path('stock-media');
        $diskCategories = [];
        if (is_dir($stockRoot)) {
            foreach (scandir($stockRoot) as $dir) {
                if ($dir === '.' || $dir === '..' || $dir === '.thumbs' || !is_dir($stockRoot . '/' . $dir)) {
                    continue;
                }
                $diskCategories[] = $dir;
            }
        }

        return view('appvideowizard::admin.stock.categories', compact('categoryStats', 'diskCategories'));
    }

    /**
     * Rename a category.
     */
    public function updateCategory(Request $request)
    {
        $request->validate([
            'old_name' => 'required|string',
            'new_name' => 'required|string|max:100',
        ]);

        $oldName = $request->input('old_name');
        $newName = str_replace(' ', '-', strtolower(trim($request->input('new_name'))));

        if ($oldName === $newName) {
            return back();
        }

        // Rename folder
        $stockRoot = public_path('stock-media');
        $oldDir = $stockRoot . '/' . $oldName;
        $newDir = $stockRoot . '/' . $newName;

        if (is_dir($oldDir) && !is_dir($newDir)) {
            rename($oldDir, $newDir);
        }

        // Update all DB records
        StockMedia::where('category', $oldName)->update([
            'category' => $newName,
        ]);

        // Update paths
        StockMedia::where('category', $newName)->each(function ($media) use ($oldName, $newName) {
            $media->update([
                'path' => str_replace($oldName . '/', $newName . '/', $media->path),
                'disk_path' => str_replace('/' . $oldName . '/', '/' . $newName . '/', $media->disk_path),
            ]);
        });

        session()->flash('success', "Category renamed from '{$oldName}' to '{$newName}'.");
        return redirect()->route('admin.stock-media.categories');
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(Request $request, string $category)
    {
        $reassignTo = $request->input('reassign_to');

        if ($reassignTo) {
            // Reassign items to another category
            StockMedia::where('category', $category)->update(['category' => $reassignTo]);

            // Move files on disk
            $stockRoot = public_path('stock-media');
            $sourceDir = $stockRoot . '/' . $category;
            $targetDir = $stockRoot . '/' . $reassignTo;

            if (is_dir($sourceDir) && is_dir($targetDir)) {
                foreach (scandir($sourceDir) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    @rename($sourceDir . '/' . $file, $targetDir . '/' . $file);
                }
                @rmdir($sourceDir);
            }

            // Update paths
            StockMedia::where('category', $reassignTo)
                ->where('path', 'LIKE', $category . '/%')
                ->each(function ($media) use ($category, $reassignTo) {
                    $media->update([
                        'path' => str_replace($category . '/', $reassignTo . '/', $media->path),
                        'disk_path' => str_replace('/' . $category . '/', '/' . $reassignTo . '/', $media->disk_path),
                    ]);
                });

            session()->flash('success', "Category '{$category}' deleted. Items reassigned to '{$reassignTo}'.");
        } else {
            // Delete all items in category
            $items = StockMedia::where('category', $category)->get();
            foreach ($items as $item) {
                $filePath = public_path('stock-media/' . $item->path);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                if ($item->thumbnail_path) {
                    $thumbPath = public_path('stock-media/' . $item->thumbnail_path);
                    if (file_exists($thumbPath)) {
                        @unlink($thumbPath);
                    }
                }
                $item->delete();
            }

            // Remove empty directory
            $stockRoot = public_path('stock-media');
            $dir = $stockRoot . '/' . $category;
            if (is_dir($dir)) {
                @rmdir($dir);
            }

            session()->flash('success', "Category '{$category}' and all its items deleted.");
        }

        return redirect()->route('admin.stock-media.categories');
    }

    /**
     * Settings page.
     */
    public function settings()
    {
        $stockRoot = public_path('stock-media');
        $totalSize = StockMedia::sum('file_size');
        $totalItems = StockMedia::count();
        $pexelsKey = get_option('pexels_api_key', '');
        $hasPexels = !empty($pexelsKey);

        return view('appvideowizard::admin.stock.settings', compact(
            'stockRoot', 'totalSize', 'totalItems', 'hasPexels'
        ));
    }

    /**
     * Save settings.
     */
    public function saveSettings(Request $request)
    {
        // Currently settings are stored in options table
        if ($request->has('pexels_api_key')) {
            update_option('pexels_api_key', $request->input('pexels_api_key'));
        }

        session()->flash('success', 'Settings saved successfully.');
        return redirect()->route('admin.stock-media.settings');
    }

    /**
     * Trigger artisan reindex.
     */
    public function reindex(Request $request)
    {
        $options = [];
        if ($request->boolean('force')) {
            $options['--force'] = true;
        }
        if ($request->boolean('clean')) {
            $options['--clean'] = true;
        }

        $exitCode = Artisan::call('wizard:index-stock', $options);
        $output = Artisan::output();

        session()->flash('success', 'Reindex completed.');
        session()->flash('reindex_output', $output);

        return redirect()->route('admin.stock-media.settings');
    }

    /**
     * Bulk actions: delete, activate, deactivate, move category.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,move',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:wizard_stock_media,id',
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');
        $count = count($ids);

        switch ($action) {
            case 'delete':
                $items = StockMedia::whereIn('id', $ids)->get();
                foreach ($items as $item) {
                    $filePath = public_path('stock-media/' . $item->path);
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    if ($item->thumbnail_path) {
                        $thumbPath = public_path('stock-media/' . $item->thumbnail_path);
                        if (file_exists($thumbPath)) {
                            @unlink($thumbPath);
                        }
                    }
                    $item->delete();
                }
                session()->flash('success', "{$count} item(s) deleted.");
                break;

            case 'activate':
                StockMedia::whereIn('id', $ids)->update(['is_active' => true]);
                session()->flash('success', "{$count} item(s) activated.");
                break;

            case 'deactivate':
                StockMedia::whereIn('id', $ids)->update(['is_active' => false]);
                session()->flash('success', "{$count} item(s) deactivated.");
                break;

            case 'move':
                $request->validate(['target_category' => 'required|string|max:100']);
                $targetCategory = $request->input('target_category');
                StockMedia::whereIn('id', $ids)->update(['category' => $targetCategory]);
                session()->flash('success', "{$count} item(s) moved to '{$targetCategory}'.");
                break;
        }

        return back();
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    protected function sanitizeFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/-+/', '-', trim($name, '-'));

        // Ensure uniqueness
        $finalName = $name . '.' . $ext;
        return $finalName;
    }

    protected function extractImageMetadata(string $path): array
    {
        $info = @getimagesize($path);
        if (!$info) {
            return ['width' => 0, 'height' => 0];
        }
        return ['width' => $info[0], 'height' => $info[1]];
    }

    protected function extractVideoMetadata(string $path): array
    {
        $result = ['width' => 0, 'height' => 0, 'duration' => null, 'fps' => null];

        $ffprobe = $this->findBinary('ffprobe');
        if (!$ffprobe) {
            return $result;
        }

        $cmd = $ffprobe
            . ' -v quiet -print_format json -show_streams -show_format '
            . escapeshellarg($path) . ' 2>/dev/null';

        $output = shell_exec($cmd);
        if (!$output) {
            return $result;
        }

        $data = json_decode($output, true);
        if (!$data) {
            return $result;
        }

        $videoStream = null;
        foreach ($data['streams'] ?? [] as $stream) {
            if (($stream['codec_type'] ?? '') === 'video') {
                $videoStream = $stream;
                break;
            }
        }

        if ($videoStream) {
            $result['width'] = (int) ($videoStream['width'] ?? 0);
            $result['height'] = (int) ($videoStream['height'] ?? 0);

            $frameRate = $videoStream['r_frame_rate'] ?? '';
            if (str_contains($frameRate, '/')) {
                [$num, $den] = explode('/', $frameRate);
                if ((int) $den > 0) {
                    $result['fps'] = round((int) $num / (int) $den, 2);
                }
            }
        }

        $result['duration'] = isset($data['format']['duration'])
            ? round((float) $data['format']['duration'], 2)
            : null;

        return $result;
    }

    protected function generateVideoThumbnail(string $fullPath, string $relativePath, string $stockRoot): ?string
    {
        $ffmpeg = $this->findBinary('ffmpeg');
        if (!$ffmpeg) {
            return null;
        }

        $thumbDir = $stockRoot . '/.thumbs';
        if (!is_dir($thumbDir)) {
            @mkdir($thumbDir, 0755, true);
        }

        $thumbName = str_replace(['/', '\\'], '-', pathinfo($relativePath, PATHINFO_FILENAME)) . '.jpg';
        $thumbPath = $thumbDir . '/' . $thumbName;

        $cmd = $ffmpeg
            . ' -i ' . escapeshellarg($fullPath)
            . ' -ss 1 -vframes 1 -q:v 3 -y '
            . escapeshellarg($thumbPath) . ' 2>/dev/null';

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($thumbPath)) {
            return '.thumbs/' . $thumbName;
        }

        return null;
    }

    protected function findBinary(string $name): ?string
    {
        $paths = [
            '/home/artime/bin/' . $name,
            '/usr/local/bin/' . $name,
            '/usr/bin/' . $name,
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        $which = trim(shell_exec("which {$name} 2>/dev/null") ?? '');
        return !empty($which) ? $which : null;
    }
}
