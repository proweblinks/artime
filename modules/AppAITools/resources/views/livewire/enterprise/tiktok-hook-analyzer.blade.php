<div>
    @include('appaitools::livewire.enterprise._enterprise-tool-base')

    <div class="aith-tool">
        <div class="aith-nav">
            <a href="{{ route('app.ai-tools.enterprise-suite') }}" class="aith-nav-btn">
                <i class="fa-light fa-arrow-left"></i> Enterprise Suite
            </a>
        </div>

        <div class="aith-card">
            <div class="aith-e-tool-header">
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#eab308,#d97706);">
                    <i class="fa-light fa-bolt" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Hook Analyzer</h2>
                    <p>Analyze and improve your first 3 seconds for retention</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Hook Text / Script</label>
                <textarea wire:model="hookText" class="aith-input" rows="3"
                          placeholder="Paste your first 3 seconds script or describe your hook..."></textarea>
                @error('hookText')
                <span class="aith-e-field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. fitness, cooking, comedy">
                @error('niche')
                <span class="aith-e-field-error">{{ $message }}</span>
                @enderror
            </div>
            @include('appaitools::livewire.enterprise._youtube-connect', ['youtubeField' => 'youtubeChannel'])
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-bolt"></i> Analyze Hook
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Analyzing...
                </span>
                <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">2 credits</span>
            </button>
            @endif

            @if($isLoading)
            {{-- Loading Steps --}}
            <div class="aith-e-loading" x-data="{ step: 0 }" x-init="
                let steps = {{ count($loadingSteps) }};
                let interval = setInterval(() => { if(step < steps - 1) step++; }, 2500);
                $wire.on('loadingComplete', () => clearInterval(interval));
            ">
                <div class="aith-e-loading-title">Analyzing hook effectiveness...</div>
                <div class="aith-e-loading-steps">
                    @foreach($loadingSteps as $i => $step)
                    <div class="aith-e-loading-step"
                         :class="{ 'active': step === {{ $i }}, 'done': step > {{ $i }} }">
                        <span class="step-icon">
                            <template x-if="step > {{ $i }}"><i class="fa-solid fa-check"></i></template>
                            <template x-if="step <= {{ $i }}">{{ $i + 1 }}</template>
                        </span>
                        <span class="step-label">{{ $step }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="aith-e-progress-bar">
                    <div class="aith-e-progress-fill" :style="'width:' + ((step + 1) / {{ count($loadingSteps) }} * 100) + '%'"></div>
                </div>
            </div>
            @endif

            @if($result && !$isLoading)
            {{-- Results --}}
            <div class="aith-e-result-header">
                <span class="aith-e-result-title">Hook Analysis Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-hook-analyzer', 'Hook-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-hook-analyzer">
            {{-- Score --}}
            @php $score = $result['hook_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Hook Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent hook - high retention potential in first 3 seconds
                        @elseif($score >= 50) Good hook with room for stronger engagement
                        @else Needs significant improvement to capture viewer attention
                        @endif
                    </div>
                </div>
            </div>

            {{-- Analysis Breakdown --}}
            @if(isset($result['analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-bar"></i> Analysis Breakdown</div>
                @php
                    $subScores = [
                        'attention_grab' => ['label' => 'Attention Grab', 'color' => '#eab308'],
                        'curiosity_gap' => ['label' => 'Curiosity Gap', 'color' => '#3b82f6'],
                        'emotional_trigger' => ['label' => 'Emotional Trigger', 'color' => '#ec4899'],
                        'clarity' => ['label' => 'Clarity', 'color' => '#22c55e'],
                    ];
                @endphp
                <div style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:0.75rem;">
                @foreach($subScores as $key => $meta)
                    @if(isset($result['analysis'][$key]))
                    @php $subVal = intval($result['analysis'][$key]); @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $meta['label'] }}</span>
                            <span style="font-size:0.8rem;font-weight:600;color:{{ $meta['color'] }};">{{ $result['analysis'][$key] }}</span>
                        </div>
                        <div class="aith-e-progress-inline">
                            <div class="aith-e-progress-inline-fill" style="width:{{ min($subVal, 100) }}%;background:{{ $meta['color'] }};"></div>
                        </div>
                    </div>
                    @endif
                @endforeach
                </div>
                @if(isset($result['analysis']['pacing']))
                <div style="padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Pacing</span>
                    <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['analysis']['pacing'] }}</div>
                </div>
                @endif
            </div>
            @endif

            {{-- Strengths --}}
            @if(!empty($result['strengths']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-thumbs-up"></i> Strengths</div>
                <ul class="aith-e-list">
                    @foreach($result['strengths'] as $strength)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;color:#22c55e;"></i></span> {{ $strength }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Weaknesses --}}
            @if(!empty($result['weaknesses']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-thumbs-down"></i> Weaknesses</div>
                <ul class="aith-e-list">
                    @foreach($result['weaknesses'] as $weakness)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;color:#ef4444;"></i></span> {{ $weakness }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Improved Versions --}}
            @if(!empty($result['improved_versions']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-wand-magic-sparkles"></i> Improved Versions</div>
                @foreach($result['improved_versions'] as $verIdx => $version)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        @if(isset($version['style']))
                        <span class="aith-e-tag aith-e-tag-medium">{{ $version['style'] }}</span>
                        @endif
                        <button onclick="enterpriseCopy(document.getElementById('hook-ver-{{ $verIdx }}').textContent, 'Hook copied!')" class="aith-e-btn-copy">
                            <i class="fa-light fa-copy"></i> Copy
                        </button>
                    </div>
                    <div id="hook-ver-{{ $verIdx }}" style="font-size:0.9rem;color:#fff;font-weight:500;padding:0.75rem;background:rgba(234,179,8,0.08);border-radius:0.375rem;border-left:3px solid #eab308;margin-bottom:0.5rem;">
                        {{ $version['hook'] ?? $version['text'] ?? '' }}
                    </div>
                    @if(isset($version['why_better']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                        <strong style="color:rgba(255,255,255,0.6);">Why better:</strong> {{ $version['why_better'] }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Hook Formulas --}}
            @if(!empty($result['hook_formulas']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-flask"></i> Hook Formulas</div>
                <div class="aith-e-grid-2">
                @foreach($result['hook_formulas'] as $formula)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="font-weight:600;color:#eab308;font-size:0.875rem;margin-bottom:0.375rem;">{{ $formula['name'] ?? '' }}</div>
                    @if(isset($formula['template']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);margin-bottom:0.375rem;padding:0.375rem;background:rgba(0,0,0,0.2);border-radius:0.25rem;font-style:italic;">
                        {{ $formula['template'] }}
                    </div>
                    @endif
                    @if(isset($formula['example']))
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">
                        <strong style="color:rgba(255,255,255,0.5);">Example:</strong> {{ $formula['example'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Retention Tips --}}
            @if(!empty($result['retention_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Retention Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['retention_tips'] as $tip)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            </div>{{-- end pdf-content --}}
            @endif

            {{-- Error --}}
            @if(session('error'))
            <div class="aith-e-error">{{ session('error') }}</div>
            @endif
        </div>

        {{-- History --}}
        @include('appaitools::livewire.enterprise._enterprise-history')
    </div>
</div>
