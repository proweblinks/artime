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
                    <i class="fa-light fa-handshake" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Brand Partnership Finder</h2>
                    <p>Match with brands looking for TikTok creators</p>
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
                       placeholder="e.g. beauty, tech, lifestyle">
                @error('niche') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Follower Count (optional)</label>
                <input type="text" wire:model="followerCount" class="aith-input"
                       placeholder="e.g. 10K, 100K, 1M">
                @error('followerCount') <span class="aith-e-field-error">{{ $message }}</span> @enderror
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-handshake"></i> Find Brand Partners
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
                <div class="aith-e-loading-title">Finding brand partnerships...</div>
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
                <span class="aith-e-result-title">Brand Partnership Results</span>
                <div class="aith-e-result-actions">
                    <button onclick="enterprisePdfExport('pdf-content-tiktok-brand-partnership', 'Brand-Partnership')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Analysis
                    </button>
                </div>
            </div>

            <div id="pdf-content-tiktok-brand-partnership">

            {{-- Score --}}
            @php $score = $result['partnership_score'] ?? 0; @endphp
            <div class="aith-e-score-card">
                <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}">
                    {{ $score }}
                </div>
                <div class="aith-e-score-info">
                    <div class="aith-e-score-label">Partnership Score</div>
                    <div class="aith-e-score-text">
                        @if($score >= 80) Highly attractive profile for brand partnerships
                        @elseif($score >= 50) Good partnership potential - strengthen your niche authority
                        @else Limited brand appeal - focus on engagement and content quality
                        @endif
                    </div>
                </div>
            </div>

            {{-- Brand Matches --}}
            @if(!empty($result['brand_matches']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-handshake"></i> Brand Matches</div>
                <div style="overflow-x:auto;">
                    <table class="aith-e-table">
                        <thead><tr><th>Brand</th><th>Industry</th><th>Match</th><th>Deal Type</th><th>Est. Rate</th><th>Why Match</th></tr></thead>
                        <tbody>
                        @foreach($result['brand_matches'] as $match)
                        <tr>
                            <td style="font-weight:600;color:#fb7185;">{{ $match['brand'] ?? '' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $match['industry'] ?? '-' }}</td>
                            <td>
                                @php $ms = intval($match['match_score'] ?? 0); @endphp
                                <span class="aith-e-tag {{ $ms >= 80 ? 'aith-e-tag-high' : ($ms >= 50 ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}">{{ $ms }}%</span>
                            </td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $match['deal_type'] ?? '-' }}</td>
                            <td style="font-weight:600;color:#22c55e;">{{ $match['estimated_rate'] ?? '-' }}</td>
                            <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $match['why_match'] ?? '' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Pitch Templates --}}
            @if(!empty($result['pitch_templates']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-envelope"></i> Pitch Templates</div>
                @foreach($result['pitch_templates'] as $tplIdx => $template)
                <div x-data="{ open: false }" style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.75rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;" @click="open = !open">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span class="aith-e-tag aith-e-tag-medium">{{ $template['brand_type'] ?? 'Template' }}</span>
                            @if(isset($template['subject_line']))
                            <span style="font-size:0.85rem;color:rgba(255,255,255,0.7);">{{ $template['subject_line'] }}</span>
                            @endif
                        </div>
                        <i class="fa-light" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" style="color:rgba(255,255,255,0.4);font-size:0.75rem;"></i>
                    </div>
                    <div x-show="open" x-collapse style="margin-top:0.75rem;">
                        @if(isset($template['pitch_body']))
                        <div style="margin-bottom:0.5rem;">
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Pitch Body</span>
                            <pre style="white-space:pre-wrap;font-size:0.8rem;color:rgba(255,255,255,0.5);margin:0;font-family:monospace;">{{ $template['pitch_body'] }}</pre>
                        </div>
                        @endif
                        @if(!empty($template['key_metrics']))
                        <div>
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.25rem;">Key Metrics to Include</span>
                            <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                                @foreach($template['key_metrics'] as $metric)
                                <span class="aith-e-pill aith-e-pill-green">{{ $metric }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Rate Card --}}
            @if(isset($result['rate_card']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-tag"></i> Rate Card</div>
                <div class="aith-e-grid-2">
                    @if(isset($result['rate_card']['sponsored_post']))
                    <div class="aith-e-summary-card aith-e-summary-card-pink">
                        <div class="aith-e-summary-label">Sponsored Post</div>
                        <div class="aith-e-summary-value" style="color:#fda4af;">{{ $result['rate_card']['sponsored_post'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['rate_card']['brand_integration']))
                    <div class="aith-e-summary-card aith-e-summary-card-purple">
                        <div class="aith-e-summary-label">Brand Integration</div>
                        <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $result['rate_card']['brand_integration'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['rate_card']['series_deal']))
                    <div class="aith-e-summary-card aith-e-summary-card-blue">
                        <div class="aith-e-summary-label">Series Deal</div>
                        <div class="aith-e-summary-value" style="color:#93c5fd;">{{ $result['rate_card']['series_deal'] }}</div>
                    </div>
                    @endif
                    @if(isset($result['rate_card']['affiliate']))
                    <div class="aith-e-summary-card aith-e-summary-card-green">
                        <div class="aith-e-summary-label">Affiliate</div>
                        <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['rate_card']['affiliate'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Outreach Strategy --}}
            @if(isset($result['outreach_strategy']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bullseye"></i> Outreach Strategy</div>
                <div class="aith-e-grid-3">
                    @foreach($result['outreach_strategy'] as $key => $val)
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
