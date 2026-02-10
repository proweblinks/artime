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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#eab308,#f97316);">
                    <i class="fa-light fa-arrow-trend-up" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Affiliate Goldmine Finder</h2>
                    <p>Discover high-paying affiliate opportunities in your niche</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">YouTube Channel URL</label>
                <input type="url" wire:model="url" class="aith-input"
                       placeholder="https://youtube.com/@channel">
                @error('url') <div class="aith-e-field-error">{{ $message }}</div> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. tech, fitness, finance">
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-arrow-trend-up"></i> Find Affiliates
                    <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">3 credits</span>
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Analyzing...
                </span>
            </button>
            @endif

            @if($isLoading)
            {{-- Loading Steps --}}
            <div class="aith-e-loading" x-data="{ step: 0 }" x-init="
                let steps = {{ count($loadingSteps) }};
                let interval = setInterval(() => { if(step < steps - 1) step++; }, 2500);
                $wire.on('loadingComplete', () => clearInterval(interval));
            ">
                <div class="aith-e-loading-title">Finding affiliate opportunities...</div>
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
                <span class="aith-e-result-title">Affiliate Analysis Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-affiliate-finder', 'Affiliate-Finder-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-affiliate-finder">

            {{-- Estimated Monthly Income Summary --}}
            @if(isset($result['estimated_monthly_income']))
            <div class="aith-e-summary-card aith-e-summary-card-green" style="margin-bottom:1rem;">
                <div class="aith-e-summary-label">Total Estimated Monthly Income</div>
                <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['estimated_monthly_income'] }}</div>
                <div class="aith-e-summary-sub">From {{ count($result['programs'] ?? []) }} affiliate programs</div>
            </div>
            @endif

            {{-- Score --}}
            @php $score = $result['affiliate_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Affiliate Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent affiliate potential
                        @elseif($score >= 50) Good affiliate opportunities available
                        @else Limited affiliate options - consider broadening your niche
                        @endif
                    </div>
                </div>
            </div>

            {{-- Channel Analysis --}}
            @if(isset($result['channel_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Channel Analysis</div>
                <div class="aith-e-grid-2">
                    @foreach($result['channel_analysis'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ is_array($val) ? implode(', ', $val) : $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Affiliate Programs --}}
            @if(!empty($result['programs']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-bill-trend-up"></i> Affiliate Programs</div>
                @foreach($result['programs'] as $program)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $program['program'] ?? '' }}</span>
                        @php $rs = $program['relevance_score'] ?? 0; @endphp
                        <span class="aith-e-tag {{ $rs >= 80 ? 'aith-e-tag-high' : ($rs >= 50 ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $rs }}/100</span>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.5rem;">
                        @if(isset($program['commission_rate']))
                        <span class="aith-e-pill aith-e-pill-green"><i class="fa-light fa-percent" style="font-size:0.65rem;"></i> {{ $program['commission_rate'] }}</span>
                        @endif
                        @if(isset($program['cookie_duration']))
                        <span class="aith-e-pill aith-e-pill-blue"><i class="fa-light fa-cookie" style="font-size:0.65rem;"></i> {{ $program['cookie_duration'] }}</span>
                        @endif
                        @if(isset($program['avg_payout']))
                        <span class="aith-e-pill aith-e-pill-orange"><i class="fa-light fa-sack-dollar" style="font-size:0.65rem;"></i> {{ $program['avg_payout'] }}</span>
                        @endif
                    </div>
                    @if(!empty($program['integration_ideas']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);">
                        {{ is_array($program['integration_ideas']) ? implode(' · ', $program['integration_ideas']) : $program['integration_ideas'] }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Script Templates --}}
            @if(!empty($result['script_templates']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-scroll"></i> Script Templates</div>
                @foreach($result['script_templates'] as $tplIdx => $template)
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;position:relative;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span class="aith-e-tag aith-e-tag-medium">{{ $template['type'] ?? 'Template' }}</span>
                        <button onclick="enterpriseCopy(document.getElementById('script-tpl-{{ $tplIdx }}').textContent, 'Script copied!')" class="aith-e-btn-copy">
                            <i class="fa-light fa-copy"></i> Copy
                        </button>
                    </div>
                    <pre id="script-tpl-{{ $tplIdx }}" style="white-space:pre-wrap;font-size:0.8rem;color:rgba(255,255,255,0.5);margin:0;font-family:monospace;">{{ $template['template'] ?? '' }}</pre>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Tips --}}
            @if(!empty($result['tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['tips'] as $tip)
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
