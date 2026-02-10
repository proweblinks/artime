<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0, step: 0, tipIndex: 0,
    activeTab: null,
    tips: [
        'Repurposing one video into 5+ formats saves 80% creation time',
        'Blog posts from video content rank well due to natural language',
        'Social media snippets get 3x more engagement than full posts',
        'Email newsletters from video content have 25% higher open rates'
    ],
    steps: [
        { label: 'Parsing Content', icon: 'fa-file-lines' },
        { label: 'Analyzing Structure', icon: 'fa-sitemap' },
        { label: 'Generating Formats', icon: 'fa-clone' },
        { label: 'Polishing Output', icon: 'fa-sparkles' }
    ],
    interval: null, tipInterval: null,
    startLoading() {
        this.progress = 0; this.step = 0; this.tipIndex = 0;
        this.interval = setInterval(() => {
            if (this.progress < 30) this.progress += 2;
            else if (this.progress < 60) this.progress += 1;
            else if (this.progress < 85) this.progress += 0.5;
            else if (this.progress < 95) this.progress += 0.2;
            this.step = Math.min(Math.floor(this.progress / 25), this.steps.length - 1);
        }, 200);
        this.tipInterval = setInterval(() => { this.tipIndex = (this.tipIndex + 1) % this.tips.length; }, 4000);
    },
    stopLoading() {
        this.progress = 100; this.step = this.steps.length;
        clearInterval(this.interval); clearInterval(this.tipInterval);
    }
}"
x-init="Livewire.hook('message.processed', (msg, comp) => { if (comp.id === $wire.__instance.id && !$wire.isLoading) stopLoading(); });">

    {{-- Navigation --}}
    <div class="aith-nav">
        <a href="{{ route('app.ai-tools.more-tools') }}" class="aith-nav-btn">
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
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">🔄</span> {{ __('Content Multiplier') }}</h2>

        <div class="aith-feature-box aith-feat-teal">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Turn one script into multiple formats') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Blog posts, social media, emails') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Optimized for each platform') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Save hours of content creation') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Original Script / Transcript') }}</label>
            <textarea wire:model="originalContent" class="aith-textarea" rows="8" placeholder="{{ __('Paste your video script or transcript here...') }}"></textarea>
            @error('originalContent') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Output Formats') }}</label>
            <div class="aith-checkbox-group">
                @foreach($formats as $key => $label)
                <label class="aith-checkbox-item">
                    <input type="checkbox" wire:model="selectedFormats" value="{{ $key }}">
                    <span>{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @error('selectedFormats') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        <button wire:click="multiply" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="multiply">
                <i class="fa-light fa-clone"></i> {{ __('Multiply Content') }}
            </span>
            <span wire:loading wire:target="multiply">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Processing...') }}
            </span>
        </button>

        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">🔄</span> {{ __('Multiplying content...') }}</div>
                <div class="aith-progress-pct" x-text="Math.round(progress) + '%'"></div>
            </div>
            <div class="aith-progress-bar"><div class="aith-progress-fill" :style="'width:' + progress + '%'"></div></div>
            <div class="aith-steps-grid" style="grid-template-columns: repeat(2, 1fr);">
                <template x-for="(s, i) in steps" :key="i">
                    <div class="aith-step" :class="{ 'aith-step-done': i < step, 'aith-step-active': i === step }">
                        <span class="aith-step-icon"><i :class="i < step ? 'fa-light fa-check' : (i === step ? 'fa-light fa-spinner-third fa-spin' : 'fa-light ' + s.icon)"></i></span>
                        <span x-text="s.label"></span>
                    </div>
                </template>
            </div>
            <div class="aith-tip"><span class="aith-emoji">💡</span> <span x-text="tips[tipIndex]"></span></div>
        </div>
    </div>
    @endif

    @if($result && isset($result['outputs']))
    <div class="aith-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">✨</span> {{ __('Multiplied Content') }}</h2>
            <button class="aith-btn-secondary" wire:click="$set('result', null)">
                <i class="fa-light fa-arrow-rotate-left"></i> {{ __('New') }}
            </button>
        </div>

        {{-- Tabs --}}
        @php $outputKeys = array_keys($result['outputs']); $firstKey = $outputKeys[0] ?? ''; @endphp
        <div x-init="activeTab = '{{ $firstKey }}'">
            <div class="aith-tabs">
                @foreach($result['outputs'] as $format => $content)
                <button class="aith-tab" :class="activeTab === '{{ $format }}' ? 'aith-tab-active' : ''"
                    @click="activeTab = '{{ $format }}'">
                    {{ $formats[$format] ?? ucfirst(str_replace('_', ' ', $format)) }}
                </button>
                @endforeach
            </div>

            @foreach($result['outputs'] as $format => $content)
            <div x-show="activeTab === '{{ $format }}'" x-cloak>
                <div style="display: flex; justify-content: flex-end; margin-bottom: 0.5rem;">
                    <button class="aith-copy-btn" onclick="aithCopyToClipboard(this.parentElement.nextElementSibling.innerText, this)">
                        <i class="fa-light fa-copy"></i> {{ __('Copy') }}
                    </button>
                </div>
                <div class="aith-result-pre">{{ $content }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- History --}}
    @include('appaitools::livewire.partials._tool-history')

</div>
</div>
