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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#3b82f6,#06b6d4);">
                    <i class="fa-light fa-chart-pie" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Revenue Diversification</h2>
                    <p>Identify untapped income streams for your channel</p>
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
                <i class="fa-light fa-chart-pie"></i> Analyze Revenue Streams
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
                <div class="aith-e-loading-title">Analyzing revenue streams...</div>
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
                <span class="aith-e-result-title">Revenue Diversification Results</span>
                <button wire:click="$set('result', null)" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                    <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                </button>
            </div>

            {{-- Score --}}
            @php $score = $result['diversification_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Diversification Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent revenue diversification
                        @elseif($score >= 50) Moderate diversification - room for growth
                        @else Highly concentrated revenue - diversification recommended
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
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Current Revenue Audit --}}
            @if(!empty($result['current_revenue_audit']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-clipboard-check"></i> Current Revenue Audit</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Stream</th><th>Status</th><th>Potential</th></tr></thead>
                        <tbody>
                        @foreach($result['current_revenue_audit'] as $item)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $item['stream'] ?? '' }}</td>
                            <td>
                                @php $status = strtolower($item['status'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $status === 'active' ? 'aith-e-tag-active' : ($status === 'underutilized' ? 'aith-e-tag-underutilized' : 'aith-e-tag-inactive') }}">{{ $item['status'] ?? '-' }}</span>
                            </td>
                            <td>
                                @php $pot = strtolower($item['potential'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $pot === 'high' ? 'aith-e-tag-high' : ($pot === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $item['potential'] ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- New Opportunities --}}
            @if(!empty($result['new_opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-sparkles"></i> New Opportunities</div>
                @foreach($result['new_opportunities'] as $opp)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $opp['stream_name'] ?? $opp['name'] ?? '' }}</span>
                        @if(isset($opp['difficulty']))
                        @php $diff = strtolower($opp['difficulty']); @endphp
                        <span class="aith-e-tag {{ $diff === 'easy' ? 'aith-e-tag-high' : ($diff === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $opp['difficulty'] }}</span>
                        @endif
                    </div>
                    @if(isset($opp['description']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.5rem;">{{ $opp['description'] }}</div>
                    @endif
                    <div style="display:flex;gap:1rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                        @if(isset($opp['monthly_potential']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Monthly Potential</span>
                            <div style="font-size:0.875rem;color:#3b82f6;font-weight:600;">{{ $opp['monthly_potential'] }}</div>
                        </div>
                        @endif
                        @if(isset($opp['time_to_revenue']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Time to Revenue</span>
                            <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);">{{ $opp['time_to_revenue'] }}</div>
                        </div>
                        @endif
                    </div>
                    @if(!empty($opp['action_steps']))
                    <ul class="aith-e-list" style="margin-top:0.375rem;">
                        @foreach($opp['action_steps'] as $step)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $step }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Priority Roadmap --}}
            @if(!empty($result['priority_roadmap']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-road"></i> Priority Roadmap</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Month</th><th>Action</th><th>Expected Result</th></tr></thead>
                        <tbody>
                        @foreach($result['priority_roadmap'] as $item)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $item['month'] ?? '' }}</td>
                            <td>{{ $item['action'] ?? '' }}</td>
                            <td>{{ $item['expected_result'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Total Potential Increase --}}
            @if(isset($result['total_potential_increase']))
            <div class="aith-e-section-card" style="text-align:center;">
                <div class="aith-e-section-card-title"><i class="fa-light fa-arrow-up-right-dots"></i> Total Potential Increase</div>
                <div style="font-size:2rem;font-weight:700;color:#3b82f6;margin:0.75rem 0;">{{ $result['total_potential_increase'] }}</div>
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
