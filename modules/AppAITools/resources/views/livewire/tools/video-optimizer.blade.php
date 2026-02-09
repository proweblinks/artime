<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('app.ai-tools.index') }}" class="btn btn-ghost btn-sm">
            <i class="fa-light fa-arrow-left"></i>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
            <i class="fa-light fa-chart-line-up text-white text-lg"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ __('Video Optimizer') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('AI-powered SEO optimization for your video content') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Input Panel --}}
        <div class="lg:col-span-1">
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body">
                    <h3 class="font-semibold mb-4">{{ __('Analyze Video') }}</h3>

                    {{-- Platform Selector --}}
                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Platform') }}</span></label>
                        <select wire:model="platform" class="select select-bordered select-sm w-full">
                            @foreach($platforms as $key => $p)
                                <option value="{{ $key }}">{{ $p['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- URL Input --}}
                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">{{ __('Video URL') }}</span></label>
                        <input type="url" wire:model="url" class="input input-bordered input-sm w-full" placeholder="https://youtube.com/watch?v=...">
                        @error('url') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Analyze Button --}}
                    <button wire:click="optimize" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-full" {{ $isLoading ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="optimize">
                            <i class="fa-light fa-sparkles mr-1"></i>{{ __('Optimize') }}
                        </span>
                        <span wire:loading wire:target="optimize">
                            <i class="fa-light fa-spinner-third fa-spin mr-1"></i>{{ __('Analyzing...') }}
                        </span>
                    </button>

                    @if(session('error'))
                        <div class="alert alert-error mt-3 text-sm">{{ session('error') }}</div>
                    @endif
                </div>
            </div>

            {{-- History --}}
            @if(count($history) > 0)
                <div class="card bg-base-200 border border-base-300 mt-4">
                    <div class="card-body">
                        <h3 class="font-semibold mb-3 text-sm">{{ __('Recent') }}</h3>
                        <div class="space-y-2">
                            @foreach($history as $item)
                                <div class="flex items-center gap-2 text-xs text-base-content/70 p-2 rounded hover:bg-base-300 cursor-pointer">
                                    <i class="{{ $platforms[$item['platform']]['icon'] ?? 'fa-light fa-globe' }}"></i>
                                    <span class="truncate flex-1">{{ $item['title'] ?? 'Untitled' }}</span>
                                    <span>{{ \Carbon\Carbon::createFromTimestamp($item['created'])->diffForHumans(null, true) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Results Panel --}}
        <div class="lg:col-span-2">
            @if($result)
                <div class="space-y-4">
                    {{-- Video Info --}}
                    @if(isset($result['video_info']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <div class="flex items-start gap-4">
                                    @if(isset($result['video_info']['thumbnail']))
                                        <img src="{{ $result['video_info']['thumbnail'] }}" alt="" class="w-32 rounded-lg">
                                    @endif
                                    <div>
                                        <h3 class="font-semibold">{{ $result['video_info']['title'] ?? '' }}</h3>
                                        <p class="text-sm text-base-content/60 mt-1">{{ $result['video_info']['channel'] ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- SEO Score --}}
                    @if(isset($result['seo_score']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <div class="flex items-center gap-4">
                                    <div class="radial-progress text-primary" style="--value:{{ $result['seo_score'] }}; --size:4rem;">
                                        {{ $result['seo_score'] }}
                                    </div>
                                    <div>
                                        <h3 class="font-semibold">{{ __('SEO Score') }}</h3>
                                        <p class="text-sm text-base-content/60">{{ $result['seo_summary'] ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Optimized Titles --}}
                    @if(isset($result['titles']) && count($result['titles']) > 0)
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <h3 class="font-semibold mb-3"><i class="fa-light fa-heading mr-2"></i>{{ __('Optimized Titles') }}</h3>
                                <div class="space-y-2">
                                    @foreach($result['titles'] as $title)
                                        <div class="flex items-center gap-2 p-3 bg-base-300 rounded-lg group">
                                            <span class="flex-1">{{ $title }}</span>
                                            <button onclick="navigator.clipboard.writeText('{{ addslashes($title) }}')" class="btn btn-ghost btn-xs opacity-0 group-hover:opacity-100 transition-opacity">
                                                <i class="fa-light fa-copy"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Description --}}
                    @if(isset($result['description']))
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-semibold"><i class="fa-light fa-align-left mr-2"></i>{{ __('Optimized Description') }}</h3>
                                    <button onclick="navigator.clipboard.writeText(document.getElementById('opt-desc').innerText)" class="btn btn-ghost btn-xs">
                                        <i class="fa-light fa-copy mr-1"></i>{{ __('Copy') }}
                                    </button>
                                </div>
                                <div id="opt-desc" class="whitespace-pre-wrap text-sm bg-base-300 p-4 rounded-lg">{{ $result['description'] }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- Tags --}}
                    @if(isset($result['tags']) && count($result['tags']) > 0)
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-semibold"><i class="fa-light fa-tags mr-2"></i>{{ __('Tags & Hashtags') }}</h3>
                                    <button onclick="navigator.clipboard.writeText('{{ addslashes(implode(', ', $result['tags'])) }}')" class="btn btn-ghost btn-xs">
                                        <i class="fa-light fa-copy mr-1"></i>{{ __('Copy All') }}
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($result['tags'] as $tag)
                                        <span class="badge badge-sm badge-outline">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- Empty State --}}
                <div class="flex flex-col items-center justify-center h-64 text-base-content/40">
                    <i class="fa-light fa-chart-line-up text-5xl mb-4"></i>
                    <p class="text-lg">{{ __('Enter a video URL to get AI-powered optimization') }}</p>
                    <p class="text-sm mt-1">{{ __('Get better titles, descriptions, and tags') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
