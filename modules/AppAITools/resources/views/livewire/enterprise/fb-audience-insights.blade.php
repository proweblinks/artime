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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fa-light fa-user-magnifying-glass" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Audience Insights Analyzer</h2>
                    <p>Deep-dive into audience demographics and behavior</p>
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
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. fitness, food, fashion, travel">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Follower Count (optional)</label>
                <input type="text" wire:model="followerCount" class="aith-input"
                       placeholder="e.g. 10K, 100K, 1M">
                @error('followerCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            @include('appaitools::livewire.enterprise._youtube-connect', ['youtubeField' => 'youtubeChannel'])
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-user-magnifying-glass"></i> Analyze Audience
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
                <div class="aith-e-loading-title">Analyzing audience insights...</div>
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
                <span class="aith-e-result-title">Audience Insights Report</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-audience-insights', 'Audience-Insights')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-audience-insights">

            {{-- Score --}}
            @php $score = $result['audience_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Audience Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent audience quality - highly engaged and targetable
                        @elseif($score >= 50) Good audience base with growth and targeting potential
                        @else Audience needs development - focus on niche targeting
                        @endif
                    </div>
                </div>
            </div>

            {{-- Demographic Segments --}}
            @if(!empty($result['demographic_segments']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-users"></i> Demographic Segments</div>
                <div class="aith-e-grid-2">
                @foreach($result['demographic_segments'] as $seg)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.9rem;">{{ $seg['title'] ?? $seg['segment'] ?? '' }}</span>
                        @if(isset($seg['percentage']))
                        <span style="font-size:1rem;font-weight:700;color:#818cf8;">{{ $seg['percentage'] }}</span>
                        @endif
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.375rem;margin-bottom:0.5rem;">
                        @if(isset($seg['age_range']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Age Range</span>
                            <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $seg['age_range'] }}</div>
                        </div>
                        @endif
                        @if(isset($seg['gender_split']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Gender Split</span>
                            <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $seg['gender_split'] }}</div>
                        </div>
                        @endif
                        @if(isset($seg['location']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Location</span>
                            <div style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $seg['location'] }}</div>
                        </div>
                        @endif
                        @if(isset($seg['spending_power']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Spending Power</span>
                            @php $sp = strtolower($seg['spending_power']); @endphp
                            <span class="aith-e-tag {{ $sp === 'high' ? 'aith-e-tag-high' : ($sp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $seg['spending_power'] }}</span>
                        </div>
                        @endif
                    </div>
                    @if(!empty($seg['interests']))
                    <div>
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Interests</span>
                        <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                            @foreach($seg['interests'] as $interest)
                            <span class="aith-e-pill aith-e-pill-green">{{ $interest }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Behavior Patterns --}}
            @if(isset($result['behavior_patterns']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-brain-circuit"></i> Behavior Patterns</div>
                @if(!empty($result['behavior_patterns']['active_hours']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Active Hours</span>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach((array)$result['behavior_patterns']['active_hours'] as $hour)
                        <span style="font-size:0.8rem;color:rgba(255,255,255,0.7);background:rgba(99,102,241,0.1);padding:0.125rem 0.5rem;border-radius:0.25rem;">{{ $hour }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(isset($result['behavior_patterns']['device_split']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Device Split</span>
                    <div style="display:flex;gap:1rem;align-items:center;">
                        @if(isset($result['behavior_patterns']['device_split']['mobile']))
                        <div style="flex:1;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:0.25rem;">
                                <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-mobile" style="margin-right:0.25rem;"></i> Mobile</span>
                                <span style="font-size:0.8rem;color:#818cf8;font-weight:600;">{{ $result['behavior_patterns']['device_split']['mobile'] }}</span>
                            </div>
                            @php $mobilePct = intval(preg_replace('/[^0-9]/', '', $result['behavior_patterns']['device_split']['mobile'])); @endphp
                            <div class="aith-e-progress-inline">
                                <div class="aith-e-progress-inline-fill" style="width:{{ min($mobilePct, 100) }}%;background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
                            </div>
                        </div>
                        @endif
                        @if(isset($result['behavior_patterns']['device_split']['desktop']))
                        <div style="flex:1;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:0.25rem;">
                                <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-desktop" style="margin-right:0.25rem;"></i> Desktop</span>
                                <span style="font-size:0.8rem;color:#a78bfa;font-weight:600;">{{ $result['behavior_patterns']['device_split']['desktop'] }}</span>
                            </div>
                            @php $desktopPct = intval(preg_replace('/[^0-9]/', '', $result['behavior_patterns']['device_split']['desktop'])); @endphp
                            <div class="aith-e-progress-inline">
                                <div class="aith-e-progress-inline-fill" style="width:{{ min($desktopPct, 100) }}%;background:linear-gradient(90deg,#8b5cf6,#a78bfa);"></div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                @if(!empty($result['behavior_patterns']['content_preferences']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Content Preferences</span>
                    <ul class="aith-e-list">
                        @foreach($result['behavior_patterns']['content_preferences'] as $pref)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $pref }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(isset($result['behavior_patterns']['engagement_style']))
                <div>
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Engagement Style</span>
                    <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);">{{ $result['behavior_patterns']['engagement_style'] }}</div>
                </div>
                @endif
            </div>
            @endif

            {{-- Spending Analysis --}}
            @if(isset($result['spending_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-wallet"></i> Spending Analysis</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['spending_analysis']['avg_disposable_income']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Avg Disposable Income</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['spending_analysis']['avg_disposable_income'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['spending_analysis']['price_sensitivity']))
                    <div class="aith-e-summary-card aith-e-summary-card-orange">
                        <div class="aith-e-summary-label">Price Sensitivity</div>
                        <div class="aith-e-summary-value" style="color:#fdba74;">{{ $result['spending_analysis']['price_sensitivity'] }}</div>
                    </div>
                    @endif
                </div>
                <div class="aith-e-grid-2" style="margin-top:0.5rem;">
                    @if(!empty($result['spending_analysis']['top_purchase_categories']))
                    <div class="aith-e-section-card" style="margin-bottom:0;">
                        <div style="font-weight:600;color:#fff;font-size:0.85rem;margin-bottom:0.375rem;">Top Purchase Categories</div>
                        <ul class="aith-e-list">
                            @foreach($result['spending_analysis']['top_purchase_categories'] as $cat)
                            <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $cat }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    @if(isset($result['spending_analysis']['impulse_buy_likelihood']))
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Impulse Buy Likelihood</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['spending_analysis']['impulse_buy_likelihood'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Lookalike Opportunities --}}
            @if(!empty($result['lookalike_opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-people-group"></i> Lookalike Opportunities</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Audience Type</th><th>Size</th><th>Overlap</th><th>Targeting Suggestion</th></tr></thead>
                        <tbody>
                        @foreach($result['lookalike_opportunities'] as $lookalike)
                        <tr>
                            <td style="font-weight:600;color:#818cf8;">{{ $lookalike['audience_type'] ?? '' }}</td>
                            <td style="color:rgba(255,255,255,0.7);">{{ $lookalike['size'] ?? '-' }}</td>
                            <td>
                                @php $overlap = strtolower($lookalike['overlap'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $overlap === 'high' ? 'aith-e-tag-high' : ($overlap === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $lookalike['overlap'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $lookalike['targeting_suggestion'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Targeting Strategy --}}
            @if(isset($result['targeting_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bullseye-arrow"></i> Targeting Strategy</div>
                @if(isset($result['targeting_strategy']['core_audience']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Core Audience</span>
                    <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">{{ $result['targeting_strategy']['core_audience'] }}</div>
                </div>
                @endif
                @if(!empty($result['targeting_strategy']['expansion_targets']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Expansion Targets</span>
                    <ul class="aith-e-list">
                        @foreach($result['targeting_strategy']['expansion_targets'] as $target)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $target }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(!empty($result['targeting_strategy']['exclusions']))
                <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Exclusions</span>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['targeting_strategy']['exclusions'] as $exclusion)
                        <span style="display:inline-flex;align-items:center;padding:0.125rem 0.5rem;border-radius:9999px;font-size:0.75rem;background:rgba(239,68,68,0.1);color:#fca5a5;">{{ $exclusion }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($result['targeting_strategy']['ad_recommendations']))
                <div>
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;">Ad Recommendations</span>
                    <ul class="aith-e-list">
                        @foreach($result['targeting_strategy']['ad_recommendations'] as $rec)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $rec }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-audience-insights.next_steps', []);
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
