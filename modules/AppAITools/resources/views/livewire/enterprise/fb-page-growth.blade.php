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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#06b6d4,#3b82f6);">
                    <i class="fa-light fa-chart-line-up" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Page Growth Analyzer</h2>
                    <p>Analyze page performance and growth opportunities</p>
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
                <label class="aith-label">Follower Count (optional)</label>
                <input type="text" wire:model="followerCount" class="aith-input"
                       placeholder="e.g. 10K, 100K, 1M">
                @error('followerCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. tech, fitness, cooking, entertainment">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            @include('appaitools::livewire.enterprise._youtube-connect', ['youtubeField' => 'youtubeChannel'])
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-chart-line-up"></i> Analyze Growth
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
                <div class="aith-e-loading-title">Analyzing page growth...</div>
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
                <span class="aith-e-result-title">Page Growth Analysis</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-fb-page-growth', 'Page-Growth-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-fb-page-growth">

            {{-- Score --}}
            @php $score = $result['growth_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Growth Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent growth trajectory - strong momentum and engagement
                        @elseif($score >= 50) Good growth potential - optimization can accelerate results
                        @else Significant growth opportunities available - focus on strategy improvements
                        @endif
                    </div>
                </div>
            </div>

            {{-- Page Assessment --}}
            @if(isset($result['page_assessment']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-pie"></i> Page Assessment</div>
                <div class="aith-e-grid-3">
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Niche</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['page_assessment']['niche'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Followers</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['page_assessment']['followers'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Posting Frequency</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['page_assessment']['posting_frequency'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="aith-e-grid-2" style="margin-top:0.5rem;">
                    <div class="aith-e-summary-card aith-e-summary-card-orange">
                        <div class="aith-e-summary-label">Engagement Rate</div>
                        <div class="aith-e-summary-value" style="color:#fdba74;">{{ $result['page_assessment']['engagement_rate'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Content Mix</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['page_assessment']['content_mix'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Content Performance --}}
            @if(isset($result['content_performance']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-fire"></i> Content Performance</div>
                <div class="aith-e-grid-3">
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Top Format</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['content_performance']['top_format'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Avg Reach</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['content_performance']['avg_reach'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Avg Engagement</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['content_performance']['avg_engagement'] ?? '-' }}</div>
                    </div>
                </div>
                @if(isset($result['content_performance']['best_performing']))
                <div style="margin-top:0.75rem;padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Best Performing</span>
                    <div style="font-size:0.85rem;color:#86efac;">{{ $result['content_performance']['best_performing'] }}</div>
                </div>
                @endif
                @if(isset($result['content_performance']['underperforming']))
                <div style="margin-top:0.5rem;padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;">
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Underperforming</span>
                    <div style="font-size:0.85rem;color:#fca5a5;">{{ $result['content_performance']['underperforming'] }}</div>
                </div>
                @endif
            </div>
            @endif

            {{-- Growth Opportunities --}}
            @if(!empty($result['growth_opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-rocket"></i> Growth Opportunities</div>
                <div class="aith-e-grid-2">
                @foreach($result['growth_opportunities'] as $opp)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#22d3ee;font-size:0.9rem;">{{ $opp['title'] ?? $opp['opportunity'] ?? '' }}</span>
                    </div>
                    <div style="display:flex;gap:0.375rem;flex-wrap:wrap;margin-bottom:0.375rem;">
                        @if(isset($opp['potential_followers']))
                        <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(34,211,238,0.1);color:#22d3ee;">+{{ $opp['potential_followers'] }}</span>
                        @endif
                        @if(isset($opp['difficulty']))
                        @php $diff = strtolower($opp['difficulty'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $diff === 'easy' || $diff === 'low' ? 'aith-e-tag-high' : ($diff === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $opp['difficulty'] }}</span>
                        @endif
                        @if(isset($opp['priority']))
                        @php $priority = strtolower($opp['priority'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $priority === 'high' ? 'aith-e-tag-high' : ($priority === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $opp['priority'] }} priority</span>
                        @endif
                    </div>
                    @if(!empty($opp['action_steps']))
                    <div style="margin-top:0.375rem;">
                        @foreach($opp['action_steps'] as $action)
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.5);padding:0.125rem 0;">
                            <i class="fa-solid fa-circle" style="font-size:0.25rem;margin-right:0.375rem;vertical-align:middle;"></i>{{ $action }}
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Competitor Benchmarks --}}
            @if(!empty($result['competitor_benchmarks']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-scale-balanced"></i> Competitor Benchmarks</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Page</th><th>Followers</th><th>Engagement</th><th>Content Strategy</th><th>Advantage</th></tr></thead>
                        <tbody>
                        @foreach($result['competitor_benchmarks'] as $comp)
                        <tr>
                            <td style="font-weight:600;color:#22d3ee;">{{ $comp['page'] ?? '' }}</td>
                            <td style="font-size:0.85rem;color:#fff;">{{ $comp['followers'] ?? '-' }}</td>
                            <td style="font-size:0.85rem;color:#86efac;">{{ $comp['engagement'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $comp['content_strategy'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $comp['advantage'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Growth Roadmap --}}
            @if(!empty($result['growth_roadmap']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-road"></i> Growth Roadmap</div>
                @foreach($result['growth_roadmap'] as $phase)
                <div style="display:flex;gap:1rem;padding:0.75rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="flex-shrink:0;width:80px;">
                        <span style="font-weight:700;color:#22d3ee;font-size:0.9rem;">{{ $phase['month'] ?? '' }}</span>
                        @if(isset($phase['target_followers']))
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-top:0.125rem;">{{ $phase['target_followers'] }}</div>
                        @endif
                    </div>
                    <div style="flex:1;">
                        @if(!empty($phase['actions']))
                        @foreach($phase['actions'] as $action)
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);padding:0.125rem 0;">
                            <i class="fa-solid fa-circle" style="font-size:0.25rem;margin-right:0.375rem;vertical-align:middle;"></i>{{ $action }}
                        </div>
                        @endforeach
                        @endif
                        @if(isset($phase['milestone']))
                        <div style="font-size:0.8rem;color:#86efac;margin-top:0.25rem;font-weight:500;">
                            <i class="fa-light fa-flag-checkered" style="margin-right:0.25rem;"></i>{{ $phase['milestone'] }}
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.fb-page-growth.next_steps', []);
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
