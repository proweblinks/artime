@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('AI Configuration') }}"
        description="{{ __('Set up and customize your AI settings easily') }}"
    />
@endsection

@section('content')
<div class="container max-w-800 pb-5">

    {{-- Update & Import AI Models --}}
    <div class="card shadow-none border-gray-300 mb-4">
        <div class="card-header fw-6">
            {{ __('Update & Import AI Models') }}
        </div>
        <div class="card-body">

            <div class="alert alert-primary mb-4" role="alert">
                <strong>{{ __('Note:') }}</strong>
                {{ __('You can update your AI models in two ways.') }}<br>
                1. {{ __('Update directly from built-in providers (no files are uploaded or sent outside).') }}<br>
                2. {{ __('Upload a JSON file manually (downloaded from our official website).') }}<br>
                <em>{{ __('Any models not listed in the new data will be removed from your database.') }}</em>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold mb-2">{{ __('Option 1: Update from Providers') }}</h6>
                <p class="text-muted small mb-3">
                    {{ __('This will automatically fetch and update models from OpenAI, Gemini, Deepseek, Claude...') }}<br>
                    {{ __('Safe: only updates database, no files are transferred.') }}
                </p>
                <form method="POST" action="{{ route('admin.ai-configuration.import-all') }}" class="actionForm" data-redirect="">
                    @csrf
                    <button type="submit" class="btn btn-dark b-r-10 w-100"
                            data-confirm="{{ __('Are you sure you want to update and import AI models automatically? This will replace existing ones.') }}">
                        <i class="fal fa-sync-alt me-1"></i> {{ __('Update from Providers') }}
                    </button>
                </form>
            </div>

            <hr class="my-4">

            <div>
                <h6 class="fw-bold mb-2">{{ __('Option 2: Upload JSON File') }}</h6>
                <p class="text-muted small mb-3">
                    {{ __('Download the latest AI models JSON file from our website, then upload it here to update your system.') }}
                    <br>
                    <a href="https://stackposts.com/ai_models.json" target="_blank">
                        <i class="fal fa-download me-1"></i>{{ __('Download JSON file') }}
                    </a>
                </p>
                <form method="POST" action="{{ route('admin.ai-configuration.import-json') }}" 
                      class="actionForm" enctype="multipart/form-data" data-redirect="">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="json_file" class="form-control" accept=".json,.txt" required>
                    </div>
                    <button type="submit" class="btn btn-danger b-r-10 w-100"
                            data-confirm="{{ __('Are you sure you want to upload and import models from JSON file? This will replace existing ones.') }}">
                        <i class="fal fa-upload me-1"></i> {{ __('Upload & Import JSON File') }}
                    </button>
                </form>
            </div>

        </div>
    </div>

    <form class="actionForm" action="{{ url_admin('settings/save') }}">

        {{-- General Configuration --}}
        <div class="card shadow-none border-gray-300 mb-4 ">
            <div class="card-header fw-6">{{ __('General configuration') }}</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select class="form-select" name="ai_status">
                            <option value="1" {{ get_option('ai_status', 1) == 1 ? 'selected' : '' }}>
                                {{ __('Enable') }}
                            </option>
                            <option value="0" {{ get_option('ai_status', 1) == 0 ? 'selected' : '' }}>
                                {{ __('Disable') }}
                            </option>
                        </select>
                    </div>

                    {{-- Default Language --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Default Language') }}</label>
                        <select class="form-select" name="ai_language">
                            @foreach (languages() as $key => $value)
                                <option value="{{ $key }}" {{ get_option('ai_language', 'en-US') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tone of Voice --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Default Tone Of Voice') }}</label>
                        <select class="form-select" name="ai_tone_of_voice">
                            @foreach (tone_of_voices() as $key => $value)
                                <option value="{{ $key }}" {{ get_option('ai_tone_of_voice', 'Friendly') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Creativity --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Default Creativity') }}</label>
                        <select class="form-select" name="ai_creativity">
                            @foreach (ai_creativity() as $key => $value)
                                <option value="{{ $key }}" {{ get_option('ai_creativity', 0) == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Max Input / Output --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Maximum Input Length') }}</label>
                        <input type="number" class="form-control" name="ai_max_input_lenght"
                               value="{{ get_option('ai_max_input_lenght', 100) }}">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Maximum Output Length') }}</label>
                        <input type="number" class="form-control" name="ai_max_output_lenght"
                               value="{{ get_option('ai_max_output_lenght', 1000) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- AI Platform by Category --}}
        <div class="card shadow-none border-gray-300 mb-4">
            <div class="card-header fw-6">{{ __('Default AI Platform') }}</div>
            <div class="card-body">
                <div class="row">
                    @php
                        $labels = [
                            'openai'   => 'OpenAI',
                            'gemini'   => 'Gemini',
                            'claude'   => 'Claude',
                            'deepseek' => 'DeepSeek',
                            'fal'      => 'FAL AI',
                            'minimax'  => 'MiniMax',
                        ];
                    @endphp

                    {{-- Text --}}
                    @if(!empty($platformsByCategory['text']))
                        <div class="col-md-6 mb-4">
                            <label class="form-label">{{ __('Text') }}</label>
                            <select class="form-select" name="ai_platform">
                                @foreach ($platformsByCategory['text'] as $p)
                                    <option value="{{ $p }}" {{ get_option('ai_platform', 'openai') == $p ? 'selected' : '' }}>
                                        {{ $labels[$p] ?? ucfirst($p) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Image --}}
                    @if(!empty($platformsByCategory['image']))
                        <div class="col-md-6 mb-4">
                            <label class="form-label">{{ __('Image') }}</label>
                            <select class="form-select" name="ai_platform_image">
                                @foreach ($platformsByCategory['image'] as $p)
                                    <option value="{{ $p }}" {{ get_option('ai_platform_image', 'openai') == $p ? 'selected' : '' }}>
                                        {{ $labels[$p] ?? ucfirst($p) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Video --}}
                    @if(!empty($platformsByCategory['video']))
                        <div class="col-md-6 mb-4">
                            <label class="form-label">{{ __('Video') }}</label>
                            <select class="form-select" name="ai_platform_video">
                                @foreach ($platformsByCategory['video'] as $p)
                                    <option value="{{ $p }}" {{ get_option('ai_platform_video', 'fal') == $p ? 'selected' : '' }}>
                                        {{ $labels[$p] ?? ucfirst($p) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Provider specific configs --}}
        @foreach ($providers as $providerKey => $providerName)
            <div class="card shadow-none border-gray-300 mb-4">
                <div class="card-header fw-6">{{ __($providerName) }}</div>
                <div class="card-body">
                    {{-- API Key --}}
                    <div class="mb-4">
                        <label class="form-label">{{ __('API Key') }}</label>
                        <input type="text" class="form-control"
                               name="ai_{{ $providerKey }}_api_key"
                               value="{{ get_option("ai_{$providerKey}_api_key", '') }}"
                               placeholder="{{ __('Enter API Key') }}">
                    </div>

                    {{-- Default Model per category --}}
                    @foreach ($categoryOrder as $category)
                        @php
                            $items = $models[$providerKey][$category] ?? collect();
                        @endphp
                        @if ($items->isNotEmpty())
                            <div class="mb-4">
                                <label class="form-label">
                                    {{ __('Default Model for :category', ['category' => $categoryLabels[$category] ?? ucfirst($category)]) }}
                                </label>
                                <select class="form-select" name="ai_{{ $providerKey }}_model_{{ $category }}">
                                    @foreach ($items as $item)
                                        <option value="{{ $item->model_key }}"
                                            {{ get_option("ai_{$providerKey}_model_{$category}") == $item->model_key ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Stock Media Providers --}}
        <div class="card shadow-none border-gray-300 mb-4">
            <div class="card-header fw-6">{{ __('Stock Media Providers') }}</div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    {{ __('Configure stock media providers for videos, images, and audio. These are used by Video Wizard and other media features.') }}
                </p>

                {{-- Pexels --}}
                <div class="border rounded p-3 mb-3">
                    <h6 class="fw-bold mb-3">
                        <i class="fab fa-pexels me-2"></i>{{ __('Pexels') }}
                        <small class="text-muted fw-normal">- {{ __('Stock videos & photos') }}</small>
                    </h6>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">{{ __('API Key') }}</label>
                            <input type="text" class="form-control" name="media_pexels_api_key"
                                   value="{{ get_option('media_pexels_api_key', '') }}"
                                   placeholder="{{ __('Enter Pexels API Key') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select class="form-select" name="media_pexels_status">
                                <option value="1" {{ get_option('media_pexels_status', 1) == 1 ? 'selected' : '' }}>{{ __('Enable') }}</option>
                                <option value="0" {{ get_option('media_pexels_status', 1) == 0 ? 'selected' : '' }}>{{ __('Disable') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Pixabay --}}
                <div class="border rounded p-3 mb-3">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-images me-2"></i>{{ __('Pixabay') }}
                        <small class="text-muted fw-normal">- {{ __('Stock videos, photos & music') }}</small>
                    </h6>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">{{ __('API Key') }}</label>
                            <input type="text" class="form-control" name="media_pixabay_api_key"
                                   value="{{ get_option('media_pixabay_api_key', '') }}"
                                   placeholder="{{ __('Enter Pixabay API Key') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select class="form-select" name="media_pixabay_status">
                                <option value="1" {{ get_option('media_pixabay_status', 1) == 1 ? 'selected' : '' }}>{{ __('Enable') }}</option>
                                <option value="0" {{ get_option('media_pixabay_status', 1) == 0 ? 'selected' : '' }}>{{ __('Disable') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Freesound --}}
                <div class="border rounded p-3">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-music me-2"></i>{{ __('Freesound') }}
                        <small class="text-muted fw-normal">- {{ __('Sound effects & ambient audio') }}</small>
                    </h6>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">{{ __('API Key') }}</label>
                            <input type="text" class="form-control" name="media_freesound_api_key"
                                   value="{{ get_option('media_freesound_api_key', '') }}"
                                   placeholder="{{ __('Enter Freesound API Key') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select class="form-select" name="media_freesound_status">
                                <option value="1" {{ get_option('media_freesound_status', 1) == 1 ? 'selected' : '' }}>{{ __('Enable') }}</option>
                                <option value="0" {{ get_option('media_freesound_status', 1) == 0 ? 'selected' : '' }}>{{ __('Disable') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Infrastructure Services --}}
        <div class="card shadow-none border-gray-300 mb-4">
            <div class="card-header fw-6">{{ __('Infrastructure Services') }}</div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    {{ __('Configure storage, GPU processing, and video processing services for Video Wizard.') }}
                </p>

                {{-- Cloudflare R2 Storage --}}
                <div class="border rounded p-3 mb-3">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-cloud me-2"></i>{{ __('Cloudflare R2 Storage') }}
                        <small class="text-muted fw-normal">- {{ __('Video & asset storage') }}</small>
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Account ID') }}</label>
                            <input type="text" class="form-control" name="r2_account_id"
                                   value="{{ get_option('r2_account_id', '') }}"
                                   placeholder="{{ __('Enter Account ID') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Bucket Name') }}</label>
                            <input type="text" class="form-control" name="r2_bucket_name"
                                   value="{{ get_option('r2_bucket_name', '') }}"
                                   placeholder="{{ __('Enter Bucket Name') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Access Key ID') }}</label>
                            <input type="text" class="form-control" name="r2_access_key_id"
                                   value="{{ get_option('r2_access_key_id', '') }}"
                                   placeholder="{{ __('Enter Access Key ID') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Access Key Secret') }}</label>
                            <input type="password" class="form-control" name="r2_access_key_secret"
                                   value="{{ get_option('r2_access_key_secret', '') }}"
                                   placeholder="{{ __('Enter Access Key Secret') }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">{{ __('Public Domain (Optional)') }}</label>
                            <input type="url" class="form-control" name="r2_public_domain"
                                   value="{{ get_option('r2_public_domain', '') }}"
                                   placeholder="{{ __('e.g., https://cdn.yourdomain.com') }}">
                            <small class="text-muted">{{ __('Custom domain for public file access. Leave empty to use default R2 URL.') }}</small>
                        </div>
                    </div>
                </div>

                {{-- RunPod GPU Processing --}}
                <div class="border rounded p-3 mb-3">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-microchip me-2"></i>{{ __('RunPod GPU Processing') }}
                        <small class="text-muted fw-normal">- {{ __('Serverless GPU for AI generation') }}</small>
                    </h6>
                    <p class="text-muted small mb-3">{{ __('RunPod provides serverless GPU endpoints for image/video generation. Add your API key and configure endpoint IDs for each service.') }}</p>

                    <div class="mb-3">
                        <label class="form-label">{{ __('API Key') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="runpod_api_key"
                               value="{{ get_option('runpod_api_key', '') }}"
                               placeholder="{{ __('Enter RunPod API Key (starts with rpa_...)') }}">
                        <small class="text-muted">{{ __('Get your API key from RunPod dashboard → Settings → API Keys') }}</small>
                    </div>

                    <hr class="my-3">
                    <h6 class="mb-3"><i class="fas fa-server me-2"></i>{{ __('Endpoint Configuration') }}</h6>

                    {{-- HiDream Endpoint --}}
                    <div class="mb-3">
                        <label class="form-label">
                            <span class="badge bg-purple me-2">Image</span>
                            {{ __('HiDream (Text-to-Image)') }}
                        </label>
                        <input type="text" class="form-control" name="runpod_hidream_endpoint"
                               value="{{ get_option('runpod_hidream_endpoint', '') }}"
                               placeholder="{{ __('e.g., rgq0go2nkcfx4h') }}">
                        <small class="text-muted">{{ __('Endpoint ID for HiDream image generation. Used in Video Wizard storyboard.') }}</small>
                    </div>

                    {{-- Kokoro TTS Endpoint --}}
                    <div class="mb-3">
                        <label class="form-label">
                            <span class="badge bg-success me-2">Audio</span>
                            {{ __('Kokoro TTS (Text-to-Speech)') }}
                        </label>
                        <input type="text" class="form-control" name="runpod_kokoro_tts_endpoint"
                               value="{{ get_option('runpod_kokoro_tts_endpoint', '') }}"
                               placeholder="{{ __('e.g., pzi4079jucgobi') }}">
                        <small class="text-muted">{{ __('Endpoint ID for Kokoro TTS high-quality voice generation. Used for narration in Video Wizard.') }}</small>
                    </div>

                    {{-- Generic GPU Endpoint (future use) --}}
                    <div class="mb-0">
                        <label class="form-label">
                            <span class="badge bg-secondary me-2">Custom</span>
                            {{ __('Custom GPU Endpoint') }}
                        </label>
                        <input type="text" class="form-control" name="runpod_custom_endpoint"
                               value="{{ get_option('runpod_custom_endpoint', '') }}"
                               placeholder="{{ __('Enter custom endpoint ID') }}">
                        <small class="text-muted">{{ __('Additional custom endpoint for other GPU workloads.') }}</small>
                    </div>
                </div>

                {{-- WaveSpeed AI --}}
                <div class="border rounded p-3 mb-3">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-bolt me-2"></i>{{ __('WaveSpeed AI') }}
                        <small class="text-muted fw-normal">- {{ __('Seedance video generation') }}</small>
                    </h6>
                    <p class="text-muted small mb-3">{{ __('WaveSpeed provides access to Seedance v1.5 Pro for image-to-video generation with auto-generated audio and lip-sync.') }}</p>
                    <div class="mb-0">
                        <label class="form-label">{{ __('API Key') }}</label>
                        <input type="text" class="form-control" name="ai_wavespeed_api_key"
                               value="{{ get_option('ai_wavespeed_api_key', '') }}"
                               placeholder="{{ __('Enter WaveSpeed API Key') }}">
                        <small class="text-muted">{{ __('Get your API key from wavespeed.ai dashboard.') }}</small>
                    </div>
                </div>

                {{-- Video Processor Service --}}
                <div class="border rounded p-3">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-film me-2"></i>{{ __('Video Processor Service') }}
                        <small class="text-muted fw-normal">- {{ __('Custom video processing microservice') }}</small>
                    </h6>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Service URL') }}</label>
                        <input type="url" class="form-control" name="video_processor_url"
                               value="{{ get_option('video_processor_url', '') }}"
                               placeholder="{{ __('e.g., https://video-processor.example.com') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Story Mode Configuration --}}
        <div class="card shadow-none border-gray-300 mb-4">
            <div class="card-header fw-6">
                <i class="fa-light fa-book-sparkles me-2"></i>{{ __('Story Mode Configuration') }}
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    {{ __('Configure the one-click Story Mode video generation pipeline. Story Mode creates short narrated videos from a single text prompt.') }}
                </p>

                <div class="row">
                    {{-- Enable/Disable --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Story Mode Status') }}</label>
                        <select class="form-select" name="story_mode_enabled">
                            <option value="1" {{ get_option('story_mode_enabled', 1) == 1 ? 'selected' : '' }}>{{ __('Enable') }}</option>
                            <option value="0" {{ get_option('story_mode_enabled', 1) == 0 ? 'selected' : '' }}>{{ __('Disable') }}</option>
                        </select>
                    </div>

                    {{-- AI Engine --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('AI Engine (Script Generation)') }}</label>
                        <select class="form-select" name="story_mode_ai_engine">
                            <option value="gemini" {{ get_option('story_mode_ai_engine', 'gemini') == 'gemini' ? 'selected' : '' }}>Gemini</option>
                            <option value="claude" {{ get_option('story_mode_ai_engine', 'gemini') == 'claude' ? 'selected' : '' }}>Claude</option>
                            <option value="openai" {{ get_option('story_mode_ai_engine', 'gemini') == 'openai' ? 'selected' : '' }}>OpenAI</option>
                            <option value="grok" {{ get_option('story_mode_ai_engine', 'gemini') == 'grok' ? 'selected' : '' }}>Grok</option>
                        </select>
                    </div>

                    {{-- AI Model --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('AI Model') }}</label>
                        <input type="text" class="form-control" name="story_mode_ai_model"
                               value="{{ get_option('story_mode_ai_model', 'gemini-2.5-flash') }}"
                               placeholder="gemini-2.5-flash">
                        <small class="text-muted">{{ __('Model for script and visual script generation') }}</small>
                    </div>

                    {{-- Image Model --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Image Generation Model') }}</label>
                        <select class="form-select" name="story_mode_image_model">
                            <option value="nanobanana-pro" {{ get_option('story_mode_image_model', 'nanobanana-pro') == 'nanobanana-pro' ? 'selected' : '' }}>NanoBanana Pro (Best Quality)</option>
                            <option value="nanobanana" {{ get_option('story_mode_image_model', 'nanobanana-pro') == 'nanobanana' ? 'selected' : '' }}>NanoBanana (Fast)</option>
                            <option value="hidream" {{ get_option('story_mode_image_model', 'nanobanana-pro') == 'hidream' ? 'selected' : '' }}>HiDream (Artistic)</option>
                        </select>
                    </div>

                    {{-- Video Model --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Video Animation Model') }}</label>
                        <select class="form-select" name="story_mode_video_model">
                            <option value="seedance-1.5-pro" {{ get_option('story_mode_video_model', 'seedance-1.5-pro') == 'seedance-1.5-pro' ? 'selected' : '' }}>Seedance v1.5 Pro</option>
                            <option value="seedance-1.5-fast" {{ get_option('story_mode_video_model', 'seedance-1.5-pro') == 'seedance-1.5-fast' ? 'selected' : '' }}>Seedance v1.5 Fast</option>
                        </select>
                    </div>

                    {{-- TTS Provider --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('TTS Provider') }}</label>
                        <select class="form-select" name="story_mode_tts_provider">
                            <option value="auto" {{ get_option('story_mode_tts_provider', 'auto') == 'auto' ? 'selected' : '' }}>{{ __('Auto Select') }}</option>
                            <option value="openai" {{ get_option('story_mode_tts_provider', 'auto') == 'openai' ? 'selected' : '' }}>OpenAI TTS</option>
                            <option value="kokoro" {{ get_option('story_mode_tts_provider', 'auto') == 'kokoro' ? 'selected' : '' }}>Kokoro TTS</option>
                            <option value="qwen3tts" {{ get_option('story_mode_tts_provider', 'auto') == 'qwen3tts' ? 'selected' : '' }}>Qwen3 TTS</option>
                        </select>
                    </div>
                </div>

                <hr class="my-3">
                <h6 class="fw-bold mb-3">{{ __('Default Settings') }}</h6>

                <div class="row">
                    {{-- Default Voice --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Default Voice') }}</label>
                        <input type="text" class="form-control" name="story_mode_default_voice"
                               value="{{ get_option('story_mode_default_voice', 'nova') }}"
                               placeholder="nova">
                    </div>

                    {{-- Default Aspect Ratio --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Default Aspect Ratio') }}</label>
                        <select class="form-select" name="story_mode_default_aspect">
                            <option value="9:16" {{ get_option('story_mode_default_aspect', '9:16') == '9:16' ? 'selected' : '' }}>9:16 (Portrait)</option>
                            <option value="16:9" {{ get_option('story_mode_default_aspect', '9:16') == '16:9' ? 'selected' : '' }}>16:9 (Landscape)</option>
                            <option value="1:1" {{ get_option('story_mode_default_aspect', '9:16') == '1:1' ? 'selected' : '' }}>1:1 (Square)</option>
                        </select>
                    </div>

                    {{-- Default Duration --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Default Duration (seconds)') }}</label>
                        <input type="number" class="form-control" name="story_mode_default_duration"
                               value="{{ get_option('story_mode_default_duration', 35) }}"
                               min="10" max="120">
                    </div>

                    {{-- Max Duration --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Max Duration (seconds)') }}</label>
                        <input type="number" class="form-control" name="story_mode_max_duration"
                               value="{{ get_option('story_mode_max_duration', 60) }}"
                               min="15" max="300">
                    </div>

                    {{-- Max Words --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Max Transcript Words') }}</label>
                        <input type="number" class="form-control" name="story_mode_max_words"
                               value="{{ get_option('story_mode_max_words', 450) }}"
                               min="50" max="1000">
                    </div>
                </div>

                <hr class="my-3">
                <h6 class="fw-bold mb-3">{{ __('Output Settings') }}</h6>

                <div class="row">
                    {{-- Captions --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Captions') }}</label>
                        <select class="form-select" name="story_mode_captions_enabled">
                            <option value="1" {{ get_option('story_mode_captions_enabled', 1) == 1 ? 'selected' : '' }}>{{ __('Enable') }}</option>
                            <option value="0" {{ get_option('story_mode_captions_enabled', 1) == 0 ? 'selected' : '' }}>{{ __('Disable') }}</option>
                        </select>
                    </div>

                    {{-- Background Music --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Background Music') }}</label>
                        <select class="form-select" name="story_mode_music_enabled">
                            <option value="1" {{ get_option('story_mode_music_enabled', 1) == 1 ? 'selected' : '' }}>{{ __('Enable') }}</option>
                            <option value="0" {{ get_option('story_mode_music_enabled', 1) == 0 ? 'selected' : '' }}>{{ __('Disable') }}</option>
                        </select>
                    </div>

                    {{-- Music Volume --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Music Volume') }}</label>
                        <input type="number" class="form-control" name="story_mode_music_volume"
                               value="{{ get_option('story_mode_music_volume', 0.15) }}"
                               min="0" max="1" step="0.05">
                        <small class="text-muted">{{ __('0 = silent, 1 = full volume') }}</small>
                    </div>

                    {{-- Export Quality --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Export Quality') }}</label>
                        <select class="form-select" name="story_mode_export_quality">
                            <option value="fast" {{ get_option('story_mode_export_quality', 'balanced') == 'fast' ? 'selected' : '' }}>{{ __('Fast') }}</option>
                            <option value="balanced" {{ get_option('story_mode_export_quality', 'balanced') == 'balanced' ? 'selected' : '' }}>{{ __('Balanced') }}</option>
                            <option value="best" {{ get_option('story_mode_export_quality', 'balanced') == 'best' ? 'selected' : '' }}>{{ __('Best') }}</option>
                        </select>
                    </div>

                    {{-- Export Resolution --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Export Resolution') }}</label>
                        <select class="form-select" name="story_mode_export_resolution">
                            <option value="720p" {{ get_option('story_mode_export_resolution', '1080p') == '720p' ? 'selected' : '' }}>720p</option>
                            <option value="1080p" {{ get_option('story_mode_export_resolution', '1080p') == '1080p' ? 'selected' : '' }}>1080p</option>
                            <option value="4k" {{ get_option('story_mode_export_resolution', '1080p') == '4k' ? 'selected' : '' }}>4K</option>
                        </select>
                    </div>
                </div>

                <hr class="my-3">
                <h6 class="fw-bold mb-3">{{ __('Transitions & Continuity') }}</h6>

                <div class="row">
                    {{-- Transition Type --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Scene Transition Type') }}</label>
                        <select class="form-select" name="story_mode_transition_type">
                            <option value="fade" {{ get_option('story_mode_transition_type', 'fade') == 'fade' ? 'selected' : '' }}>{{ __('Fade') }}</option>
                            <option value="dissolve" {{ get_option('story_mode_transition_type', 'fade') == 'dissolve' ? 'selected' : '' }}>{{ __('Dissolve') }}</option>
                            <option value="wipeleft" {{ get_option('story_mode_transition_type', 'fade') == 'wipeleft' ? 'selected' : '' }}>{{ __('Wipe Left') }}</option>
                            <option value="smoothleft" {{ get_option('story_mode_transition_type', 'fade') == 'smoothleft' ? 'selected' : '' }}>{{ __('Smooth Left') }}</option>
                            <option value="circlecrop" {{ get_option('story_mode_transition_type', 'fade') == 'circlecrop' ? 'selected' : '' }}>{{ __('Circle Crop') }}</option>
                            <option value="none" {{ get_option('story_mode_transition_type', 'fade') == 'none' ? 'selected' : '' }}>{{ __('None (Hard Cut)') }}</option>
                        </select>
                        <small class="text-muted">{{ __('Default fallback transition type. AI will select per-scene transitions dynamically based on content mood.') }}</small>
                    </div>

                    {{-- Crossfade Duration --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Crossfade Duration (seconds)') }}</label>
                        <input type="number" class="form-control" name="story_mode_crossfade_duration"
                               value="{{ get_option('story_mode_crossfade_duration', 0.5) }}"
                               min="0" max="3" step="0.1">
                        <small class="text-muted">{{ __('0 = hard cut, 0.5 = default smooth transition') }}</small>
                    </div>

                    {{-- Fade Out Duration --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Final Fade-to-Black (seconds)') }}</label>
                        <input type="number" class="form-control" name="story_mode_fadeout_duration"
                               value="{{ get_option('story_mode_fadeout_duration', 1.5) }}"
                               min="0" max="5" step="0.1">
                        <small class="text-muted">{{ __('Video and audio fade-out at the end. 0 = no fade.') }}</small>
                    </div>

                    {{-- Frame Chaining Mode --}}
                    <div class="col-md-6 mb-4">
                        <label class="form-label">{{ __('Sequential Frame Chaining') }}</label>
                        <select class="form-select" name="story_mode_frame_chaining">
                            <option value="0" {{ get_option('story_mode_frame_chaining', 0) == 0 ? 'selected' : '' }}>{{ __('Disable (Parallel - Fast)') }}</option>
                            <option value="1" {{ get_option('story_mode_frame_chaining', 0) == 1 ? 'selected' : '' }}>{{ __('Enable (Sequential - Slow)') }}</option>
                        </select>
                        <small class="text-muted">{{ __('Sequential mode extracts last frame of each clip for perfect continuity. ~4x slower.') }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save button --}}
        <div class="mt-4">
            <button type="submit" class="btn btn-dark b-r-10 w-100">
                {{ __('Save changes') }}
            </button>
        </div>
    </form>
</div>
@endsection