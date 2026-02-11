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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#8b5cf6,#06b6d4);">
                    <i class="fa-light fa-chart-network" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Cross-Platform Audience Arbitrage</h2>
                    <p>Find content gaps between YouTube and TikTok</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">YouTube Channel URL</label>
                <input type="url" wire:model="youtubeChannel" class="aith-input"
                       placeholder="https://youtube.com/@channel">
                @error('youtubeChannel') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">TikTok Niche Focus (optional)</label>
                <input type="text" wire:model="tiktokNiche" class="aith-input"
                       placeholder="e.g. fitness, tech reviews, cooking">
                @error('tiktokNiche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-chart-network"></i> Find Arbitrage Opportunities
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Analyzing...
                </span>
                <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">4 credits</span>
            </button>
            @endif

            @if($isLoading)
            {{-- Loading Steps --}}
            <div class="aith-e-loading" x-data="{ step: 0 }" x-init="
                let steps = {{ count($loadingSteps) }};
                let interval = setInterval(() => { if(step < steps - 1) step++; }, 2500);
                $wire.on('loadingComplete', () => clearInterval(interval));
            ">
                <div class="aith-e-loading-title">Analyzing cross-platform opportunities...</div>
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
                <span class="aith-e-result-title">Arbitrage Analysis Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-yt-arbitrage', 'YT-TikTok-Arbitrage')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-yt-arbitrage">

            {{-- YouTube Insights Card --}}
            @if(isset($result['youtube_insights']))
            @php $yt = $result['youtube_insights']; @endphp
            <div class="aith-e-section-card" style="border-left:3px solid #FF0000;">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                    <i class="fa-brands fa-youtube" style="color:#FF0000;font-size:1rem;"></i>
                    <span style="font-weight:700;color:#fff;font-size:0.9rem;">YouTube Channel Insights</span>
                    <span style="margin-left:auto;font-size:0.65rem;padding:0.125rem 0.5rem;border-radius:9999px;background:rgba(255,0,0,0.15);color:#ff6b6b;">REAL DATA</span>
                </div>
                <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
                    @if(!empty($yt['thumbnail']))
                    <img src="{{ $yt['thumbnail'] }}" alt="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    @endif
                    <div style="flex:1;min-width:200px;">
                        <div style="font-weight:600;color:#fff;font-size:0.9rem;margin-bottom:0.5rem;">{{ $yt['title'] ?? '' }}</div>
                        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-users" style="margin-right:0.25rem;"></i> {{ $yt['subscribers'] ?? '0' }} subs</span>
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-eye" style="margin-right:0.25rem;"></i> {{ $yt['total_views'] ?? '0' }} views</span>
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-chart-line" style="margin-right:0.25rem;"></i> {{ $yt['avg_views'] ?? '0' }} avg</span>
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-heart" style="margin-right:0.25rem;"></i> {{ $yt['engagement_rate'] ?? '0%' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Score --}}
            @php $score = $result['arbitrage_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Arbitrage Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Massive untapped TikTok potential from YouTube content
                        @elseif($score >= 50) Good cross-platform opportunities available
                        @else Limited arbitrage - niche may already be saturated on TikTok
                        @endif
                    </div>
                </div>
            </div>

            {{-- YouTube Overview --}}
            @if(isset($result['youtube_overview']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-brands fa-youtube"></i> YouTube Overview</div>
                <div class="aith-e-grid-3">
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Subscribers</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;font-size:0.9rem;">{{ $result['youtube_overview']['subscribers'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Avg Views</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;font-size:0.9rem;">{{ $result['youtube_overview']['avg_views'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Posting Freq</div>
                        <div class="aith-e-summary-value" style="color:#86efac;font-size:0.9rem;">{{ $result['youtube_overview']['posting_frequency'] ?? '-' }}</div>
                    </div>
                </div>
                @if(!empty($result['youtube_overview']['top_content_themes']))
                <div style="margin-top:0.75rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Top Content Themes</div>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['youtube_overview']['top_content_themes'] as $theme)
                        <span class="aith-e-pill aith-e-pill-green">{{ $theme }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            {{-- Content Gaps Table --}}
            @if(!empty($result['content_gaps']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-magnifying-glass-chart"></i> Content Gaps</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Topic</th><th>YouTube Performance</th><th>TikTok Saturation</th><th>Opportunity</th><th>Reasoning</th></tr></thead>
                        <tbody>
                        @foreach($result['content_gaps'] as $gap)
                        <tr>
                            <td style="font-weight:600;color:#8b5cf6;">{{ $gap['topic'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $gap['youtube_performance'] ?? '' }}</td>
                            <td>
                                @php $sat = strtolower($gap['tiktok_saturation'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $sat === 'low' ? 'aith-e-tag-high' : ($sat === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $gap['tiktok_saturation'] ?? '-' }}</span>
                            </td>
                            <td>
                                @php $opp = strtolower($gap['opportunity_level'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $opp === 'high' ? 'aith-e-tag-high' : ($opp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $gap['opportunity_level'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $gap['reasoning'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- First-Mover Opportunities --}}
            @if(!empty($result['first_mover_opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-rocket"></i> First-Mover Opportunities</div>
                <div class="aith-e-grid-2">
                @foreach($result['first_mover_opportunities'] as $opp)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $opp['idea'] ?? '' }}</span>
                        @if(isset($opp['urgency']))
                        @php $urg = strtolower($opp['urgency']); @endphp
                        <span class="aith-e-tag {{ $urg === 'high' ? 'aith-e-tag-low' : ($urg === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-high') }}">{{ $opp['urgency'] }} urgency</span>
                        @endif
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.375rem;">
                        @if(isset($opp['format']))
                        <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(139,92,246,0.1);color:#c4b5fd;">{{ $opp['format'] }}</span>
                        @endif
                        @if(isset($opp['estimated_reach']))
                        <span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(34,197,94,0.1);color:#86efac;">{{ $opp['estimated_reach'] }}</span>
                        @endif
                    </div>
                    @if(isset($opp['reference_video']))
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">
                        <strong style="color:rgba(255,255,255,0.5);">Reference:</strong> {{ $opp['reference_video'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Audience Overlap --}}
            @if(isset($result['audience_overlap']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-circle-nodes"></i> Audience Overlap</div>
                <div class="aith-e-grid-2">
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Estimated Overlap</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;font-size:0.9rem;">{{ $result['audience_overlap']['estimated_overlap'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">TikTok Growth Potential</div>
                        <div class="aith-e-summary-value" style="color:#86efac;font-size:0.9rem;">{{ $result['audience_overlap']['tiktok_growth_potential'] ?? '-' }}</div>
                    </div>
                </div>
                @if(isset($result['audience_overlap']['unique_youtube_audience']))
                <div style="padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;margin-top:0.75rem;font-size:0.8rem;color:rgba(255,255,255,0.5);">
                    <strong style="color:rgba(255,255,255,0.6);">Unique YouTube Audience:</strong> {{ $result['audience_overlap']['unique_youtube_audience'] }}
                </div>
                @endif
                @if(isset($result['audience_overlap']['demographic_shift']))
                <div style="padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:0.375rem;margin-top:0.375rem;font-size:0.8rem;color:rgba(255,255,255,0.5);">
                    <strong style="color:rgba(255,255,255,0.6);">Demographic Shift:</strong> {{ $result['audience_overlap']['demographic_shift'] }}
                </div>
                @endif
            </div>
            @endif

            {{-- Cross-Platform Strategy --}}
            @if(isset($result['cross_platform_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-sitemap"></i> Cross-Platform Strategy</div>
                @if(!empty($result['cross_platform_strategy']['content_pillars']))
                <div style="margin-bottom:0.75rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.25rem;font-weight:600;">Content Pillars</div>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['cross_platform_strategy']['content_pillars'] as $pillar)
                        <span class="aith-e-pill aith-e-pill-green">{{ $pillar }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                <div class="aith-e-grid-3">
                    @if(isset($result['cross_platform_strategy']['posting_cadence']))
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Posting Cadence</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;font-size:0.85rem;">{{ $result['cross_platform_strategy']['posting_cadence'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['cross_platform_strategy']['repurpose_ratio']))
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Repurpose Ratio</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;font-size:0.85rem;">{{ $result['cross_platform_strategy']['repurpose_ratio'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['cross_platform_strategy']['growth_timeline']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Growth Timeline</div>
                        <div class="aith-e-summary-value" style="color:#86efac;font-size:0.85rem;">{{ $result['cross_platform_strategy']['growth_timeline'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Quick Wins --}}
            @if(!empty($result['quick_wins']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bolt"></i> Quick Wins</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Action</th><th>Expected Result</th><th>Effort</th><th>Timeframe</th></tr></thead>
                        <tbody>
                        @foreach($result['quick_wins'] as $win)
                        <tr>
                            <td style="font-weight:600;color:#8b5cf6;">{{ $win['action'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.6);">{{ $win['expected_result'] ?? '' }}</td>
                            <td>
                                @php $eff = strtolower($win['effort'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $eff === 'low' ? 'aith-e-tag-high' : 'aith-e-tag-medium' }}">{{ $win['effort'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $win['timeframe'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.tiktok-yt-arbitrage.next_steps', []);
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
