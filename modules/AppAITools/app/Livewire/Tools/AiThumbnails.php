<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\AppAITools\Models\AiToolHistory;
use Modules\AppAITools\Services\ThumbnailService;

class AiThumbnails extends Component
{
    use WithFileUploads;

    // Mode & form
    public string $mode = 'quick';
    public string $title = '';
    public string $category = 'general';
    public string $style = 'professional';
    public int $variations = 2;
    public string $customPrompt = '';

    // YouTube (upgrade mode)
    public string $youtubeUrl = '';
    public ?array $youtubeData = null;
    public bool $isFetchingYouTube = false;

    // Reference image (reference mode)
    public $referenceImage = null; // Livewire upload
    public ?string $referenceImagePreview = null;
    public ?string $referenceStorageKey = null;

    // Advanced options (Phase 2)
    public bool $showAdvanced = false;
    public string $referenceType = 'auto';
    public string $compositionTemplate = 'auto';
    public string $expressionModifier = 'keep';
    public string $backgroundStyle = 'auto';
    public float $faceStrength = 0.8;
    public float $styleStrength = 0.7;

    // Face lock (Phase 6)
    public bool $faceLockEnabled = false;
    public ?string $faceLockStorageKey = null;
    public ?string $faceLockPreview = null;
    public $faceLockImage = null;

    // State
    public bool $isLoading = false;
    public ?array $result = null;
    public array $history = [];

    // Upscaling state (Phase 3)
    public array $upscalingIndex = [];

    // Inpainting state (Phase 4)
    public ?int $editingImageIndex = null;
    public string $editPrompt = '';

    // Bulk mode (Phase 5)
    public bool $bulkMode = false;
    public string $bulkUrls = '';
    public string $playlistUrl = '';
    public array $bulkItems = [];
    public bool $isBulkProcessing = false;

    public function mount()
    {
        $this->loadHistory();
    }

    public function loadHistory()
    {
        $teamId = session('current_team_id');
        if ($teamId) {
            $this->history = AiToolHistory::forTeam($teamId)
                ->forTool('ai_thumbnails')
                ->completed()
                ->orderByDesc('created')
                ->limit(10)
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id_secure,
                    'title' => $h->title,
                    'created' => $h->created,
                    'input_data' => $h->input_data,
                    'assets' => $h->assets->map(fn ($a) => ['path' => $a->file_path, 'metadata' => $a->metadata])->toArray(),
                ])
                ->toArray();
        }
    }

    public function updatedMode($value)
    {
        // Reset mode-specific state on switch
        $this->youtubeUrl = '';
        $this->youtubeData = null;
        $this->referenceImage = null;
        $this->referenceImagePreview = null;
        $this->referenceStorageKey = null;
        $this->resetErrorBag();
    }

    public function updatedReferenceImage()
    {
        $this->validate([
            'referenceImage' => 'image|max:10240', // 10MB max
        ]);

        if ($this->referenceImage) {
            $teamId = session('current_team_id', 0);
            $service = app(ThumbnailService::class);

            // Read file contents and store to disk
            $contents = file_get_contents($this->referenceImage->getRealPath());
            $base64 = base64_encode($contents);
            $mimeType = $this->referenceImage->getMimeType();
            $ext = $this->referenceImage->getClientOriginalExtension() ?: 'png';

            $this->referenceStorageKey = $service->storeReferenceImage($teamId, $base64, $ext);
            $this->referenceImagePreview = 'data:' . $mimeType . ';base64,' . $base64;

            // Clear upload to keep Livewire state small
            $this->referenceImage = null;
        }
    }

    public function removeReferenceImage()
    {
        $this->referenceImagePreview = null;
        $this->referenceStorageKey = null;
        $this->referenceImage = null;
    }

    public function fetchYouTubeData()
    {
        $this->validate([
            'youtubeUrl' => 'required|url',
        ]);

        $this->isFetchingYouTube = true;

        try {
            $youtubeService = app(\Modules\AppAITools\Services\YouTubeDataService::class);
            $data = $youtubeService->getVideoData($this->youtubeUrl);

            if (!$data) {
                session()->flash('error', __('Could not fetch video data. Please check the URL.'));
                $this->isFetchingYouTube = false;
                return;
            }

            $this->youtubeData = $data;

            // Auto-fill title if empty
            if (empty($this->title) && !empty($data['title'])) {
                $this->title = $data['title'];
            }

            // For upgrade mode, download the existing thumbnail as reference
            if ($this->mode === 'upgrade' && !empty($data['thumbnail'])) {
                try {
                    $thumbContents = file_get_contents($data['thumbnail']);
                    if ($thumbContents) {
                        $teamId = session('current_team_id', 0);
                        $service = app(ThumbnailService::class);
                        $base64 = base64_encode($thumbContents);
                        $this->referenceStorageKey = $service->storeReferenceImage($teamId, $base64, 'jpg');
                        $this->referenceImagePreview = $data['thumbnail'];
                    }
                } catch (\Exception $e) {
                    // Non-critical: can still generate without reference
                }
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isFetchingYouTube = false;
        }
    }

    public function generate()
    {
        $rules = [
            'title' => 'required|string|min:3|max:200',
            'style' => 'required|in:' . implode(',', array_keys(config('appaitools.thumbnail_styles'))),
            'category' => 'required|in:' . implode(',', array_keys(config('appaitools.thumbnail_categories'))),
            'variations' => 'required|integer|min:1|max:4',
        ];

        if ($this->mode === 'reference') {
            $rules['referenceStorageKey'] = 'required';
        }

        if ($this->mode === 'upgrade') {
            $rules['youtubeUrl'] = 'required|url';
        }

        $this->validate($rules, [
            'referenceStorageKey.required' => __('Please upload a reference image.'),
            'youtubeUrl.required' => __('Please enter a YouTube URL.'),
        ]);

        $this->isLoading = true;

        try {
            $service = app(ThumbnailService::class);

            $params = [
                'mode' => $this->mode,
                'title' => $this->title,
                'category' => $this->category,
                'style' => $this->style,
                'variations' => $this->variations,
                'customPrompt' => $this->customPrompt,
                'youtubeData' => $this->youtubeData,
                'referenceStorageKey' => $this->referenceStorageKey,
                // Phase 2 advanced options
                'referenceType' => $this->referenceType,
                'compositionTemplate' => $this->compositionTemplate,
                'expressionModifier' => $this->expressionModifier,
                'backgroundStyle' => $this->backgroundStyle,
                'faceStrength' => $this->faceStrength,
                'styleStrength' => $this->styleStrength,
                // Phase 6 face lock
                'faceLockStorageKey' => $this->faceLockEnabled ? $this->faceLockStorageKey : null,
            ];

            $this->result = $service->generatePro($params);
            $this->loadHistory();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function upscaleImage(int $index)
    {
        if (!$this->result || !isset($this->result['images'][$index])) {
            return;
        }

        $this->upscalingIndex[$index] = true;

        try {
            $service = app(ThumbnailService::class);
            $image = $this->result['images'][$index];
            $imagePath = $image['path'] ?? '';

            if (!$imagePath) {
                throw new \Exception(__('Image not found for upscaling.'));
            }

            $hdResult = $service->upscaleImage($imagePath, $this->result['history_id'] ?? null);

            // Add HD path to result
            $this->result['images'][$index]['hd_path'] = $hdResult['path'];
            $this->result['images'][$index]['hd_url'] = $hdResult['url'];
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            unset($this->upscalingIndex[$index]);
        }
    }

    public function startInpaintEdit(int $index)
    {
        $this->editingImageIndex = $index;
        $this->editPrompt = '';
    }

    public function cancelInpaintEdit()
    {
        $this->editingImageIndex = null;
        $this->editPrompt = '';
    }

    public function applyInpaintEdit(string $maskBase64)
    {
        if ($this->editingImageIndex === null || !$this->result) {
            return;
        }

        $this->validate([
            'editPrompt' => 'required|string|min:3|max:500',
        ]);

        $index = $this->editingImageIndex;
        $image = $this->result['images'][$index] ?? null;

        if (!$image || empty($image['path'])) {
            session()->flash('error', __('Image not found.'));
            return;
        }

        try {
            $service = app(ThumbnailService::class);
            $editedResult = $service->inpaintEdit($image['path'], $maskBase64, $this->editPrompt);

            // Replace original with edited
            $this->result['images'][$index]['path'] = $editedResult['path'];
            $this->result['images'][$index]['url'] = $editedResult['url'];
            unset($this->result['images'][$index]['hd_path'], $this->result['images'][$index]['hd_url']);

            $this->editingImageIndex = null;
            $this->editPrompt = '';
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function updatedFaceLockImage()
    {
        $this->validate([
            'faceLockImage' => 'image|max:10240',
        ]);

        if ($this->faceLockImage) {
            $teamId = session('current_team_id', 0);
            $service = app(ThumbnailService::class);

            $contents = file_get_contents($this->faceLockImage->getRealPath());
            $base64 = base64_encode($contents);
            $ext = $this->faceLockImage->getClientOriginalExtension() ?: 'png';

            $this->faceLockStorageKey = $service->storeReferenceImage($teamId, $base64, $ext);
            $this->faceLockPreview = 'data:' . $this->faceLockImage->getMimeType() . ';base64,' . $base64;

            $this->faceLockImage = null;
        }
    }

    public function removeFaceLock()
    {
        $this->faceLockEnabled = false;
        $this->faceLockStorageKey = null;
        $this->faceLockPreview = null;
        $this->faceLockImage = null;
    }

    public function loadHistoryItem(string $idSecure)
    {
        $teamId = session('current_team_id');
        $history = AiToolHistory::forTeam($teamId)
            ->where('id_secure', $idSecure)
            ->first();

        if ($history && $history->result_data) {
            $this->result = $history->result_data;
        }
    }

    // Bulk mode methods (Phase 5)
    public function fetchBulkUrls()
    {
        $urls = array_filter(array_map('trim', explode("\n", $this->bulkUrls)));
        $urls = array_slice($urls, 0, 10);

        if (empty($urls)) {
            session()->flash('error', __('Please enter at least one YouTube URL.'));
            return;
        }

        $this->bulkItems = [];
        $youtubeService = app(\Modules\AppAITools\Services\YouTubeDataService::class);

        foreach ($urls as $url) {
            try {
                $data = $youtubeService->getVideoData($url);
                $this->bulkItems[] = [
                    'url' => $url,
                    'data' => $data,
                    'status' => $data ? 'pending' : 'error',
                    'error' => $data ? null : 'Could not fetch video data',
                    'result' => null,
                ];
            } catch (\Exception $e) {
                $this->bulkItems[] = [
                    'url' => $url,
                    'data' => null,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'result' => null,
                ];
            }
        }
    }

    public function fetchPlaylistVideos()
    {
        if (empty($this->playlistUrl)) {
            session()->flash('error', __('Please enter a playlist URL.'));
            return;
        }

        try {
            $youtubeService = app(\Modules\AppAITools\Services\YouTubeDataService::class);
            $videos = $youtubeService->getPlaylistVideos($this->playlistUrl, 10);

            $this->bulkItems = [];
            foreach ($videos as $video) {
                $this->bulkItems[] = [
                    'url' => 'https://www.youtube.com/watch?v=' . $video['id'],
                    'data' => $video,
                    'status' => 'pending',
                    'error' => null,
                    'result' => null,
                ];
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function processBulk()
    {
        $this->isBulkProcessing = true;
        $service = app(ThumbnailService::class);

        foreach ($this->bulkItems as $idx => &$item) {
            if ($item['status'] !== 'pending') continue;

            $item['status'] = 'processing';

            try {
                $params = [
                    'mode' => 'upgrade',
                    'title' => $item['data']['title'] ?? 'Untitled',
                    'category' => $this->category,
                    'style' => $this->style,
                    'variations' => $this->variations,
                    'customPrompt' => $this->customPrompt,
                    'youtubeData' => $item['data'],
                    'referenceStorageKey' => null,
                    'referenceType' => $this->referenceType,
                    'compositionTemplate' => $this->compositionTemplate,
                    'expressionModifier' => $this->expressionModifier,
                    'backgroundStyle' => $this->backgroundStyle,
                    'faceStrength' => $this->faceStrength,
                    'styleStrength' => $this->styleStrength,
                    'faceLockStorageKey' => $this->faceLockEnabled ? $this->faceLockStorageKey : null,
                ];

                // Download YouTube thumbnail as reference
                if (!empty($item['data']['thumbnail'])) {
                    try {
                        $thumbContents = file_get_contents($item['data']['thumbnail']);
                        if ($thumbContents) {
                            $teamId = session('current_team_id', 0);
                            $params['referenceStorageKey'] = $service->storeReferenceImage($teamId, base64_encode($thumbContents), 'jpg');
                        }
                    } catch (\Exception $e) {
                        // Continue without reference
                    }
                }

                $item['result'] = $service->generatePro($params);
                $item['status'] = 'done';
            } catch (\Exception $e) {
                $item['status'] = 'error';
                $item['error'] = $e->getMessage();
            }
        }
        unset($item);

        $this->isBulkProcessing = false;
        $this->loadHistory();
    }

    public function regenerateBulkItem(int $idx)
    {
        if (!isset($this->bulkItems[$idx])) return;
        $this->bulkItems[$idx]['status'] = 'pending';
        $this->bulkItems[$idx]['error'] = null;
        $this->bulkItems[$idx]['result'] = null;

        // Process just this one item
        $service = app(ThumbnailService::class);
        $item = &$this->bulkItems[$idx];
        $item['status'] = 'processing';

        try {
            $params = [
                'mode' => 'upgrade',
                'title' => $item['data']['title'] ?? 'Untitled',
                'category' => $this->category,
                'style' => $this->style,
                'variations' => 1,
                'customPrompt' => $this->customPrompt,
                'youtubeData' => $item['data'],
                'referenceStorageKey' => null,
                'referenceType' => $this->referenceType,
                'compositionTemplate' => $this->compositionTemplate,
                'expressionModifier' => $this->expressionModifier,
                'backgroundStyle' => $this->backgroundStyle,
                'faceStrength' => $this->faceStrength,
                'styleStrength' => $this->styleStrength,
                'faceLockStorageKey' => $this->faceLockEnabled ? $this->faceLockStorageKey : null,
            ];

            if (!empty($item['data']['thumbnail'])) {
                try {
                    $thumbContents = file_get_contents($item['data']['thumbnail']);
                    if ($thumbContents) {
                        $teamId = session('current_team_id', 0);
                        $params['referenceStorageKey'] = $service->storeReferenceImage($teamId, base64_encode($thumbContents), 'jpg');
                    }
                } catch (\Exception $e) {}
            }

            $item['result'] = $service->generatePro($params);
            $item['status'] = 'done';
        } catch (\Exception $e) {
            $item['status'] = 'error';
            $item['error'] = $e->getMessage();
        }
        unset($item);
    }

    public function render()
    {
        return view('appaitools::livewire.tools.ai-thumbnails', [
            'modes' => config('appaitools.thumbnail_modes'),
            'categories' => config('appaitools.thumbnail_categories'),
            'styles' => config('appaitools.thumbnail_styles'),
        ]);
    }
}
