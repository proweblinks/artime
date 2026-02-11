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
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#6366f1,#a855f7);">
                    <i class="fa-light fa-people-arrows" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Duet & Stitch Planner</h2>
                    <p>Find high-engagement duet/stitch opportunities</p>
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
                <label class="aith-label">Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. fitness, cooking, comedy">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Goal (optional)</label>
                <input type="text" wire:model="goal" class="aith-input"
                       placeholder="e.g. grow followers, go viral, brand awareness">
                @error('goal') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-people-arrows"></i> Find Opportunities
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
                <div class="aith-e-loading-title">Finding collaboration opportunities...</div>
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
                <span class="aith-e-result-title">Duet & Stitch Plan</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-duet-stitch', 'Duet-Stitch-Plan')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-duet-stitch">

            {{-- Score --}}
            @php $score = $result['collaboration_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Collaboration Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent collaboration opportunities in your niche
                        @elseif($score >= 50) Good duet/stitch potential - strategic targeting recommended
                        @else Limited opportunities found - broaden your niche or approach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Duet Opportunities --}}
            @if(!empty($result['duet_opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-users"></i> Duet Opportunities</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Creator</th><th>Followers</th><th>Content Type</th><th>Engagement</th><th>Duet Idea</th><th>Potential Reach</th></tr></thead>
                        <tbody>
                        @foreach($result['duet_opportunities'] as $opp)
                        <tr>
                            <td style="font-weight:600;color:#a78bfa;">{{ $opp['creator'] ?? '' }}</td>
                            <td>{{ $opp['followers'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $opp['content_type'] ?? '-' }}</td>
                            <td>
                                <span class="aith-e-pill aith-e-pill-green">{{ $opp['engagement_rate'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.7);">{{ $opp['duet_idea'] ?? '' }}</td>
                            <td style="font-weight:600;color:#6366f1;">{{ $opp['potential_reach'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Stitch Opportunities --}}
            @if(!empty($result['stitch_opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-scissors"></i> Stitch Opportunities</div>
                <div class="aith-e-grid-2">
                @foreach($result['stitch_opportunities'] as $stitch)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $stitch['creator'] ?? '' }}</span>
                    </div>
                    @if(isset($stitch['video_topic']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Topic:</strong> {{ $stitch['video_topic'] }}
                    </div>
                    @endif
                    @if(isset($stitch['stitch_angle']))
                    <div style="font-size:0.8rem;color:#a78bfa;margin-bottom:0.25rem;">
                        <strong style="color:rgba(255,255,255,0.6);">Angle:</strong> {{ $stitch['stitch_angle'] }}
                    </div>
                    @endif
                    @if(isset($stitch['why_effective']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">
                        <strong style="color:rgba(255,255,255,0.6);">Why it works:</strong> {{ $stitch['why_effective'] }}
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Trending Duets --}}
            @if(!empty($result['trending_duets']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-fire"></i> Trending Duets</div>
                @foreach($result['trending_duets'] as $trend)
                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.625rem 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="flex:1;">
                        <div style="font-weight:600;color:#a78bfa;font-size:0.875rem;">{{ $trend['trend'] ?? '' }}</div>
                        @if(isset($trend['how_to_participate']))
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-top:0.25rem;">{{ $trend['how_to_participate'] }}</div>
                        @endif
                    </div>
                    @if(isset($trend['timing']))
                    <span class="aith-e-tag aith-e-tag-medium" style="white-space:nowrap;">{{ $trend['timing'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Strategy --}}
            @if(isset($result['strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chess"></i> Strategy</div>
                <div class="aith-e-grid-3">
                    @foreach($result['strategy'] as $key => $val)
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">{{ str_replace('_', ' ', $key) }}</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">
                            @if(is_array($val))
                                {{ implode(', ', $val) }}
                            @else
                                {{ $val }}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tips --}}
            @if(!empty($result['tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['tips'] as $tip)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
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
