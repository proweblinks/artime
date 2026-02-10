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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#f43f5e,#ec4899);">
                    <i class="fa-light fa-handshake" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Brand Deal Matchmaker</h2>
                    <p>Find perfect brand partnerships for your channel</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">YouTube Channel URL</label>
                <input type="url" wire:model="url" class="aith-input"
                       placeholder="https://youtube.com/@channel">
                @error('url') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" wire:target="analyze" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-handshake"></i> Find Brand Matches
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Analyzing...
                </span>
                <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">3 credits</span>
            </button>
            @endif

            @if($isLoading)
            {{-- Loading Steps --}}
            <div class="aith-e-loading" x-data="{ step: 0 }" x-init="
                let steps = {{ count($loadingSteps) }};
                let interval = setInterval(() => { if(step < steps - 1) step++; }, 2500);
                $wire.on('loadingComplete', () => clearInterval(interval));
            ">
                <div class="aith-e-loading-title">Matching brands...</div>
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
                <span class="aith-e-result-title">Brand Match Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-brand-deal-matchmaker', 'Brand-Deal-Matchmaker-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-brand-deal-matchmaker">

            {{-- Score --}}
            @php $score = $result['matchmaking_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Matchmaking Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent brand partnership potential
                        @elseif($score >= 50) Good brand deal opportunities available
                        @else Limited brand matches - consider growing your niche authority
                        @endif
                    </div>
                </div>
            </div>

            {{-- Channel Profile --}}
            @if(isset($result['channel_profile']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Channel Profile</div>
                <div class="aith-e-grid-2">
                    @foreach($result['channel_profile'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if($key === 'brand_safety_score')
                                @php $bs = intval($val); @endphp
                                <span class="aith-e-tag {{ $bs >= 80 ? 'aith-e-tag-high' : ($bs >= 50 ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $val }}</span>
                            @else
                                {{ is_array($val) ? implode(', ', $val) : $val }}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Brand Matches --}}
            @if(!empty($result['brand_matches']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-handshake"></i> Brand Matches</div>
                @foreach($result['brand_matches'] as $match)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                        @php $ms = $match['match_score'] ?? 0; @endphp
                        <span class="aith-e-match-badge {{ $ms >= 80 ? 'aith-e-match-high' : ($ms >= 50 ? 'aith-e-match-medium' : 'aith-e-match-low') }}">{{ $ms }}%</span>
                        <div style="flex:1;">
                            <div style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $match['brand'] ?? '' }}</div>
                            <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">{{ $match['industry'] ?? '' }} · {{ $match['deal_type'] ?? '' }}</div>
                        </div>
                        @if(isset($match['estimated_rate']))
                        <span style="color:#22c55e;font-weight:600;font-size:0.875rem;">{{ $match['estimated_rate'] }}</span>
                        @endif
                    </div>
                    @if(isset($match['reasoning']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.375rem;">{{ $match['reasoning'] }}</div>
                    @endif
                    @if(isset($match['pitch_angle']))
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">
                        <strong style="color:rgba(255,255,255,0.6);">Pitch:</strong> {{ $match['pitch_angle'] }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Pitch Templates --}}
            @if(!empty($result['pitch_templates']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-envelope"></i> Pitch Templates</div>
                @foreach($result['pitch_templates'] as $tplIdx => $template)
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;position:relative;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span class="aith-e-tag aith-e-tag-medium">{{ $template['type'] ?? 'Template' }}</span>
                        <button onclick="enterpriseCopy(document.getElementById('pitch-tpl-{{ $tplIdx }}').textContent, 'Pitch template copied!')" class="aith-e-btn-copy">
                            <i class="fa-light fa-copy"></i> Copy
                        </button>
                    </div>
                    <pre id="pitch-tpl-{{ $tplIdx }}" style="white-space:pre-wrap;font-size:0.8rem;color:rgba(255,255,255,0.5);margin:0;font-family:monospace;">{{ $template['template'] ?? '' }}</pre>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Outreach Strategy --}}
            @if(isset($result['outreach_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bullseye"></i> Outreach Strategy</div>
                <div class="aith-e-grid-2">
                    @foreach($result['outreach_strategy'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ is_array($val) ? implode(', ', $val) : $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tips --}}
            @if(!empty($result['tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Tips</div>
                <div class="aith-e-grid-2">
                    @foreach($result['tips'] as $tip)
                    <div class="aith-e-section-card" style="margin-bottom:0;">
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $tip }}</div>
                    </div>
                    @endforeach
                </div>
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
