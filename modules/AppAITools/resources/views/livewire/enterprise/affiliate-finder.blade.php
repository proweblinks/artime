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
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. tech, fitness, finance">
            </div>
            <button wire:click="analyze" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <i class="fa-light fa-arrow-trend-up"></i> Find Affiliates
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
                <button wire:click="$set('result', null)" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                    <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                </button>
            </div>

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
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Program</th><th>Network</th><th>Commission Rate</th><th>Cookie Duration</th><th>Avg Payout</th><th>Relevance</th><th>Integration Ideas</th></tr></thead>
                        <tbody>
                        @foreach($result['programs'] as $program)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $program['program'] ?? '' }}</td>
                            <td>{{ $program['network'] ?? '-' }}</td>
                            <td>{{ $program['commission_rate'] ?? '-' }}</td>
                            <td>{{ $program['cookie_duration'] ?? '-' }}</td>
                            <td>{{ $program['avg_payout'] ?? '-' }}</td>
                            <td>
                                @php $rs = $program['relevance_score'] ?? 0; @endphp
                                <span class="aith-e-tag {{ $rs >= 80 ? 'aith-e-tag-high' : ($rs >= 50 ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $rs >= 80 ? 'High' : ($rs >= 50 ? 'Medium' : 'Low') }}</span>
                            </td>
                            <td>{{ is_array($program['integration_ideas'] ?? null) ? implode(', ', $program['integration_ideas']) : ($program['integration_ideas'] ?? '-') }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Script Templates --}}
            @if(!empty($result['script_templates']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-scroll"></i> Script Templates</div>
                @foreach($result['script_templates'] as $template)
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;">
                    <span class="aith-e-tag aith-e-tag-medium">{{ $template['type'] ?? 'Template' }}</span>
                    <pre style="white-space:pre-wrap;font-size:0.8rem;color:rgba(255,255,255,0.5);margin-top:0.5rem;">{{ $template['template'] ?? '' }}</pre>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Estimated Monthly Income --}}
            @if(isset($result['estimated_monthly_income']))
            <div class="aith-e-section-card">
                <div style="text-align:center;padding:1rem;">
                    <span style="font-size:1.5rem;font-weight:800;color:#86efac;">{{ $result['estimated_monthly_income'] }}</span>
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-top:0.25rem;">Estimated Monthly Affiliate Income</div>
                </div>
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
