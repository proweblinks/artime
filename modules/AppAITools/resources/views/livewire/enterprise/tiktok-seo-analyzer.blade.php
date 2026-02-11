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
                    <i class="fa-light fa-magnifying-glass" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>TikTok SEO Analyzer</h2>
                    <p>Optimize captions, keywords, and descriptions for TikTok search</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div class="aith-form-group">
                <label class="aith-label">TikTok Profile</label>
                <input type="text" wire:model="profile" class="aith-input"
                       placeholder="@username or profile URL">
                @error('profile')
                <span class="aith-e-field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Caption to Analyze (optional)</label>
                <textarea wire:model="caption" class="aith-input" rows="3"
                          placeholder="Paste a caption to analyze..."></textarea>
                @error('caption')
                <span class="aith-e-field-error">{{ $message }}</span>
                @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-magnifying-glass"></i> Analyze SEO
                </span>
                <span wire:loading wire:target="analyze">
                    <i class="fa-light fa-spinner-third fa-spin"></i> Analyzing...
                </span>
                <span style="margin-left:0.5rem;opacity:0.6;font-size:0.8rem;">2 credits</span>
            </button>
            @endif

            @if($isLoading)
            {{-- Loading Steps --}}
            <div class="aith-e-loading" x-data="{ step: 0 }" x-init="
                let steps = {{ count($loadingSteps) }};
                let interval = setInterval(() => { if(step < steps - 1) step++; }, 2500);
                $wire.on('loadingComplete', () => clearInterval(interval));
            ">
                <div class="aith-e-loading-title">Analyzing TikTok SEO...</div>
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
                <span class="aith-e-result-title">TikTok SEO Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-seo-analyzer', 'TikTok-SEO-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-seo-analyzer">
            {{-- Score --}}
            @php $score = $result['seo_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">SEO Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Excellent SEO optimization - highly discoverable content
                        @elseif($score >= 50) Good SEO foundation with room for improvement
                        @else Needs significant SEO optimization for better discoverability
                        @endif
                    </div>
                </div>
            </div>

            {{-- Profile Analysis --}}
            @if(isset($result['profile_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-user"></i> Profile Analysis</div>
                <div class="aith-e-grid-2" style="margin-bottom:0.75rem;">
                    @if(isset($result['profile_analysis']['bio_score']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Bio Score</span>
                        <div style="font-size:0.875rem;margin-top:0.125rem;">
                            @php $bs = intval($result['profile_analysis']['bio_score']); @endphp
                            <span class="aith-e-tag {{ $bs >= 80 ? 'aith-e-tag-high' : ($bs >= 50 ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $result['profile_analysis']['bio_score'] }}</span>
                        </div>
                    </div>
                    @endif
                    @if(isset($result['profile_analysis']['username_score']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Username Score</span>
                        <div style="font-size:0.875rem;margin-top:0.125rem;">
                            @php $us = intval($result['profile_analysis']['username_score']); @endphp
                            <span class="aith-e-tag {{ $us >= 80 ? 'aith-e-tag-high' : ($us >= 50 ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $result['profile_analysis']['username_score'] }}</span>
                        </div>
                    </div>
                    @endif
                    @if(isset($result['profile_analysis']['keyword_density']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Keyword Density</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['profile_analysis']['keyword_density'] }}</div>
                    </div>
                    @endif
                </div>
                @if(!empty($result['profile_analysis']['improvements']))
                <div>
                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Improvements</span>
                    <ul class="aith-e-list" style="margin-top:0.375rem;">
                        @foreach($result['profile_analysis']['improvements'] as $improvement)
                        <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $improvement }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif

            {{-- Caption Analysis --}}
            @if(isset($result['caption_analysis']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-text"></i> Caption Analysis</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['caption_analysis']['readability']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Readability</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['caption_analysis']['readability'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['caption_analysis']['keyword_usage']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Keyword Usage</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['caption_analysis']['keyword_usage'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['caption_analysis']['cta_present']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">CTA Present</span>
                        <div style="font-size:0.875rem;margin-top:0.125rem;">
                            @if($result['caption_analysis']['cta_present'])
                            <span style="color:#22c55e;"><i class="fa-solid fa-check-circle"></i> Yes</span>
                            @else
                            <span style="color:#ef4444;"><i class="fa-solid fa-times-circle"></i> No</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    @if(isset($result['caption_analysis']['hashtag_placement']))
                    <div style="padding:0.375rem 0;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Hashtag Placement</span>
                        <div style="font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.125rem;">{{ $result['caption_analysis']['hashtag_placement'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Keyword Opportunities --}}
            @if(!empty($result['keyword_opportunities']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-key"></i> Keyword Opportunities</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Keyword</th><th>Search Volume</th><th>Competition</th><th>Recommendation</th></tr></thead>
                        <tbody>
                        @foreach($result['keyword_opportunities'] as $kw)
                        <tr>
                            <td style="font-weight:600;color:#3b82f6;">{{ $kw['keyword'] ?? '' }}</td>
                            <td>{{ $kw['search_volume'] ?? '-' }}</td>
                            <td>
                                @php $comp = strtolower($kw['competition'] ?? ''); @endphp
                                <span class="aith-e-tag {{ $comp === 'low' ? 'aith-e-tag-high' : ($comp === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $kw['competition'] ?? '-' }}</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $kw['recommendation'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Content Pillars --}}
            @if(!empty($result['content_pillars']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-columns"></i> Content Pillars</div>
                <div class="aith-e-grid-2">
                @foreach($result['content_pillars'] as $pillar)
                <div class="aith-e-section-card" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                        <span style="font-weight:600;color:#fff;font-size:0.875rem;">{{ $pillar['topic'] ?? '' }}</span>
                        @if(isset($pillar['search_demand']))
                        @php $sd = strtolower($pillar['search_demand']); @endphp
                        <span class="aith-e-tag {{ $sd === 'high' ? 'aith-e-tag-high' : ($sd === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $pillar['search_demand'] }}</span>
                        @endif
                    </div>
                    @if(!empty($pillar['content_ideas']))
                    <div>
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;">Content Ideas</span>
                        <ul style="margin:0.25rem 0 0 0;padding:0;list-style:none;">
                            @foreach($pillar['content_ideas'] as $idea)
                            <li style="font-size:0.8rem;color:rgba(255,255,255,0.5);padding:0.125rem 0;">
                                <i class="fa-solid fa-circle" style="font-size:0.25rem;vertical-align:middle;margin-right:0.375rem;"></i>{{ $idea }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Optimization Tips --}}
            @if(!empty($result['optimization_tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Optimization Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['optimization_tips'] as $tip)
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
