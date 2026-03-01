<?php

namespace Modules\AppVideoWizard\Console\Commands;

use Illuminate\Console\Command;
use Modules\AppVideoWizard\Models\StockMedia;

class IndexStockMedia extends Command
{
    protected $signature = 'wizard:index-stock
                            {--path= : Override stock media root directory}
                            {--category= : Only index a specific category subfolder}
                            {--force : Re-index files even if checksum already exists}
                            {--dry-run : Preview what would be indexed without writing to DB}
                            {--clean : Remove DB entries for files that no longer exist on disk}';

    protected $description = 'Scan and index local stock media files into the wizard_stock_media table';

    protected array $supportedExtensions = [
        'jpg', 'jpeg', 'png', 'webp',
        'mp4', 'mov', 'webm',
    ];

    protected array $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    protected array $videoExtensions = ['mp4', 'mov', 'webm'];

    protected string $stockRoot;
    protected string $ffprobePath;
    protected string $ffmpegPath;

    public function handle(): int
    {
        $this->stockRoot = $this->option('path')
            ?: public_path('stock-media');

        if (!is_dir($this->stockRoot)) {
            $this->error("Stock media directory not found: {$this->stockRoot}");
            $this->info("Create it with: mkdir -p " . $this->stockRoot);
            return 1;
        }

        $this->ffprobePath = $this->findBinary('ffprobe');
        $this->ffmpegPath = $this->findBinary('ffmpeg');

        // Handle --clean mode
        if ($this->option('clean')) {
            return $this->cleanOrphaned();
        }

        $categoryFilter = $this->option('category');
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $this->info('Scanning stock media directory: ' . $this->stockRoot);
        if ($isDryRun) {
            $this->warn('DRY RUN — no changes will be made');
        }

        $files = $this->scanFiles($categoryFilter);
        $total = count($files);

        if ($total === 0) {
            $this->warn('No supported media files found.');
            return 0;
        }

        $this->info("Found {$total} media files");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $indexed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($files as $fileIndex => $file) {
            try {
                $result = $this->processFile($file, $isForce, $isDryRun, $fileIndex);
                if ($result === 'indexed') {
                    $indexed++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing {$file['relative']}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Summary:");
        $this->line("  Indexed: {$indexed}");
        $this->line("  Skipped (already indexed): {$skipped}");
        if ($errors > 0) {
            $this->warn("  Errors: {$errors}");
        }

        return 0;
    }

    /**
     * Scan the stock-media directory for supported files.
     */
    protected function scanFiles(?string $categoryFilter): array
    {
        $files = [];
        $scanDir = $categoryFilter
            ? $this->stockRoot . DIRECTORY_SEPARATOR . $categoryFilter
            : $this->stockRoot;

        if (!is_dir($scanDir)) {
            $this->error("Category directory not found: {$scanDir}");
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($scanDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $ext = strtolower($fileInfo->getExtension());
            if (!in_array($ext, $this->supportedExtensions)) {
                continue;
            }

            // Skip .thumbs directory
            $fullPath = $fileInfo->getRealPath();
            if (str_contains(str_replace('\\', '/', $fullPath), '/.thumbs/')) {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($fullPath, strlen($this->stockRoot) + 1));

            // Derive category from first-level subfolder
            $pathParts = explode('/', $relativePath);
            $category = count($pathParts) > 1 ? $pathParts[0] : 'uncategorized';

            $files[] = [
                'absolute' => $fullPath,
                'relative' => $relativePath,
                'filename' => $fileInfo->getFilename(),
                'extension' => $ext,
                'category' => $category,
                'size' => $fileInfo->getSize(),
            ];
        }

        return $files;
    }

    /**
     * Process a single file: compute checksum, extract metadata, insert/update DB.
     */
    protected function processFile(array $file, bool $force, bool $dryRun, int $index = 0): string
    {
        $checksum = hash_file('sha256', $file['absolute']);

        // Check if already indexed
        $existing = StockMedia::where('checksum', $checksum)->first();
        if ($existing && !$force) {
            return 'skipped';
        }

        $isVideo = in_array($file['extension'], $this->videoExtensions);
        $type = $isVideo ? 'video' : 'image';

        // Extract metadata
        $metadata = $isVideo
            ? $this->extractVideoMetadata($file['absolute'])
            : $this->extractImageMetadata($file['absolute']);

        // Determine orientation
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

        // Derive title and tags from filename
        $nameWithoutExt = pathinfo($file['filename'], PATHINFO_FILENAME);
        $title = $this->filenameToTitle($nameWithoutExt, $file['category'], $index);
        $tags = $this->filenameToTags($nameWithoutExt, $file['category']);

        // Determine MIME type
        $mimeMap = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'webp' => 'image/webp',
            'mp4' => 'video/mp4', 'mov' => 'video/quicktime',
            'webm' => 'video/webm',
        ];
        $mimeType = $mimeMap[$file['extension']] ?? 'application/octet-stream';

        // Generate video thumbnail
        $thumbnailPath = null;
        if ($isVideo && !$dryRun) {
            $thumbnailPath = $this->generateVideoThumbnail($file);
        }

        if ($dryRun) {
            return 'indexed';
        }

        // Upsert based on checksum
        $data = [
            'filename' => $file['filename'],
            'path' => $file['relative'],
            'disk_path' => $file['absolute'],
            'type' => $type,
            'mime_type' => $mimeType,
            'file_size' => $file['size'],
            'width' => $width,
            'height' => $height,
            'duration' => $metadata['duration'] ?? null,
            'fps' => $metadata['fps'] ?? null,
            'category' => $file['category'],
            'title' => $title,
            'tags' => $tags,
            'thumbnail_path' => $thumbnailPath,
            'orientation' => $orientation,
            'is_active' => true,
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            StockMedia::create(array_merge($data, ['checksum' => $checksum]));
        }

        return 'indexed';
    }

    /**
     * Extract image metadata using getimagesize().
     */
    protected function extractImageMetadata(string $path): array
    {
        $info = @getimagesize($path);
        if (!$info) {
            return ['width' => 0, 'height' => 0];
        }

        return [
            'width' => $info[0],
            'height' => $info[1],
        ];
    }

    /**
     * Extract video metadata using ffprobe.
     */
    protected function extractVideoMetadata(string $path): array
    {
        $result = ['width' => 0, 'height' => 0, 'duration' => null, 'fps' => null];

        if (!$this->ffprobePath) {
            return $result;
        }

        $cmd = $this->ffprobePath
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

        // Find video stream
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

            // Parse FPS from r_frame_rate (e.g. "30/1" or "30000/1001")
            $frameRate = $videoStream['r_frame_rate'] ?? '';
            if (str_contains($frameRate, '/')) {
                [$num, $den] = explode('/', $frameRate);
                if ((int) $den > 0) {
                    $result['fps'] = round((int) $num / (int) $den, 2);
                }
            }
        }

        // Duration from format
        $result['duration'] = isset($data['format']['duration'])
            ? round((float) $data['format']['duration'], 2)
            : null;

        return $result;
    }

    /**
     * Generate a thumbnail for a video file.
     *
     * @return string|null Relative path to thumbnail from stock-media root
     */
    protected function generateVideoThumbnail(array $file): ?string
    {
        if (!$this->ffmpegPath) {
            return null;
        }

        $thumbDir = $this->stockRoot . DIRECTORY_SEPARATOR . '.thumbs';
        if (!is_dir($thumbDir)) {
            @mkdir($thumbDir, 0755, true);
        }

        // Thumbnail filename: category-filename.jpg
        $thumbName = str_replace(['/', '\\'], '-', pathinfo($file['relative'], PATHINFO_FILENAME)) . '.jpg';
        $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $thumbName;

        // Extract frame at 1 second
        $cmd = $this->ffmpegPath
            . ' -i ' . escapeshellarg($file['absolute'])
            . ' -ss 1 -vframes 1 -q:v 3 -y '
            . escapeshellarg($thumbPath) . ' 2>/dev/null';

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($thumbPath)) {
            return '.thumbs/' . $thumbName;
        }

        return null;
    }

    /**
     * Category-to-keywords mapping for smart tagging.
     * Used when filenames are gibberish (hashes, numbers).
     */
    protected array $categoryKeywords = [
        'space' => ['space', 'galaxy', 'stars', 'universe', 'cosmos', 'nebula', 'planet', 'astronaut', 'rocket', 'astronomy', 'night-sky', 'orbital'],
        'nature' => ['nature', 'landscape', 'scenery', 'outdoors', 'trees', 'forest', 'mountains', 'river', 'sunset', 'wildlife', 'earth', 'green'],
        'art-craft' => ['art', 'craft', 'creative', 'handmade', 'diy', 'painting', 'drawing', 'artistic', 'design', 'crafting', 'maker'],
        'cars' => ['car', 'automotive', 'vehicle', 'driving', 'supercar', 'racing', 'sports-car', 'luxury-car', 'speed', 'drift', 'engine'],
        'cats' => ['cat', 'kitten', 'feline', 'cute', 'pets', 'funny-cat', 'adorable', 'animal', 'whiskers', 'playful'],
        'cooking' => ['cooking', 'food', 'recipe', 'kitchen', 'chef', 'meal', 'delicious', 'cuisine', 'baking', 'gourmet', 'foodie'],
        'fitness' => ['fitness', 'workout', 'exercise', 'gym', 'training', 'health', 'muscle', 'cardio', 'strength', 'motivation', 'bodybuilding'],
        'luxury' => ['luxury', 'wealth', 'rich', 'lifestyle', 'expensive', 'premium', 'elegant', 'fashion', 'designer', 'opulent', 'millionaire'],
        'satisfying' => ['satisfying', 'asmr', 'oddly-satisfying', 'relaxing', 'calming', 'mesmerizing', 'soothing', 'smooth', 'perfect', 'therapeutic'],
        'travel' => ['travel', 'adventure', 'explore', 'destination', 'tourism', 'wanderlust', 'journey', 'vacation', 'scenic', 'backpacking', 'globe'],
        'viral-hooks' => ['viral', 'hook', 'attention', 'trending', 'engaging', 'intro', 'opener', 'scroll-stopper', 'clickbait', 'transition'],
    ];

    /**
     * Check if a filename is "gibberish" (hash, numeric ID, etc.)
     * and therefore needs category-based tagging instead of filename-based.
     */
    protected function isGibberishFilename(string $name): bool
    {
        $clean = preg_replace('/[^a-zA-Z]/', '', $name);
        // All hex chars (hash)
        if (preg_match('/^[0-9a-fA-F]{16,}$/', preg_replace('/[^0-9a-fA-F]/', '', $name))) {
            return true;
        }
        // Mostly numbers
        $digits = preg_replace('/[^0-9]/', '', $name);
        if (strlen($digits) > strlen($clean) && strlen($clean) < 5) {
            return true;
        }
        // Very short alphabetic content after removing common suffixes
        $stripped = preg_replace('/\b(video|dashinit|mp4|mov|webm|reels?|shorts?)\b/i', '', $clean);
        if (strlen(trim($stripped)) < 4) {
            return true;
        }
        return false;
    }

    /**
     * Convert filename to human-readable title.
     * Falls back to category-based title for gibberish filenames.
     */
    protected function filenameToTitle(string $name, string $category = '', int $index = 0): string
    {
        if ($this->isGibberishFilename($name)) {
            $catLabel = ucwords(str_replace(['-', '_'], ' ', $category));
            return $catLabel . ' Clip ' . ($index + 1);
        }

        $name = str_replace(['_', '-'], ' ', $name);
        // Remove common watermarks/source tags
        $name = preg_replace('/\b(digishopers?|profilecard\.?\w*|digitalsnolimit\.?\w*|mzshop\s*ph)\b/i', '', $name);
        // Remove leading/trailing numbers with separators
        $name = preg_replace('/^\s*[\(\[\{]?\d+[\)\]\}]?\s*/', '', $name);
        $name = preg_replace('/\s*[\(\[\{]\d+[\)\]\}]\s*$/', '', $name);
        $name = trim($name);

        if (empty($name) || strlen($name) < 3) {
            $catLabel = ucwords(str_replace(['-', '_'], ' ', $category));
            return $catLabel . ' Clip ' . ($index + 1);
        }

        return ucwords(trim($name));
    }

    /**
     * Generate comma-separated tags from filename and category.
     * Uses category keyword mapping for gibberish filenames.
     */
    protected function filenameToTags(string $name, string $category): string
    {
        $tags = [];

        // Always add category keywords
        $catKey = strtolower(str_replace(' ', '-', $category));
        if (isset($this->categoryKeywords[$catKey])) {
            // Pick 5-6 keywords from the category
            $catTags = $this->categoryKeywords[$catKey];
            $tags = array_merge($tags, array_slice($catTags, 0, 6));
        } else {
            $tags[] = strtolower($category);
        }

        // Extract meaningful words from filename (if not gibberish)
        if (!$this->isGibberishFilename($name)) {
            $clean = preg_replace('/\b(digishopers?|profilecard\.?\w*|digitalsnolimit\.?\w*|mzshop\s*ph|video|dashinit)\b/i', '', $name);
            $parts = preg_split('/[\s\-_\(\)\[\]\.]+/', strtolower($clean));
            $parts = array_filter($parts, fn($p) => strlen($p) >= 3 && !ctype_digit($p));
            $tags = array_merge($tags, array_values($parts));
        }

        // Add generic video tags
        $tags[] = 'stock';
        $tags[] = 'clip';
        $tags[] = 'short';

        return implode(',', array_unique(array_values($tags)));
    }

    /**
     * Remove DB entries for files that no longer exist on disk.
     */
    protected function cleanOrphaned(): int
    {
        $isDryRun = $this->option('dry-run');
        $this->info('Checking for orphaned database entries...');

        $all = StockMedia::all();
        $orphaned = 0;

        foreach ($all as $media) {
            if (!file_exists($media->disk_path)) {
                $orphaned++;
                $this->line("  Orphaned: {$media->path}");
                if (!$isDryRun) {
                    $media->delete();
                }
            }
        }

        if ($orphaned === 0) {
            $this->info('No orphaned entries found.');
        } else {
            $verb = $isDryRun ? 'Would remove' : 'Removed';
            $this->info("{$verb} {$orphaned} orphaned entries.");
        }

        return 0;
    }

    /**
     * Find ffmpeg/ffprobe binary.
     */
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

        // Try system PATH
        $which = trim(shell_exec("which {$name} 2>/dev/null") ?? '');
        return !empty($which) ? $which : null;
    }
}
