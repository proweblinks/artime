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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#22c55e,#059669);">
                    <i class="fa-light fa-coins" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Monetization Analyzer</h2>
                    <p>Estimate channel earnings and optimize revenue streams</p>
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
                    <i class="fa-light fa-coins"></i> Analyze Revenue
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
                <div class="aith-e-loading-title">Analyzing monetization...</div>
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
                <span class="aith-e-result-title">Monetization Analysis Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-monetization-analyzer', 'Monetization-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-monetization-analyzer">

            {{-- Summary Cards --}}
            @if(isset($result['channel_overview']) || isset($result['total_monthly_estimate']))
            <div class="aith-e-grid-3" style="margin-bottom:1rem;">
                <div class="aith-e-summary-card aith-e-summary-card-green">
                    <div class="aith-e-summary-label">Monthly Estimate</div>
                    <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['total_monthly_estimate'] ?? 'N/A' }}</div>
                </div>
                <div class="aith-e-summary-card aith-e-summary-card-blue">
                    <div class="aith-e-summary-label">Yearly Estimate</div>
                    <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['total_monthly_estimate'] ?? 'N/A' }}</div>
                    <div class="aith-e-summary-sub">x12 monthly</div>
                </div>
                <div class="aith-e-summary-card aith-e-summary-card-purple">
                    <div class="aith-e-summary-label">Estimated CPM</div>
                    <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['channel_overview']['estimated_cpm'] ?? 'N/A' }}</div>
                </div>
            </div>
            @endif

            {{-- Score --}}
            @php $score = $result['monetization_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Monetization Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent monetization potential
                        @elseif($score >= 50) Good revenue opportunities available
                        @else Limited monetization - significant optimization needed
                        @endif
                    </div>
                </div>
            </div>

            {{-- Channel Overview --}}
            @if(isset($result['channel_overview']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-simple"></i> Channel Overview</div>
                <div class="aith-e-grid-2">
                    @foreach($result['channel_overview'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Revenue Breakdown --}}
            @if(!empty($result['revenue_breakdown']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-bill-trend-up"></i> Revenue Breakdown</div>
                @foreach($result['revenue_breakdown'] as $item)
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.625rem 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                    <span style="font-weight:600;color:#fff;flex:1;">{{ $item['stream'] ?? '' }}</span>
                    <span style="color:#22c55e;font-weight:600;font-size:0.875rem;">{{ $item['monthly_estimate'] ?? '-' }}</span>
                    <span class="aith-e-tag {{ strtolower($item['status'] ?? '') === 'active' ? 'aith-e-tag-active' : (strtolower($item['status'] ?? '') === 'underutilized' ? 'aith-e-tag-underutilized' : 'aith-e-tag-inactive') }}">{{ $item['status'] ?? '-' }}</span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Growth Opportunities --}}
            @if(!empty($result['growth_opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-arrow-trend-up"></i> Growth Opportunities</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Opportunity</th><th>Potential Revenue</th><th>Difficulty</th><th>Priority</th></tr></thead>
                        <tbody>
                        @foreach($result['growth_opportunities'] as $opp)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $opp['opportunity'] ?? '' }}</td>
                            <td>{{ $opp['potential_revenue'] ?? '-' }}</td>
                            <td>
                                @php $diff = strtolower($opp['difficulty'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $diff === 'easy' ? 'aith-e-tag-high' : ($diff === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $opp['difficulty'] ?? '-' }}</span>
                            </td>
                            <td>
                                @php $pri = strtolower($opp['priority'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $pri === 'high' ? 'aith-e-tag-high' : ($pri === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $opp['priority'] ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Optimization Tips --}}
            @if(!empty($result['optimization_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Optimization Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['optimization_tips'] as $tip)
                    <li><span class="bullet" style="font-size:0.85rem;">&#128161;</span> {{ $tip }}</li>
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
