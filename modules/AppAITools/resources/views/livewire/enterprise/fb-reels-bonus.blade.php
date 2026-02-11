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
                    <i class="fa-light fa-money-bill-trend-up" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Reels Play Bonus Optimizer</h2>
                    <p>Maximize Facebook Reels Play Bonus earnings</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Facebook Page URL</label>
                <input type="text" wire:model="pageUrl" class="aith-input"
                       placeholder="https://facebook.com/yourpage">
                @error('pageUrl') <span class="aith-e-field-error">{{ $message }}</span> @enderror
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
            @include('appaitools::livewire.enterprise._youtube-connect', ['youtubeField' => 'youtubeChannel'])
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-money-bill-trend-up"></i> Analyze Bonus Potential
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
                <div class="aith-e-loading-title">Analyzing Reels bonus potential...</div>
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
                <span class="aith-e-result-title">Reels Bonus Analysis</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-reels-bonus', 'Reels-Bonus-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-reels-bonus">

            {{-- Score --}}
            @php $score = $result['bonus_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Bonus Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent bonus earning potential - maximize your payouts
                        @elseif($score >= 50) Good potential - optimization can boost earnings significantly
                        @else Below average - focus on growing views and meeting requirements
                        @endif
                    </div>
                </div>
            </div>

            {{-- Earnings Tiers --}}
            @if(isset($result['earnings_tiers']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-layer-group"></i> Earnings Tiers</div>
                @if(isset($result['earnings_tiers']['current_tier']))
                <div class="aith-e-summary-card aith-e-summary-card-green" style="margin-bottom:0.75rem;">
                    <div class="aith-e-summary-label">Current Tier</div>
                    <div class="aith-e-summary-value" style="color:#86efac;font-size:1rem;">{{ $result['earnings_tiers']['current_tier'] }}</div>
                </div>
                @endif
                @if(!empty($result['earnings_tiers']['tier_breakdown']))
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Tier</th><th>Views Required</th><th>Payout/1000</th><th>Your Status</th></tr></thead>
                        <tbody>
                        @foreach($result['earnings_tiers']['tier_breakdown'] as $tier)
                        <tr>
                            <td style="font-weight:600;color:#22c55e;">{{ $tier['tier'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $tier['views_required'] ?? '' }}</td>
                            <td style="font-weight:600;color:#86efac;">{{ $tier['payout_per_1000'] ?? '' }}</td>
                            <td>
                                @php $status = strtolower($tier['your_status'] ?? ''); @endphp
                                <span class="aith-e-tag {{ str_contains($status, 'current') || str_contains($status, 'achieved') ? 'aith-e-tag-high' : (str_contains($status, 'near') || str_contains($status, 'close') ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $tier['your_status'] ?? '' }}</span>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endif

            {{-- Bonus Eligibility --}}
            @if(isset($result['bonus_eligibility']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-clipboard-check"></i> Bonus Eligibility</div>
                @if(isset($result['bonus_eligibility']['status']))
                @php $eligStatus = strtolower($result['bonus_eligibility']['status']); @endphp
                <div style="margin-bottom:0.75rem;">
                    <span class="aith-e-tag {{ str_contains($eligStatus, 'eligible') ? 'aith-e-tag-high' : (str_contains($eligStatus, 'partial') || str_contains($eligStatus, 'near') ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}" style="font-size:0.85rem;padding:0.25rem 0.75rem;">{{ $result['bonus_eligibility']['status'] }}</span>
                </div>
                @endif
                @if(!empty($result['bonus_eligibility']['requirements_met']))
                <div style="margin-bottom:0.5rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.375rem;font-weight:600;">Requirements Met</div>
                    @foreach($result['bonus_eligibility']['requirements_met'] as $req)
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0;font-size:0.85rem;color:#86efac;">
                        <i class="fa-solid fa-check" style="font-size:0.7rem;"></i> {{ $req }}
                    </div>
                    @endforeach
                </div>
                @endif
                @if(!empty($result['bonus_eligibility']['requirements_missing']))
                <div style="margin-bottom:0.5rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.375rem;font-weight:600;">Requirements Missing</div>
                    @foreach($result['bonus_eligibility']['requirements_missing'] as $req)
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0;font-size:0.85rem;color:#ef4444;">
                        <i class="fa-solid fa-xmark" style="font-size:0.7rem;"></i> {{ $req }}
                    </div>
                    @endforeach
                </div>
                @endif
                @if(isset($result['bonus_eligibility']['time_to_qualify']))
                <div style="padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;margin-top:0.5rem;font-size:0.8rem;color:rgba(255,255,255,0.5);">
                    <strong style="color:rgba(255,255,255,0.6);">Time to Qualify:</strong> {{ $result['bonus_eligibility']['time_to_qualify'] }}
                </div>
                @endif
            </div>
            @endif

            {{-- Content Strategies --}}
            @if(!empty($result['content_strategies']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Content Strategies</div>
                <div class="aith-e-grid-2">
                @foreach($result['content_strategies'] as $strategy)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $strategy['strategy'] ?? '' }}</span>
                        @if(isset($strategy['expected_impact']))
                        @php $impact = strtolower($strategy['expected_impact']); @endphp
                        <span class="aith-e-tag {{ $impact === 'high' ? 'aith-e-tag-high' : ($impact === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $strategy['expected_impact'] }}</span>
                        @endif
                    </div>
                    @if(isset($strategy['implementation']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">How:</strong> {{ $strategy['implementation'] }}
                    </div>
                    @endif
                    @if(isset($strategy['content_example']))
                    <div style="font-size:0.8rem;color:rgba(34,197,94,0.7);margin-top:0.25rem;">
                        <strong style="color:rgba(34,197,94,0.8);">Example:</strong> {{ $strategy['content_example'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Optimal Posting --}}
            @if(isset($result['optimal_posting']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-clock"></i> Optimal Posting</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['optimal_posting']['frequency']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Frequency</div>
                        <div class="aith-e-summary-value" style="color:#86efac;font-size:0.85rem;">{{ $result['optimal_posting']['frequency'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['optimal_posting']['duration_sweet_spot']))
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Duration Sweet Spot</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;font-size:0.85rem;">{{ $result['optimal_posting']['duration_sweet_spot'] }}</div>
                    </div>
                    @endif
                </div>
                @if(!empty($result['optimal_posting']['best_times']))
                <div style="margin-top:0.75rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.375rem;font-weight:600;">Best Posting Times</div>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['optimal_posting']['best_times'] as $time)
                        <span class="aith-e-pill aith-e-pill-green">{{ $time }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(isset($result['optimal_posting']['format_mix']))
                <div style="padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;margin-top:0.75rem;font-size:0.8rem;color:rgba(255,255,255,0.5);">
                    <strong style="color:rgba(255,255,255,0.6);">Format Mix:</strong> {{ $result['optimal_posting']['format_mix'] }}
                </div>
                @endif
            </div>
            @endif

            {{-- Growth Milestones --}}
            @if(!empty($result['growth_milestones']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-flag-checkered"></i> Growth Milestones</div>
                @foreach($result['growth_milestones'] as $milestone)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.625rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div>
                        <span style="font-weight:600;color:#86efac;font-size:0.875rem;">{{ $milestone['followers'] ?? '' }}</span>
                        @if(isset($milestone['monthly_bonus']))
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-top:0.125rem;">Est. {{ $milestone['monthly_bonus'] }}/mo</div>
                        @endif
                    </div>
                    <div style="text-align:right;">
                        @if(isset($milestone['unlock']))
                        <span style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $milestone['unlock'] }}</span>
                        @endif
                        @if(isset($milestone['timeline']))
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.35);margin-top:0.125rem;">{{ $milestone['timeline'] }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-reels-bonus.next_steps', []);
                $allTools = config('appaitools.enterprise_tools', []);
            @endphp
            @if(!empty($nextSteps))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-arrow-right"></i> What's Next?</div>
                <div class="aith-e-grid-2">
                    @foreach($nextSteps as $ns)
                    @php $nsTool = $allTools[$ns['tool']] ?? null; @endphp
                    @if($nsTool)
                    <a href="{{ route($nsTool['route']) }}" class="aith-e-section-card" style="margin-bottom:0;text-decoration:none;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.borderColor='rgba(139,92,246,0.3)'" onmouseout="this.style.borderColor=''">
                        <div style="font-weight:600;color:#c4b5fd;font-size:0.875rem;margin-bottom:0.25rem;">{{ $nsTool['name'] }}</div>
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">{{ $ns['reason'] }}</div>
                    </a>
                    @endif
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
