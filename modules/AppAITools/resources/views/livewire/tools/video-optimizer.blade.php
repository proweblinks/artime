<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0,
    step: 0,
    tipIndex: 0,
    tips: [
        'Titles with numbers get 45% more clicks',
        'Keep titles under 60 characters for best SEO',
        'Descriptions should be 200+ words with keywords',
        'Use 15-30 relevant tags per video',
        'Put important keywords in the first 25 characters',
        'Emojis in titles can increase CTR by 20%'
    ],
    steps: [
        { label: 'Fetching Video', icon: 'fa-download' },
        { label: 'Analyzing SEO', icon: 'fa-magnifying-glass' },
        { label: 'Generating Titles', icon: 'fa-heading' },
        { label: 'Writing Description', icon: 'fa-align-left' },
        { label: 'Finding Tags', icon: 'fa-tags' },
        { label: 'Calculating Score', icon: 'fa-chart-simple' }
    ],
    interval: null,
    tipInterval: null,
    startLoading() {
        this.progress = 0;
        this.step = 0;
        this.tipIndex = 0;
        this.interval = setInterval(() => {
            if (this.progress < 30) this.progress += 2;
            else if (this.progress < 60) this.progress += 1;
            else if (this.progress < 85) this.progress += 0.5;
            else if (this.progress < 95) this.progress += 0.2;
            this.step = Math.min(Math.floor(this.progress / (100 / this.steps.length)), this.steps.length - 1);
        }, 200);
        this.tipInterval = setInterval(() => {
            this.tipIndex = (this.tipIndex + 1) % this.tips.length;
        }, 4000);
    },
    stopLoading() {
        this.progress = 100;
        this.step = this.steps.length;
        clearInterval(this.interval);
        clearInterval(this.tipInterval);
    }
}"
x-init="
    $wire.on('loading-started', () => startLoading());
    Livewire.hook('message.processed', (msg, comp) => {
        if (comp.id === $wire.__instance.id && !$wire.isLoading) stopLoading();
    });
">

    {{-- Navigation --}}
    <div class="aith-nav">
        <a href="{{ route('app.ai-tools.index') }}" class="aith-nav-btn">
            <i class="fa-light fa-arrow-left"></i> {{ __('Back') }}
        </a>
        <div class="aith-nav-spacer"></div>
        @if(count($history) > 0)
        <button class="aith-nav-btn" onclick="document.getElementById('aith-history-panel').classList.toggle('aith-open')">
            <i class="fa-light fa-clock-rotate-left"></i> {{ __('History') }}
        </button>
        @endif
    </div>

    @if(!$result)
    {{-- Form Card --}}
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">🚀</span> {{ __('Video Optimizer') }}</h2>

        {{-- Feature Description --}}
        <div class="aith-feature-box aith-feat-blue">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Generate SEO-optimized titles') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Write rich descriptions with keywords') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Suggest strategic tags & hashtags') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Calculate before/after SEO score') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        {{-- Form --}}
        <div class="aith-form-group">
            <label class="aith-label">{{ __('Video URL') }}</label>
            <input type="url" wire:model="url" class="aith-input" placeholder="https://youtube.com/watch?v=...">
            @error('url') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        <button wire:click="optimize" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}>
            <span wire:loading.remove wire:target="optimize">
                <i class="fa-light fa-rocket"></i> {{ __('Optimize Video') }}
            </span>
            <span wire:loading wire:target="optimize">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Optimizing...') }}
            </span>
        </button>

        {{-- Loading State --}}
        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">🚀</span> {{ __('Optimizing...') }}</div>
                <div class="aith-progress-pct" x-text="Math.round(progress) + '%'"></div>
            </div>
            <div class="aith-progress-bar">
                <div class="aith-progress-fill" :style="'width:' + progress + '%'"></div>
            </div>
            <div class="aith-steps-grid">
                <template x-for="(s, i) in steps" :key="i">
                    <div class="aith-step" :class="{ 'aith-step-done': i < step, 'aith-step-active': i === step }">
                        <span class="aith-step-icon">
                            <i :class="i < step ? 'fa-light fa-check' : (i === step ? 'fa-light fa-spinner-third fa-spin' : 'fa-light ' + s.icon)"></i>
                        </span>
                        <span x-text="s.label"></span>
                    </div>
                </template>
            </div>
            <div class="aith-tip"><span class="aith-emoji">💡</span> <span x-text="tips[tipIndex]"></span></div>
        </div>
    </div>
    @endif

    {{-- Results --}}
    @if($result)
    <div class="aith-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">✨</span> {{ __('Optimization Results') }}</h2>
            <button class="aith-btn-secondary" wire:click="$set('result', null)">
                <i class="fa-light fa-arrow-rotate-left"></i> {{ __('New') }}
            </button>
        </div>

        {{-- Video Info --}}
        @if(isset($result['video_info']))
        <div class="aith-video-info">
            @if(isset($result['video_info']['thumbnail']))
            <img src="{{ $result['video_info']['thumbnail'] }}" alt="" class="aith-video-thumb">
            @endif
            <div class="aith-video-meta">
                <h4>{{ $result['video_info']['title'] ?? '' }}</h4>
                <p>{{ $result['video_info']['channel'] ?? '' }}</p>
                <div class="aith-video-stats">
                    @if(isset($result['video_info']['views']))
                    <span class="aith-badge aith-badge-ghost"><i class="fa-light fa-eye"></i> {{ $result['video_info']['views'] }}</span>
                    @endif
                    @if(isset($result['video_info']['likes']))
                    <span class="aith-badge aith-badge-ghost"><i class="fa-light fa-thumbs-up"></i> {{ $result['video_info']['likes'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- SEO Score --}}
        @if(isset($result['seo_score']))
        <div style="margin-bottom: 1.25rem;">
            <h3 class="aith-section-title"><i class="fa-light fa-gauge-high"></i> {{ __('SEO Score') }}</h3>
            <div class="aith-score-wrap">
                @if(isset($result['original_score']))
                <div style="text-align:center">
                    <div class="aith-score-gauge">
                        <svg viewBox="0 0 120 120">
                            <circle class="aith-gauge-bg" cx="60" cy="60" r="50"/>
                            <circle class="aith-gauge-fill" cx="60" cy="60" r="50"
                                stroke="{{ $result['original_score'] >= 70 ? '#10b981' : ($result['original_score'] >= 40 ? '#f59e0b' : '#ef4444') }}"
                                stroke-dasharray="314"
                                stroke-dashoffset="{{ 314 - (314 * ($result['original_score'] ?? 0) / 100) }}"/>
                        </svg>
                        <div class="aith-score-val" style="color: {{ $result['original_score'] >= 70 ? '#10b981' : ($result['original_score'] >= 40 ? '#f59e0b' : '#ef4444') }}">{{ $result['original_score'] }}</div>
                    </div>
                    <div class="aith-score-label">{{ __('Before') }}</div>
                </div>
                <div style="font-size: 1.5rem; color: #94a3b8;">→</div>
                @endif
                <div style="text-align:center">
                    <div class="aith-score-gauge">
                        <svg viewBox="0 0 120 120">
                            <circle class="aith-gauge-bg" cx="60" cy="60" r="50"/>
                            <circle class="aith-gauge-fill" cx="60" cy="60" r="50"
                                stroke="{{ $result['seo_score'] >= 70 ? '#10b981' : ($result['seo_score'] >= 40 ? '#f59e0b' : '#ef4444') }}"
                                stroke-dasharray="314"
                                stroke-dashoffset="{{ 314 - (314 * $result['seo_score'] / 100) }}"/>
                        </svg>
                        <div class="aith-score-val" style="color: {{ $result['seo_score'] >= 70 ? '#10b981' : ($result['seo_score'] >= 40 ? '#f59e0b' : '#ef4444') }}">{{ $result['seo_score'] }}</div>
                    </div>
                    <div class="aith-score-label">{{ __('After') }}</div>
                </div>
                @if(isset($result['original_score']))
                <div>
                    @php $improvement = $result['seo_score'] - $result['original_score']; @endphp
                    <span class="aith-badge {{ $improvement > 0 ? 'aith-badge-success' : 'aith-badge-high' }}" style="font-size: 0.9375rem; padding: 0.375rem 0.875rem;">
                        {{ $improvement > 0 ? '+' : '' }}{{ $improvement }} {{ __('points') }}
                    </span>
                </div>
                @endif
            </div>
            @if(isset($result['seo_summary']))
            <p style="font-size: 0.8125rem; color: #64748b; margin-top: 0.75rem;">{{ $result['seo_summary'] }}</p>
            @endif
        </div>
        @endif

        {{-- Tabs --}}
        <div class="aith-tabs">
            <button class="aith-tab {{ $activeTab === 'titles' ? 'aith-tab-active' : '' }}" wire:click="setTab('titles')">
                <i class="fa-light fa-heading"></i> {{ __('Titles') }}
            </button>
            <button class="aith-tab {{ $activeTab === 'description' ? 'aith-tab-active' : '' }}" wire:click="setTab('description')">
                <i class="fa-light fa-align-left"></i> {{ __('Description') }}
            </button>
            <button class="aith-tab {{ $activeTab === 'tags' ? 'aith-tab-active' : '' }}" wire:click="setTab('tags')">
                <i class="fa-light fa-tags"></i> {{ __('Tags') }}
            </button>
        </div>

        {{-- Titles Tab --}}
        <div style="{{ $activeTab !== 'titles' ? 'display:none' : '' }}">
            @if(isset($result['titles']) && count($result['titles']) > 0)
                @foreach($result['titles'] as $i => $title)
                <div class="aith-result-item">
                    <div class="aith-result-row">
                        <div style="flex:1">
                            <div class="aith-result-label">{{ ['🎯 Clickbait Style', '📊 SEO Focused', '💡 Creative Angle'][$i] ?? 'Option ' . ($i + 1) }}</div>
                            <div class="aith-result-text">{{ $title }}</div>
                        </div>
                        <button class="aith-copy-btn" onclick="aithCopyToClipboard('{{ addslashes($title) }}', this)">
                            <i class="fa-light fa-copy"></i> {{ __('Copy') }}
                        </button>
                    </div>
                </div>
                @endforeach
            @endif
        </div>

        {{-- Description Tab --}}
        <div style="{{ $activeTab !== 'description' ? 'display:none' : '' }}">
            @if(isset($result['description']))
            <div style="display: flex; justify-content: flex-end; margin-bottom: 0.5rem;">
                <button class="aith-copy-btn" onclick="aithCopyToClipboard(document.getElementById('aith-opt-desc').innerText, this)">
                    <i class="fa-light fa-copy"></i> {{ __('Copy Description') }}
                </button>
            </div>
            <div id="aith-opt-desc" class="aith-result-pre">{{ $result['description'] }}</div>
            @endif
        </div>

        {{-- Tags Tab --}}
        <div style="{{ $activeTab !== 'tags' ? 'display:none' : '' }}">
            @if(isset($result['tags']) && count($result['tags']) > 0)
            <div style="display: flex; justify-content: flex-end; margin-bottom: 0.75rem;">
                <button class="aith-copy-btn" onclick="aithCopyToClipboard('{{ addslashes(implode(', ', $result['tags'])) }}', this)">
                    <i class="fa-light fa-copy"></i> {{ __('Copy All Tags') }}
                </button>
            </div>
            <div class="aith-tags-wrap">
                @foreach($result['tags'] as $tag)
                <span class="aith-tag">{{ $tag }}</span>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Tips --}}
        @if(isset($result['tips']) && count($result['tips']) > 0)
        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
            <h3 class="aith-section-title"><i class="fa-light fa-lightbulb"></i> {{ __('Pro Tips') }}</h3>
            @foreach($result['tips'] as $tip)
            <div class="aith-tip" style="margin-bottom: 0.5rem;"><span class="aith-emoji">💡</span> {{ $tip }}</div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- History Panel --}}
    @if(count($history) > 0)
    <div id="aith-history-panel" class="aith-card" style="display:none; margin-top: 1rem;">
        <h3 class="aith-section-title"><i class="fa-light fa-clock-rotate-left"></i> {{ __('Recent Optimizations') }}</h3>
        @foreach($history as $item)
        <div class="aith-result-item" style="cursor:default;">
            <div class="aith-result-row">
                <div style="flex:1">
                    <div class="aith-result-text">{{ $item['title'] ?? 'Untitled' }}</div>
                    <div style="font-size: 0.6875rem; color: #94a3b8; margin-top: 0.25rem;">{{ \Carbon\Carbon::createFromTimestamp($item['created'])->diffForHumans() }}</div>
                </div>
                <span class="aith-badge aith-badge-ghost">{{ $item['platform'] ?? 'youtube' }}</span>
            </div>
        </div>
        @endforeach
    </div>
    <style>
        #aith-history-panel.aith-open { display: block !important; }
    </style>
    @endif

</div>
</div>
