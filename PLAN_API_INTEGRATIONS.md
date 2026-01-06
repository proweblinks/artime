# Comprehensive Plan: Video Wizard API Integrations

## Overview

This plan outlines the implementation of additional API integrations needed for the Video Wizard module. The integrations follow the existing architecture pattern used for OpenAI, Gemini, Claude, and DeepSeek.

---

## Current Architecture Summary

### Storage Pattern
- **Settings**: Stored in `options` table as key-value pairs
  - `ai_{provider}_api_key` - API keys
  - `ai_{provider}_model_{category}` - Default models per category
- **Models**: Stored in `ai_models` table with provider, model_key, category

### Service Pattern
- Individual service classes in `/app/Services/`
- Central dispatcher in `AIService.php`
- Facade access via `\AI::` helper

### Admin Pattern
- Controller: `/modules/AdminAIConfiguration/app/Http/Controllers/`
- View: `/modules/AdminAIConfiguration/resources/views/index.blade.php`
- Providers array drives UI generation automatically

---

## APIs to Implement

### Group A: AI Generation Services (Add to existing AI Configuration page)

| Provider | API Key | Categories | Purpose |
|----------|---------|------------|---------|
| FAL AI | `fal.key` | image, video | AI image/video generation (Flux, etc.) |
| MiniMax | `minimax.key` | video, audio | AI video generation, voice synthesis |

### Group B: Stock Media Services (New Admin Section)

| Provider | API Key | Categories | Purpose |
|----------|---------|------------|---------|
| Pexels | `pexels.key` | video, image | Stock videos and images |
| Pixabay | `pixabay.key` | video, image, audio | Stock videos, images, music |
| Freesound | `freesound.key` | audio | Sound effects and ambient audio |

### Group C: Infrastructure Services (New Admin Section)

| Provider | Keys | Purpose |
|----------|------|---------|
| Cloudflare R2 | account_id, access_key_id, access_key_secret, bucket_name | Video/asset storage |
| RunPod | api_key | GPU processing for video rendering |
| Video Processor | url | Custom video processing microservice |

---

## Implementation Steps

### Phase 1: FAL AI Integration

#### 1.1 Create FAL Service Class
**File**: `/app/Services/FalService.php`

```php
<?php

namespace App\Services;

use GuzzleHttp\Client;

class FalService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl = 'https://fal.run';

    public function __construct()
    {
        $this->apiKey = get_option('ai_fal_api_key', '');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Key ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    // Methods: generateImage(), generateVideo()
}
```

#### 1.2 Update AIService.php
- Add `FalService` to constructor injection
- Add to `getService()` match statement
- Add to `getPlatforms()` array

#### 1.3 Update Admin Controller
- Add `'fal' => 'FAL AI'` to `$providers` array

#### 1.4 Add FAL Models to Database
Insert models via JSON import or direct seeding:
- `fal/flux-pro` (image)
- `fal/flux-dev` (image)
- `fal/kling-video` (video)
- etc.

---

### Phase 2: MiniMax Integration

#### 2.1 Create MiniMax Service Class
**File**: `/app/Services/MiniMaxService.php`

```php
<?php

namespace App\Services;

class MiniMaxService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.minimaxi.chat/v1';

    // Methods: generateVideo(), generateSpeech(), textToSpeech()
}
```

#### 2.2 Integration Points
- Same pattern as FAL: Service → AIService → Admin Controller → View

---

### Phase 3: Stock Media Services (New Module)

#### 3.1 Create New Admin Module
**Module**: `AdminMediaConfiguration`

```
modules/AdminMediaConfiguration/
├── app/
│   └── Http/
│       └── Controllers/
│           └── AdminMediaConfigurationController.php
├── resources/
│   └── views/
│       └── index.blade.php
├── routes/
│   └── web.php
└── module.json
```

#### 3.2 Create Media Services

**File**: `/app/Services/PexelsService.php`
```php
<?php

namespace App\Services;

class PexelsService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.pexels.com/v1';

    public function searchVideos(string $query, array $options = []): array
    public function searchPhotos(string $query, array $options = []): array
    public function getVideo(string $id): array
}
```

**File**: `/app/Services/PixabayService.php`
```php
<?php

namespace App\Services;

class PixabayService
{
    protected $apiKey;
    protected $baseUrl = 'https://pixabay.com/api';

    public function searchVideos(string $query, array $options = []): array
    public function searchImages(string $query, array $options = []): array
    public function searchMusic(string $query, array $options = []): array
}
```

**File**: `/app/Services/FreesoundService.php`
```php
<?php

namespace App\Services;

class FreesoundService
{
    protected $apiKey;
    protected $baseUrl = 'https://freesound.org/apiv2';

    public function searchSounds(string $query, array $options = []): array
    public function getSound(int $id): array
    public function downloadSound(int $id): string
}
```

#### 3.3 Admin View for Media Configuration

```blade
{{-- Stock Media Providers --}}
@foreach (['pexels' => 'Pexels', 'pixabay' => 'Pixabay', 'freesound' => 'Freesound'] as $key => $name)
    <div class="card shadow-none border-gray-300 mb-4">
        <div class="card-header fw-6">{{ $name }}</div>
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label">{{ __('API Key') }}</label>
                <input type="text" class="form-control"
                       name="media_{{ $key }}_api_key"
                       value="{{ get_option("media_{$key}_api_key", '') }}"
                       placeholder="{{ __('Enter API Key') }}">
            </div>
            <div class="mb-4">
                <label class="form-label">{{ __('Status') }}</label>
                <select class="form-select" name="media_{{ $key }}_status">
                    <option value="1" {{ get_option("media_{$key}_status", 1) == 1 ? 'selected' : '' }}>
                        {{ __('Enable') }}
                    </option>
                    <option value="0" {{ get_option("media_{$key}_status", 1) == 0 ? 'selected' : '' }}>
                        {{ __('Disable') }}
                    </option>
                </select>
            </div>
        </div>
    </div>
@endforeach
```

---

### Phase 4: Infrastructure Services (New Module)

#### 4.1 Create Infrastructure Admin Module
**Module**: `AdminInfraConfiguration`

#### 4.2 Create Storage Service

**File**: `/app/Services/R2StorageService.php`
```php
<?php

namespace App\Services;

use Aws\S3\S3Client;

class R2StorageService
{
    protected $client;
    protected $bucket;

    public function __construct()
    {
        $accountId = get_option('r2_account_id', '');
        $accessKeyId = get_option('r2_access_key_id', '');
        $accessKeySecret = get_option('r2_access_key_secret', '');
        $this->bucket = get_option('r2_bucket_name', '');

        $this->client = new S3Client([
            'region' => 'auto',
            'version' => 'latest',
            'endpoint' => "https://{$accountId}.r2.cloudflarestorage.com",
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $accessKeySecret,
            ],
        ]);
    }

    public function upload(string $path, $content, string $contentType = null): string
    public function getUrl(string $path): string
    public function delete(string $path): bool
    public function getSignedUrl(string $path, int $expiry = 3600): string
}
```

#### 4.3 Create RunPod Service

**File**: `/app/Services/RunPodService.php`
```php
<?php

namespace App\Services;

class RunPodService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.runpod.ai/v2';

    public function runJob(string $endpointId, array $input): array
    public function getJobStatus(string $jobId): array
    public function cancelJob(string $jobId): bool
}
```

#### 4.4 Admin View for Infrastructure

```blade
{{-- Cloudflare R2 Storage --}}
<div class="card shadow-none border-gray-300 mb-4">
    <div class="card-header fw-6">{{ __('Cloudflare R2 Storage') }}</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-4">
                <label class="form-label">{{ __('Account ID') }}</label>
                <input type="text" class="form-control" name="r2_account_id"
                       value="{{ get_option('r2_account_id', '') }}">
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label">{{ __('Bucket Name') }}</label>
                <input type="text" class="form-control" name="r2_bucket_name"
                       value="{{ get_option('r2_bucket_name', '') }}">
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label">{{ __('Access Key ID') }}</label>
                <input type="text" class="form-control" name="r2_access_key_id"
                       value="{{ get_option('r2_access_key_id', '') }}">
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label">{{ __('Access Key Secret') }}</label>
                <input type="password" class="form-control" name="r2_access_key_secret"
                       value="{{ get_option('r2_access_key_secret', '') }}">
            </div>
        </div>
    </div>
</div>

{{-- RunPod GPU Processing --}}
<div class="card shadow-none border-gray-300 mb-4">
    <div class="card-header fw-6">{{ __('RunPod GPU Processing') }}</div>
    <div class="card-body">
        <div class="mb-4">
            <label class="form-label">{{ __('API Key') }}</label>
            <input type="text" class="form-control" name="runpod_api_key"
                   value="{{ get_option('runpod_api_key', '') }}">
        </div>
    </div>
</div>

{{-- Video Processor --}}
<div class="card shadow-none border-gray-300 mb-4">
    <div class="card-header fw-6">{{ __('Video Processor Service') }}</div>
    <div class="card-body">
        <div class="mb-4">
            <label class="form-label">{{ __('Service URL') }}</label>
            <input type="url" class="form-control" name="video_processor_url"
                   value="{{ get_option('video_processor_url', '') }}"
                   placeholder="https://video-processor.example.com">
        </div>
    </div>
</div>
```

---

## Option A: Add Everything to Existing AI Configuration Page

Instead of creating new modules, we can extend the existing AdminAIConfiguration module:

### Modified Admin Controller

```php
public function index()
{
    // AI Providers (existing)
    $providers = [
        'openai'   => 'OpenAI',
        'gemini'   => 'Gemini AI',
        'deepseek' => 'Deepseek AI',
        'claude'   => 'Claude AI',
        'fal'      => 'FAL AI',      // NEW
        'minimax'  => 'MiniMax AI',  // NEW
    ];

    // Stock Media Providers (NEW)
    $mediaProviders = [
        'pexels'    => 'Pexels',
        'pixabay'   => 'Pixabay',
        'freesound' => 'Freesound',
    ];

    // Infrastructure (NEW)
    $infraProviders = [
        'r2'              => 'Cloudflare R2',
        'runpod'          => 'RunPod',
        'video_processor' => 'Video Processor',
    ];

    // ... rest of method
}
```

---

## Database Seeders for New Models

**File**: `database/seeders/AIModelsSeeder.php` (add to existing)

```php
// FAL AI Models
['provider' => 'fal', 'model_key' => 'fal-ai/flux-pro/v1.1', 'name' => 'Flux Pro v1.1 - Professional image generation', 'category' => 'image'],
['provider' => 'fal', 'model_key' => 'fal-ai/flux/dev', 'name' => 'Flux Dev - Fast image generation', 'category' => 'image'],
['provider' => 'fal', 'model_key' => 'fal-ai/kling-video/v1/standard/image-to-video', 'name' => 'Kling Video - Image to video', 'category' => 'video'],
['provider' => 'fal', 'model_key' => 'fal-ai/minimax/video-01', 'name' => 'MiniMax Video-01 via FAL', 'category' => 'video'],

// MiniMax Models
['provider' => 'minimax', 'model_key' => 'video-01', 'name' => 'Video-01 - Text/Image to video', 'category' => 'video'],
['provider' => 'minimax', 'model_key' => 'speech-01', 'name' => 'Speech-01 - Text to speech', 'category' => 'speech'],
['provider' => 'minimax', 'model_key' => 'abab6.5s-chat', 'name' => 'Abab 6.5s - Text generation', 'category' => 'text'],
```

---

## Files to Create/Modify Summary

### New Files to Create

| File | Purpose |
|------|---------|
| `/app/Services/FalService.php` | FAL AI integration |
| `/app/Services/MiniMaxService.php` | MiniMax AI integration |
| `/app/Services/PexelsService.php` | Pexels stock media |
| `/app/Services/PixabayService.php` | Pixabay stock media |
| `/app/Services/FreesoundService.php` | Freesound audio |
| `/app/Services/R2StorageService.php` | Cloudflare R2 storage |
| `/app/Services/RunPodService.php` | RunPod GPU processing |
| `/app/Services/VideoProcessorService.php` | Video processing |

### Files to Modify

| File | Changes |
|------|---------|
| `/app/Services/AIService.php` | Add FAL, MiniMax to constructor, getService(), getPlatforms() |
| `/modules/AdminAIConfiguration/app/Http/Controllers/AdminAIConfigurationController.php` | Add new providers to $providers array, add media/infra sections |
| `/modules/AdminAIConfiguration/resources/views/index.blade.php` | Add Media Providers section, Infrastructure section |

---

## Implementation Order

1. **Phase 1** (AI Providers): FAL AI, MiniMax
   - Create service classes
   - Update AIService
   - Update Admin controller
   - Seed models to database

2. **Phase 2** (Stock Media): Pexels, Pixabay, Freesound
   - Create service classes
   - Add admin section to existing page

3. **Phase 3** (Infrastructure): R2, RunPod, Video Processor
   - Create service classes
   - Add admin section to existing page

4. **Phase 4** (Video Wizard Integration):
   - Update Video Wizard to use new services
   - Create unified MediaService facade

---

## Option Keys Reference

After implementation, these options will be available:

```php
// AI Providers
get_option('ai_fal_api_key')
get_option('ai_fal_model_image')
get_option('ai_fal_model_video')
get_option('ai_minimax_api_key')
get_option('ai_minimax_model_video')
get_option('ai_minimax_model_speech')

// Stock Media
get_option('media_pexels_api_key')
get_option('media_pexels_status')
get_option('media_pixabay_api_key')
get_option('media_pixabay_status')
get_option('media_freesound_api_key')
get_option('media_freesound_status')

// Infrastructure
get_option('r2_account_id')
get_option('r2_bucket_name')
get_option('r2_access_key_id')
get_option('r2_access_key_secret')
get_option('runpod_api_key')
get_option('video_processor_url')
```

---

## Estimated Effort

| Phase | Components | Estimated Time |
|-------|------------|----------------|
| Phase 1 | FAL AI + MiniMax | 4-6 hours |
| Phase 2 | Pexels + Pixabay + Freesound | 3-4 hours |
| Phase 3 | R2 + RunPod + Video Processor | 3-4 hours |
| Phase 4 | Video Wizard Integration | 2-3 hours |
| **Total** | | **12-17 hours** |

---

## Ready to Proceed?

Confirm to begin implementation starting with Phase 1 (FAL AI and MiniMax).
