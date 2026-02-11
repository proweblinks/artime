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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#8b5cf6,#ec4899);">
                    <i class="fa-light fa-chart-mixed" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Cross-Platform IG Arbitrage</h2>
                    <p>Find content gaps between YouTube and Instagram</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            <div class="aith-form-group">
                <label class="aith-label">YouTube Channel URL</label>
                <input type="url" wire:model="youtubeChannel" class="aith-input"
                       placeholder="https://youtube.com/@channel">
                @error('youtubeChannel') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Instagram Niche Focus (optional)</label>
                <input type="text" wire:model="igNiche" class="aith-input"
                       placeholder="e.g. fitness, beauty, tech reviews">
                @error('igNiche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-chart-mixed"></i> Find Opportunities
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Analyzing...
                </span>
                <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">4 credits</span>
            </button>
            @endif

            @if($isLoading)
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
            <div class="aith-e-result-header">
                <span class="aith-e-result-title">IG Arbitrage Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-ig-yt-arbitrage', 'IG-Arbitrage')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-ig-yt-arbitrage">

            {{-- YouTube Insights Card --}}
            @if(isset($result['youtube_insights']))
            @php $yt = $result['youtube_insights']; @endphp
            <div class="aith-e-section-card" style="border-left:3px solid #FF0000;">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                    <i class="fa-brands fa-youtube" style="color:#FF0000;font-size:1rem;"></i>
                    <span style="font-weight:700;color:#fff;font-size:0.9rem;">YouTube Insights</span>
                    <span style="margin-left:auto;font-size:0.65rem;padding:0.125rem 0.5rem;border-radius:9999px;background:rgba(255,0,0,0.15);color:#ff6b6b;">REAL DATA</span>
                </div>
                <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
                    @if(!empty($yt['thumbnail']))
                    <img src="{{ $yt['thumbnail'] }}" alt="" style="width:120px;height:68px;border-radius:0.375rem;object-fit:cover;flex-shrink:0;">
                    @endif
                    <div style="flex:1;min-width:200px;">
                        <div style="font-weight:600;color:#fff;font-size:0.875rem;margin-bottom:0.375rem;">{{ $yt['title'] ?? '' }}</div>
                        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-users" style="margin-right:0.25rem;"></i> {{ $yt['subscribers'] ?? '0' }}</span>
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-eye" style="margin-right:0.25rem;"></i> {{ $yt['avg_views'] ?? '0' }} avg</span>
                            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><i class="fa-light fa-chart-line" style="margin-right:0.25rem;"></i> {{ $yt['engagement_rate'] ?? '0%' }}</span>
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
                        @if($score >= 80) Excellent cross-platform opportunity
                        @elseif($score >= 50) Good opportunities with strategic adaptation
                        @else Limited arbitrage potential — niche may be saturated
                        @endif
                    </div>
                </div>
            </div>

            {{-- Content Gaps --}}
            @if(!empty($result['content_gaps']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bullseye"></i> Content Gaps</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Topic</th><th>YT Performance</th><th>IG Saturation</th><th>Opportunity</th><th>Best Format</th></tr></thead>
                        <tbody>
                        @foreach($result['content_gaps'] as $gap)
                        <tr>
                            <td style="font-weight:600;color:#fff;">{{ $gap['topic'] ?? '' }}</td>
                            <td style="font-size:0.8rem;">{{ $gap['youtube_performance'] ?? '' }}</td>
                            <td>
                                @php $sat = strtolower($gap['ig_saturation'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $sat === 'low' ? 'aith-e-tag-high' : ($sat === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $gap['ig_saturation'] ?? '' }}</span>
                            </td>
                            <td>
                                @php $opp = strtolower($gap['opportunity_level'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $opp === 'high' ? 'aith-e-tag-high' : ($opp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $gap['opportunity_level'] ?? '' }}</span>
                            </td>
                            <td><span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(236,72,153,0.1);color:#ec4899;">{{ $gap['best_ig_format'] ?? '' }}</span></td>
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
                @foreach($result['first_mover_opportunities'] as $opp)
                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="flex-shrink:0;">
                        @php $urg = strtolower($opp['urgency'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $urg === 'high' ? 'aith-e-tag-high' : ($urg === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $opp['urgency'] ?? '' }}</span>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $opp['idea'] ?? '' }}</div>
                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.25rem;">
                            @if(isset($opp['format']))
                            <span style="font-size:0.75rem;color:#ec4899;">{{ $opp['format'] }}</span>
                            @endif
                            @if(isset($opp['estimated_reach']))
                            <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);">~{{ $opp['estimated_reach'] }} reach</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Audience Overlap --}}
            @if(isset($result['audience_overlap']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-users"></i> Audience Overlap</div>
                <div class="aith-e-grid-2">
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Estimated Overlap</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['audience_overlap']['estimated_overlap'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">IG Growth Potential</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['audience_overlap']['ig_growth_potential'] ?? '-' }}</div>
                    </div>
                </div>
                @if(isset($result['audience_overlap']['demographic_shift']))
                <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-top:0.75rem;">
                    <strong style="color:rgba(255,255,255,0.6);">Demographic Shift:</strong> {{ $result['audience_overlap']['demographic_shift'] }}
                </div>
                @endif
            </div>
            @endif

            {{-- Format Recommendations --}}
            @if(!empty($result['format_recommendations']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-wand-magic-sparkles"></i> Format Recommendations</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>YouTube Type</th><th>IG Format</th><th>Adaptation Tips</th></tr></thead>
                        <tbody>
                        @foreach($result['format_recommendations'] as $rec)
                        <tr>
                            <td style="font-size:0.85rem;color:rgba(255,255,255,0.6);">{{ $rec['youtube_type'] ?? '' }}</td>
                            <td><span style="font-size:0.75rem;padding:0.125rem 0.375rem;border-radius:0.25rem;background:rgba(236,72,153,0.1);color:#ec4899;">{{ $rec['ig_format'] ?? '' }}</span></td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $rec['adaptation_tips'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Cross-Platform Strategy --}}
            @if(isset($result['cross_platform_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chess"></i> Cross-Platform Strategy</div>
                @if(!empty($result['cross_platform_strategy']['content_pillars']))
                <div style="margin-bottom:0.75rem;">
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.375rem;font-weight:600;">Content Pillars</div>
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($result['cross_platform_strategy']['content_pillars'] as $pillar)
                        <span class="aith-e-pill aith-e-pill-green">{{ $pillar }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                <div class="aith-e-grid-3">
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Posting Cadence</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;font-size:0.85rem;">{{ $result['cross_platform_strategy']['posting_cadence'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Repurpose Ratio</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;font-size:0.85rem;">{{ $result['cross_platform_strategy']['repurpose_ratio'] ?? '-' }}</div>
                    </div>
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Growth Timeline</div>
                        <div class="aith-e-summary-value" style="color:#86efac;font-size:0.85rem;">{{ $result['cross_platform_strategy']['growth_timeline'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Quick Wins --}}
            @if(!empty($result['quick_wins']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bolt"></i> Quick Wins</div>
                @foreach($result['quick_wins'] as $win)
                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="flex-shrink:0;">
                        @php $effort = strtolower($win['effort'] ?? ''); @endphp
                        <span class="aith-e-tag {{ $effort === 'low' ? 'aith-e-tag-high' : 'aith-e-tag-medium' }}">{{ $win['effort'] ?? '' }} effort</span>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:0.85rem;color:#fff;font-weight:500;">{{ $win['action'] ?? '' }}</div>
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-top:0.125rem;">{{ $win['expected_result'] ?? '' }} · {{ $win['timeframe'] ?? '' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Next Steps --}}
            @php
                $nextSteps = config('appaitools.enterprise_tools.ig-yt-arbitrage.next_steps', []);
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

            </div>
            @endif

            @if(session('error'))
            <div class="aith-e-error">{{ session('error') }}</div>
            @endif
        </div>

        @include('appaitools::livewire.enterprise._enterprise-history')
    </div>
</div>
