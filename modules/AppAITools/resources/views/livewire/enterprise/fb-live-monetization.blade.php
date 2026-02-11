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
                    <i class="fa-light fa-signal-stream" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Live Monetization Planner</h2>
                    <p>Plan and monetize Facebook Live streams</p>
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
                <label class="aith-label">Content Type (optional)</label>
                <input type="text" wire:model="contentType" class="aith-input"
                       placeholder="e.g. Q&A, tutorial, gaming">
                @error('contentType') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Follower Count (optional)</label>
                <input type="text" wire:model="followerCount" class="aith-input"
                       placeholder="e.g. 10K, 100K, 1M">
                @error('followerCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-signal-stream"></i> Analyze Live Potential
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
                <div class="aith-e-loading-title">Analyzing live monetization...</div>
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
                <span class="aith-e-result-title">Live Monetization Plan</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-live-monetization', 'Live-Monetization')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-live-monetization">

            {{-- Score --}}
            @php $score = $result['live_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Live Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent live monetization potential - maximize your streams
                        @elseif($score >= 50) Good live earning potential - optimization can boost revenue
                        @else Below average live potential - focus on growing audience first
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stars Earnings --}}
            @if(isset($result['stars_earnings']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-stars"></i> Stars Earnings</div>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                    @if(isset($result['stars_earnings']['eligibility']))
                    @php $elig = strtolower($result['stars_earnings']['eligibility']); @endphp
                    <span class="aith-e-tag {{ $elig === 'eligible' ? 'aith-e-tag-high' : ($elig === 'partial' || $elig === 'near' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $result['stars_earnings']['eligibility'] }}</span>
                    @endif
                </div>
                <div class="aith-e-grid-3" style="margin-bottom:0.75rem;">
                    @if(isset($result['stars_earnings']['avg_stars_per_stream']))
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Avg Stars/Stream</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['stars_earnings']['avg_stars_per_stream'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['stars_earnings']['estimated_monthly']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Est. Monthly</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['stars_earnings']['estimated_monthly'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['stars_earnings']['top_earner_benchmark']))
                    <div class="aith-e-summary-card aith-e-summary-card-orange">
                        <div class="aith-e-summary-label">Top Earner Benchmark</div>
                        <div class="aith-e-summary-value" style="color:#fdba74;">{{ $result['stars_earnings']['top_earner_benchmark'] }}</div>
                    </div>
                    @endif
                </div>
                @if(!empty($result['stars_earnings']['boost_tactics']))
                <div>
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Boost Tactics</span>
                    <ul class="aith-e-list">
                        @foreach($result['stars_earnings']['boost_tactics'] as $tactic)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tactic }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif

            {{-- Subscription Tiers --}}
            @if(!empty($result['subscription_tiers']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-badge-dollar"></i> Subscription Tiers</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Tier</th><th>Price</th><th>Perks</th><th>Expected Subscribers</th><th>Monthly Revenue</th></tr></thead>
                        <tbody>
                        @foreach($result['subscription_tiers'] as $tier)
                        <tr>
                            <td style="font-weight:600;color:#f43f5e;">{{ $tier['tier'] ?? '' }}</td>
                            <td style="font-weight:600;color:#fff;">{{ $tier['price'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ is_array($tier['perks'] ?? null) ? implode(', ', $tier['perks']) : ($tier['perks'] ?? '-') }}</td>
                            <td style="color:rgba(255,255,255,0.7);">{{ $tier['expected_subscribers'] ?? '-' }}</td>
                            <td style="font-weight:600;color:#22c55e;">{{ $tier['monthly_revenue'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Engagement Techniques --}}
            @if(!empty($result['engagement_techniques']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-hand-sparkles"></i> Engagement Techniques</div>
                <div class="aith-e-grid-2">
                @foreach($result['engagement_techniques'] as $technique)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $technique['title'] ?? '' }}</span>
                        @if(isset($technique['type']))
                        <span class="aith-e-tag aith-e-tag-medium">{{ $technique['type'] }}</span>
                        @endif
                    </div>
                    @if(isset($technique['implementation']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.7);">Implementation:</strong> {{ $technique['implementation'] }}
                    </div>
                    @endif
                    @if(isset($technique['expected_impact']))
                    <div style="font-size:0.8rem;color:#ec4899;">
                        <strong style="color:rgba(255,255,255,0.7);">Expected Impact:</strong> {{ $technique['expected_impact'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Live Shopping Strategy --}}
            @if(isset($result['live_shopping_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-cart-shopping"></i> Live Shopping Strategy</div>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                    @if(isset($result['live_shopping_strategy']['eligible']))
                    @php $shopElig = strtolower($result['live_shopping_strategy']['eligible']); @endphp
                    <span class="aith-e-tag {{ $shopElig === 'yes' || $shopElig === 'eligible' ? 'aith-e-tag-high' : ($shopElig === 'partial' || $shopElig === 'near' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $result['live_shopping_strategy']['eligible'] }}</span>
                    @endif
                </div>
                @if(!empty($result['live_shopping_strategy']['product_showcase_tips']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Product Showcase Tips</span>
                    <ul class="aith-e-list">
                        @foreach($result['live_shopping_strategy']['product_showcase_tips'] as $tip)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(isset($result['live_shopping_strategy']['timing']))
                <div style="font-size:0.85rem;color:rgba(255,255,255,0.6);margin-bottom:0.75rem;">
                    <strong style="color:rgba(255,255,255,0.7);">Timing:</strong> {{ $result['live_shopping_strategy']['timing'] }}
                </div>
                @endif
                @if(!empty($result['live_shopping_strategy']['conversion_tactics']))
                <div>
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Conversion Tactics</span>
                    <ul class="aith-e-list">
                        @foreach($result['live_shopping_strategy']['conversion_tactics'] as $tactic)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tactic }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif

            {{-- Revenue Estimate --}}
            @if(isset($result['revenue_estimate']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-mixed"></i> Revenue Estimate</div>
                <div class="aith-e-grid-3" style="margin-bottom:0.75rem;">
                    @if(isset($result['revenue_estimate']['stars_monthly']))
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Stars Monthly</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['revenue_estimate']['stars_monthly'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['revenue_estimate']['subscriptions_monthly']))
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Subscriptions Monthly</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['revenue_estimate']['subscriptions_monthly'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['revenue_estimate']['live_shopping_monthly']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Live Shopping Monthly</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['revenue_estimate']['live_shopping_monthly'] }}</div>
                    </div>
                    @endif
                </div>
                @if(isset($result['revenue_estimate']['total_monthly']))
                <div class="aith-e-summary-card aith-e-summary-card-green" style="text-align:center;padding:1rem;">
                    <div class="aith-e-summary-label" style="font-size:0.85rem;">Total Estimated Monthly Revenue</div>
                    <div class="aith-e-summary-value" style="color:#22c55e;font-size:1.5rem;">{{ $result['revenue_estimate']['total_monthly'] }}</div>
                </div>
                @endif
                @if(isset($result['revenue_estimate']['growth_trajectory']))
                <div style="font-size:0.85rem;color:rgba(255,255,255,0.6);padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;margin-top:0.75rem;">
                    <strong style="color:rgba(255,255,255,0.7);">Growth Trajectory:</strong> {{ $result['revenue_estimate']['growth_trajectory'] }}
                </div>
                @endif
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-live-monetization.next_steps', []);
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
