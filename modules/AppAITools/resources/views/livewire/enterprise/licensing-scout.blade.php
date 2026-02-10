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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#14b8a6,#06b6d4);">
                    <i class="fa-light fa-file-certificate" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Licensing & Syndication Scout</h2>
                    <p>Find licensing opportunities for your content</p>
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
            <button wire:click="analyze" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <i class="fa-light fa-file-certificate"></i> Scout Opportunities
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
                <div class="aith-e-loading-title">Scouting licensing opportunities...</div>
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
                <span class="aith-e-result-title">Licensing & Syndication Results</span>
                <button wire:click="$set('result', null)" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                    <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                </button>
            </div>

            {{-- Score --}}
            @php $score = $result['licensing_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Licensing Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent licensing potential
                        @elseif($score >= 50) Good syndication opportunities available
                        @else Limited licensing options - consider building unique content assets
                        @endif
                    </div>
                </div>
            </div>

            {{-- Content Analysis --}}
            @if(isset($result['content_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Content Analysis</div>
                <div class="aith-e-grid-2">
                    @foreach($result['content_analysis'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if($key === 'licensing_potential')
                                @php $lp = strtolower($val); @endphp
                                <span class="aith-e-tag {{ $lp === 'high' ? 'aith-e-tag-high' : ($lp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $val }}</span>
                            @else
                                {{ is_array($val) ? implode(', ', $val) : $val }}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Opportunities --}}
            @if(!empty($result['opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-gem"></i> Licensing Opportunities</div>
                @foreach($result['opportunities'] as $opp)
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                        <span class="aith-e-tag aith-e-tag-medium">{{ $opp['type'] ?? '' }}</span>
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $opp['platform'] ?? '' }}</span>
                    </div>
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.5rem;">{{ $opp['description'] ?? '' }}</div>
                    <div style="display:flex;gap:1rem;font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.5rem;">
                        <span><strong style="color:rgba(255,255,255,0.6);">Revenue Model:</strong> {{ $opp['revenue_model'] ?? '-' }}</span>
                        <span><strong style="color:rgba(255,255,255,0.6);">Est. Monthly:</strong> {{ $opp['estimated_monthly'] ?? '-' }}</span>
                    </div>
                    @if(!empty($opp['requirements']))
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.5rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Requirements:</strong> {{ $opp['requirements'] }}
                    </div>
                    @endif
                    @if(!empty($opp['action_steps']))
                    <ul style="list-style:none;padding:0;margin:0.5rem 0 0;">
                        @foreach($opp['action_steps'] as $step)
                        <li style="font-size:0.75rem;color:rgba(255,255,255,0.4);padding:0.125rem 0;padding-left:0.75rem;position:relative;">
                            <span style="position:absolute;left:0;color:#7c3aed;">&#8226;</span> {{ $step }}
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Syndication Networks --}}
            @if(!empty($result['syndication_networks']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-network-wired"></i> Syndication Networks</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Network</th><th>Type</th><th>Revenue Share</th><th>Best For</th></tr></thead>
                        <tbody>
                        @foreach($result['syndication_networks'] as $network)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $network['network'] ?? '' }}</td>
                            <td>{{ $network['type'] ?? '-' }}</td>
                            <td>{{ $network['revenue_share'] ?? '-' }}</td>
                            <td>{{ $network['best_for'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Legal Considerations --}}
            @if(!empty($result['legal_considerations']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-scale-balanced"></i> Legal Considerations</div>
                <ul class="aith-e-list">
                    @foreach($result['legal_considerations'] as $item)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Action Plan --}}
            @if(!empty($result['action_plan']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-list-check"></i> Action Plan</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Step</th><th>Action</th><th>Timeline</th><th>Expected Outcome</th></tr></thead>
                        <tbody>
                        @foreach($result['action_plan'] as $plan)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $plan['step'] ?? '' }}</td>
                            <td>{{ $plan['action'] ?? '-' }}</td>
                            <td>{{ $plan['timeline'] ?? '-' }}</td>
                            <td>{{ $plan['expected_outcome'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
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
