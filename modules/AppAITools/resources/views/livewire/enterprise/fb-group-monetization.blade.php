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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#3b82f6,#4f46e5);">
                    <i class="fa-light fa-users-gear" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Group Monetization Planner</h2>
                    <p>Turn Facebook Groups into revenue engines</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">Facebook Group URL</label>
                <input type="text" wire:model="groupUrl" class="aith-input"
                       placeholder="https://facebook.com/groups/...">
                @error('groupUrl') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Member Count (optional)</label>
                <input type="text" wire:model="memberCount" class="aith-input"
                       placeholder="e.g. 5K, 50K, 500K">
                @error('memberCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. fitness, entrepreneurship, parenting">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-users-gear"></i> Analyze Group
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
                <div class="aith-e-loading-title">Analyzing group monetization...</div>
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
                <span class="aith-e-result-title">Group Monetization Plan</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-group-monetization', 'Group-Monetization-Plan')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-group-monetization">

            {{-- Score --}}
            @php $score = $result['group_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Group Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent monetization potential - multiple revenue streams available
                        @elseif($score >= 50) Good potential - focus on engagement and community building
                        @else Below average - grow membership and engagement before monetizing
                        @endif
                    </div>
                </div>
            </div>

            {{-- Group Assessment --}}
            @if(isset($result['group_assessment']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-pie"></i> Group Assessment</div>
                <div class="aith-e-grid-3">
                    @if(isset($result['group_assessment']['niche']))
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Niche</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;font-size:0.85rem;">{{ $result['group_assessment']['niche'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['group_assessment']['type']))
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Type</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;font-size:0.85rem;">{{ $result['group_assessment']['type'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['group_assessment']['member_count']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Members</div>
                        <div class="aith-e-summary-value" style="color:#86efac;font-size:0.85rem;">{{ $result['group_assessment']['member_count'] }}</div>
                    </div>
                    @endif
                </div>
                <div class="aith-e-grid-2" style="margin-top:0.75rem;">
                    @if(isset($result['group_assessment']['engagement_level']))
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Engagement Level</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;font-size:0.85rem;">{{ $result['group_assessment']['engagement_level'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['group_assessment']['growth_rate']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Growth Rate</div>
                        <div class="aith-e-summary-value" style="color:#86efac;font-size:0.85rem;">{{ $result['group_assessment']['growth_rate'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Monetization Models --}}
            @if(!empty($result['monetization_models']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-money-check-dollar"></i> Monetization Models</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Model</th><th>Est. Monthly</th><th>Difficulty</th><th>Time to Revenue</th><th>Requirements</th></tr></thead>
                        <tbody>
                        @foreach($result['monetization_models'] as $model)
                        <tr>
                            <td style="font-weight:600;color:#3b82f6;">{{ $model['model'] ?? '' }}</td>
                            <td style="font-weight:600;color:#86efac;">{{ $model['estimated_monthly'] ?? '-' }}</td>
                            <td>
                                @php $diff = strtolower($model['difficulty'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $diff === 'easy' || $diff === 'low' ? 'aith-e-tag-high' : ($diff === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $model['difficulty'] ?? '' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $model['time_to_revenue'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $model['requirements'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Engagement Tactics --}}
            @if(!empty($result['engagement_tactics']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-comments"></i> Engagement Tactics</div>
                <div class="aith-e-grid-2">
                @foreach($result['engagement_tactics'] as $tactic)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $tactic['tactic'] ?? '' }}</span>
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.375rem;">
                        @if(isset($tactic['frequency']))
                        <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(59,130,246,0.1);color:#3b82f6;">{{ $tactic['frequency'] }}</span>
                        @endif
                        @if(isset($tactic['expected_impact']))
                        @php $impact = strtolower($tactic['expected_impact']); @endphp
                        <span class="aith-e-tag {{ $impact === 'high' ? 'aith-e-tag-high' : ($impact === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $tactic['expected_impact'] }} impact</span>
                        @endif
                    </div>
                    @if(isset($tactic['implementation']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $tactic['implementation'] }}</div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Subscription Strategy --}}
            @if(isset($result['subscription_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-crown"></i> Subscription Strategy</div>
                @if(!empty($result['subscription_strategy']['price_tiers']))
                <div style="overflow-x:auto;margin-bottom:0.75rem;">
                    <table class="aith-e-table">
                        <thead><tr><th>Tier</th><th>Price</th><th>Perks</th><th>Target Conversion</th></tr></thead>
                        <tbody>
                        @foreach($result['subscription_strategy']['price_tiers'] as $tier)
                        <tr>
                            <td style="font-weight:600;color:#4f46e5;">{{ $tier['tier'] ?? '' }}</td>
                            <td style="font-weight:600;color:#86efac;">{{ $tier['price'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $tier['perks'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $tier['target_conversion'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                @if(isset($result['subscription_strategy']['free_vs_paid_ratio']))
                <div style="padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;margin-top:0.5rem;font-size:0.8rem;color:rgba(255,255,255,0.5);">
                    <strong style="color:rgba(255,255,255,0.6);">Free vs Paid Ratio:</strong> {{ $result['subscription_strategy']['free_vs_paid_ratio'] }}
                </div>
                @endif
                @if(isset($result['subscription_strategy']['launch_strategy']))
                <div style="padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;margin-top:0.375rem;font-size:0.8rem;color:rgba(255,255,255,0.5);">
                    <strong style="color:rgba(255,255,255,0.6);">Launch Strategy:</strong> {{ $result['subscription_strategy']['launch_strategy'] }}
                </div>
                @endif
            </div>
            @endif

            {{-- Revenue Roadmap --}}
            @if(!empty($result['revenue_roadmap']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-road"></i> Revenue Roadmap</div>
                @foreach($result['revenue_roadmap'] as $milestone)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.625rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div>
                        <span style="font-weight:600;color:#3b82f6;font-size:0.875rem;">{{ $milestone['month'] ?? '' }}</span>
                        @if(isset($milestone['action']))
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-top:0.125rem;">{{ $milestone['action'] }}</div>
                        @endif
                    </div>
                    <div style="text-align:right;">
                        @if(isset($milestone['expected_revenue']))
                        <span style="font-weight:600;color:#86efac;font-size:0.875rem;">{{ $milestone['expected_revenue'] }}</span>
                        @endif
                        @if(isset($milestone['milestone']))
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.35);margin-top:0.125rem;">{{ $milestone['milestone'] }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-group-monetization.next_steps', []);
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
