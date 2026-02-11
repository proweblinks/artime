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
                    <i class="fa-light fa-sack-dollar" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Creator Fund Calculator</h2>
                    <p>Estimate earnings and optimize for creator fund payouts</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">TikTok Profile</label>
                <input type="text" wire:model="profile" class="aith-input"
                       placeholder="@username">
                @error('profile') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Average Views (optional)</label>
                <input type="text" wire:model="avgViews" class="aith-input"
                       placeholder="e.g. 10K, 50K, 100K">
                @error('avgViews') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Follower Count (optional)</label>
                <input type="text" wire:model="followerCount" class="aith-input"
                       placeholder="e.g. 50K, 500K, 1M">
                @error('followerCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-sack-dollar"></i> Calculate Earnings
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
                <div class="aith-e-loading-title">Calculating creator fund earnings...</div>
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
                <span class="aith-e-result-title">Creator Fund Analysis</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-creator-fund', 'Creator-Fund-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-creator-fund">

            {{-- Score --}}
            @php $score = $result['fund_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Fund Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent earning potential from creator fund programs
                        @elseif($score >= 50) Good earning potential - optimization can increase payouts
                        @else Below average earnings - focus on growing views and engagement
                        @endif
                    </div>
                </div>
            </div>

            {{-- Earnings Estimate --}}
            @if(isset($result['earnings_estimate']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-bill-trend-up"></i> Earnings Estimate</div>
                <div class="aith-e-grid-2">
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Daily</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['earnings_estimate']['daily'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Weekly</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['earnings_estimate']['weekly'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="aith-e-grid-2" style="margin-top:0.5rem;">
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Monthly</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['earnings_estimate']['monthly'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Yearly</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['earnings_estimate']['yearly'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Fund Breakdown --}}
            @if(isset($result['fund_breakdown']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-pie"></i> Fund Breakdown</div>
                <div class="aith-e-grid-3">
                    @foreach($result['fund_breakdown'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ is_array($val) ? implode(', ', $val) : $val }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Eligibility --}}
            @if(isset($result['eligibility']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-clipboard-check"></i> Eligibility Status</div>
                @if(isset($result['eligibility']['status']))
                <div style="margin-bottom:0.75rem;">
                    @php $eligStatus = strtolower($result['eligibility']['status']); @endphp
                    <span class="aith-e-tag {{ $eligStatus === 'eligible' ? 'aith-e-tag-high' : ($eligStatus === 'partial' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}" style="font-size:0.85rem;padding:0.375rem 0.75rem;">
                        {{ $result['eligibility']['status'] }}
                    </span>
                </div>
                @endif
                @if(!empty($result['eligibility']['requirements_met']))
                <div style="margin-bottom:0.5rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;margin-bottom:0.375rem;display:block;">Requirements Met</span>
                    @foreach($result['eligibility']['requirements_met'] as $req)
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0;font-size:0.85rem;">
                        <i class="fa-solid fa-circle-check" style="color:#22c55e;font-size:0.75rem;"></i>
                        <span style="color:rgba(255,255,255,0.7);">{{ $req }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
                @if(!empty($result['eligibility']['requirements_missing']))
                <div>
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;margin-bottom:0.375rem;display:block;">Requirements Missing</span>
                    @foreach($result['eligibility']['requirements_missing'] as $req)
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0;font-size:0.85rem;">
                        <i class="fa-solid fa-circle-xmark" style="color:#ef4444;font-size:0.75rem;"></i>
                        <span style="color:rgba(255,255,255,0.7);">{{ $req }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            {{-- Revenue Comparison --}}
            @if(!empty($result['revenue_comparison']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-scale-balanced"></i> Revenue Comparison</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Source</th><th>Est. Monthly</th><th>Difficulty</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($result['revenue_comparison'] as $rev)
                        <tr>
                            <td style="font-weight:600;color:#86efac;">{{ $rev['source'] ?? '' }}</td>
                            <td>{{ $rev['estimated_monthly'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $rev['difficulty'] ?? '-' }}</td>
                            <td>
                                @php $revStatus = strtolower($rev['status'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $revStatus === 'active' ? 'aith-e-tag-high' : ($revStatus === 'available' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $rev['status'] ?? '-' }}</span>
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
                <div class="aith-e-section-card-title"><i class="fa-light fa-rocket"></i> Optimization Tips</div>
                @foreach($result['optimization_tips'] as $tip)
                <div class="aith-e-section-card" style="margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $tip['tip'] ?? '' }}</span>
                        @if(isset($tip['effort']))
                        @php $effort = strtolower($tip['effort']); @endphp
                        <span class="aith-e-tag {{ $effort === 'low' ? 'aith-e-tag-high' : ($effort === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $tip['effort'] }} effort</span>
                        @endif
                    </div>
                    @if(isset($tip['potential_increase']))
                    <div style="font-size:0.8rem;color:#22c55e;">
                        <i class="fa-light fa-arrow-trend-up" style="font-size:0.7rem;"></i> Potential increase: {{ $tip['potential_increase'] }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Growth Milestones --}}
            @if(!empty($result['growth_milestones']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-flag-checkered"></i> Growth Milestones</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Followers</th><th>Est. Monthly</th><th>Unlock</th></tr></thead>
                        <tbody>
                        @foreach($result['growth_milestones'] as $milestone)
                        <tr>
                            <td style="font-weight:600;color:#86efac;">{{ $milestone['followers'] ?? '' }}</td>
                            <td>{{ $milestone['estimated_monthly'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $milestone['unlock'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
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
