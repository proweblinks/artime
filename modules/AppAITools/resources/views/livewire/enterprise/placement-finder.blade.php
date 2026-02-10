<div>
    @include('appaitools::livewire.enterprise._enterprise-tool-base')

    {{-- Wider layout for placement finder --}}
    <style>
        .aith-tool.aith-tool-wide { max-width: 1280px; }
        .aith-pf-avatar {
            width: 2.75rem; height: 2.75rem; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.1rem; color: #fff; flex-shrink: 0;
            text-transform: uppercase;
        }
        .aith-pf-avatar-sm {
            width: 2.25rem; height: 2.25rem; font-size: 0.85rem;
        }
        .aith-pf-channel-card {
            padding: 1rem; border-radius: 0.75rem;
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 0.625rem; transition: border-color 0.2s;
        }
        .aith-pf-channel-card:hover {
            border-color: rgba(139,92,246,0.25);
        }
        .aith-pf-find-more {
            width: 100%; padding: 1rem; border-radius: 0.75rem;
            background: rgba(139,92,246,0.06); border: 2px dashed rgba(139,92,246,0.2);
            color: #c4b5fd; font-size: 0.9rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .aith-pf-find-more:hover {
            background: rgba(139,92,246,0.12); border-color: rgba(139,92,246,0.35);
        }
        .aith-pf-find-more:disabled {
            opacity: 0.5; cursor: not-allowed;
        }
    </style>

    <div class="aith-tool aith-tool-wide">
        <div class="aith-nav">
            <a href="{{ route('app.ai-tools.enterprise-suite') }}" class="aith-nav-btn">
                <i class="fa-light fa-arrow-left"></i> Enterprise Suite
            </a>
        </div>

        <div class="aith-card">
            <div class="aith-e-tool-header">
                <div class="aith-e-tool-icon" style="background:linear-gradient(135deg,#a855f7,#7c3aed);">
                    <i class="fa-light fa-bullseye-pointer" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <div class="aith-e-tool-info">
                    <h2>Placement Finder</h2>
                    <p>Find YouTube channels for Google Ads placement targeting</p>
                </div>
                <span class="aith-e-badge-enterprise">Enterprise</span>
            </div>

            @if(!$result && !$isLoading)
            {{-- Input Form --}}
            <div style="text-align:center;margin-bottom:1.25rem;">
                <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;line-height:1.5;">Enter your YouTube channel link and we'll find channels whose audience would engage with your content — ready to paste into Google Ads.</p>
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Your YouTube Channel URL</label>
                <input type="url" wire:model="url" class="aith-input"
                       placeholder="https://youtube.com/@yourchannel">
                @error('url') <span class="aith-e-field-error">{{ $message }}</span> @enderror
                <span style="font-size:0.75rem;color:rgba(255,255,255,0.35);margin-top:0.25rem;display:block;">Supports: youtube.com/@handle, youtube.com/channel/..., youtube.com/c/...</span>
            </div>
            <div class="aith-form-group">
                <label class="aith-label">Target Niche (optional)</label>
                <input type="text" wire:model="niche" class="aith-input"
                       placeholder="e.g. tech reviews, fitness, personal finance">
            </div>
            <button wire:click="analyze" wire:loading.attr="disabled" wire:target="analyze" class="aith-btn-primary" style="width:100%;margin-top:1rem;">
                <span wire:loading.remove wire:target="analyze">
                    <i class="fa-light fa-magnifying-glass"></i> Find Placement Channels
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
                <div class="aith-e-loading-title">Finding placement channels...</div>
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
                <span class="aith-e-result-title">Placement Results</span>
                <div class="aith-e-result-actions">
                    @if(!empty($result['placements']))
                    <button onclick="
                        var urls = @js(collect($result['placements'])->pluck('channel_url')->filter()->implode('\n'));
                        enterpriseCopy(urls, 'All {{ count($result['placements']) }} channel URLs copied!');
                    " class="aith-e-btn-copy" style="padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-copy"></i> Copy All {{ count($result['placements']) }} URLs
                    </button>
                    @endif
                    <button onclick="enterprisePdfExport('pdf-content-placement-finder', 'Placement-Finder-Analysis')" class="aith-e-btn-pdf">
                        <i class="fa-light fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="resetForm" class="aith-btn-secondary" style="font-size:0.8rem;padding:0.375rem 0.75rem;">
                        <i class="fa-light fa-arrow-rotate-left"></i> New Search
                    </button>
                </div>
            </div>

            <div id="pdf-content-placement-finder">

            {{-- Channel Info Card --}}
            @if(isset($result['channel_info']))
            @php $ci = $result['channel_info']; @endphp
            <div style="display:flex;align-items:center;gap:1rem;padding:1.25rem;border-radius:0.75rem;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.2);margin-bottom:1rem;">
                @if(!empty($ci['thumbnail_url']))
                <img src="{{ $ci['thumbnail_url'] }}" alt="{{ $ci['name'] ?? '' }}" style="width:3.5rem;height:3.5rem;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid rgba(139,92,246,0.3);">
                @else
                @php
                    $channelInitial = strtoupper(substr($ci['name'] ?? 'Y', 0, 1));
                    $avatarGradients = ['A'=>'#e91e63,#9c27b0','B'=>'#2196f3,#00bcd4','C'=>'#ff9800,#f44336','D'=>'#4caf50,#009688','E'=>'#673ab7,#3f51b5','F'=>'#ff5722,#e91e63','G'=>'#00bcd4,#4caf50','H'=>'#9c27b0,#e91e63','I'=>'#3f51b5,#2196f3','J'=>'#f44336,#ff9800','K'=>'#009688,#00bcd4','L'=>'#e91e63,#ff5722','M'=>'#2196f3,#673ab7','N'=>'#ff9800,#4caf50','O'=>'#4caf50,#2196f3','P'=>'#673ab7,#e91e63','Q'=>'#00bcd4,#3f51b5','R'=>'#f44336,#673ab7','S'=>'#3f51b5,#00bcd4','T'=>'#ff5722,#ff9800','U'=>'#9c27b0,#2196f3','V'=>'#e91e63,#673ab7','W'=>'#4caf50,#ff9800','X'=>'#2196f3,#e91e63','Y'=>'#ff9800,#9c27b0','Z'=>'#00bcd4,#f44336'];
                @endphp
                <div class="aith-pf-avatar" style="background:linear-gradient(135deg,{{ $avatarGradients[$channelInitial] ?? '#7c3aed,#a855f7' }});width:3.5rem;height:3.5rem;font-size:1.4rem;">
                    {{ $channelInitial }}
                </div>
                @endif
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;color:#fff;font-size:1.1rem;">{{ $ci['name'] ?? 'Your Channel' }}</div>
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-top:0.125rem;">{{ $ci['handle'] ?? '' }} · {{ $ci['niche'] ?? '' }}{{ isset($ci['sub_niche']) ? ' / '.$ci['sub_niche'] : '' }}</div>
                    @if(isset($ci['estimated_subscribers']))
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.35);margin-top:0.25rem;">{{ $ci['estimated_subscribers'] }} subscribers · {{ $ci['upload_frequency'] ?? '' }}</div>
                    @endif
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    @php $score = $result['placement_score'] ?? 0; @endphp
                    <div class="aith-e-score-circle {{ $score >= 80 ? 'aith-e-score-high' : ($score >= 50 ? 'aith-e-score-medium' : 'aith-e-score-low') }}" style="width:3.5rem;height:3.5rem;font-size:1rem;">
                        {{ $score }}
                    </div>
                    <div style="font-size:0.6rem;color:rgba(255,255,255,0.3);margin-top:0.25rem;text-align:center;">SCORE</div>
                </div>
            </div>

            {{-- Channel Quick Stats --}}
            <div class="aith-e-grid-3" style="margin-bottom:1rem;">
                <div class="aith-e-summary-card aith-e-summary-card-purple">
                    <div class="aith-e-summary-label">Subscribers</div>
                    <div class="aith-e-summary-value" style="color:#c4b5fd;">{{ $ci['estimated_subscribers'] ?? '-' }}</div>
                    <div class="aith-e-summary-sub">{{ $ci['content_style'] ?? '' }}</div>
                </div>
                <div class="aith-e-summary-card aith-e-summary-card-blue">
                    <div class="aith-e-summary-label">Audience</div>
                    <div class="aith-e-summary-value" style="color:#93c5fd;font-size:1rem;">{{ $ci['audience_type'] ?? '-' }}</div>
                    <div class="aith-e-summary-sub">{{ $ci['upload_frequency'] ?? '' }}</div>
                </div>
                @if(isset($result['niche_insights']))
                <div class="aith-e-summary-card aith-e-summary-card-green">
                    <div class="aith-e-summary-label">Niche CPM Range</div>
                    <div class="aith-e-summary-value" style="color:#86efac;">{{ $result['niche_insights']['niche_cpm_range'] ?? '-' }}</div>
                    <div class="aith-e-summary-sub">{{ $result['niche_insights']['competition_level'] ?? '' }} competition</div>
                </div>
                @endif
            </div>
            @endif

            {{-- Niche Insights --}}
            @if(isset($result['niche_insights']))
            @php $ni = $result['niche_insights']; @endphp
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-chart-mixed"></i> Niche Intelligence</div>

                {{-- Top badges row --}}
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem;">
                    @if(isset($ni['competition_level']))
                    @php $cl = strtolower($ni['competition_level']); @endphp
                    <div style="display:flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                        <i class="fa-light fa-gauge-high" style="font-size:0.7rem;color:rgba(255,255,255,0.4);"></i>
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.4);">Competition</span>
                        <span class="aith-e-tag {{ $cl === 'low' ? 'aith-e-tag-high' : ($cl === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}" style="font-size:0.65rem;padding:0.1rem 0.4rem;">{{ $ni['competition_level'] }}</span>
                    </div>
                    @endif
                    @if(isset($ni['brand_safety']))
                    @php $bs = strtolower($ni['brand_safety']); @endphp
                    <div style="display:flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                        <i class="fa-light fa-shield-check" style="font-size:0.7rem;color:rgba(255,255,255,0.4);"></i>
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.4);">Brand Safety</span>
                        <span class="aith-e-tag {{ $bs === 'high' ? 'aith-e-tag-high' : ($bs === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}" style="font-size:0.65rem;padding:0.1rem 0.4rem;">{{ $ni['brand_safety'] }}</span>
                    </div>
                    @endif
                    @if(isset($ni['buying_intent']))
                    @php $bi = strtolower($ni['buying_intent']); @endphp
                    <div style="display:flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                        <i class="fa-light fa-cart-shopping" style="font-size:0.7rem;color:rgba(255,255,255,0.4);"></i>
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.4);">Buying Intent</span>
                        <span class="aith-e-tag {{ $bi === 'high' ? 'aith-e-tag-high' : ($bi === 'medium' ? 'aith-e-tag-medium' : 'aith-e-tag-low') }}" style="font-size:0.65rem;padding:0.1rem 0.4rem;">{{ $ni['buying_intent'] }}</span>
                    </div>
                    @endif
                    @if(isset($ni['avg_engagement_rate']))
                    <div style="display:flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                        <i class="fa-light fa-heart" style="font-size:0.7rem;color:rgba(255,255,255,0.4);"></i>
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.4);">Engagement</span>
                        <span style="font-size:0.7rem;color:#86efac;font-weight:600;">{{ $ni['avg_engagement_rate'] }}</span>
                    </div>
                    @endif
                    @if(isset($ni['optimal_video_length']))
                    <div style="display:flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:0.5rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                        <i class="fa-light fa-clock" style="font-size:0.7rem;color:rgba(255,255,255,0.4);"></i>
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.4);">Optimal Length</span>
                        <span style="font-size:0.7rem;color:#93c5fd;font-weight:600;">{{ $ni['optimal_video_length'] }}</span>
                    </div>
                    @endif
                </div>

                {{-- Demographics: Age + Gender + Audience --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">

                    {{-- Age Distribution --}}
                    @if(!empty($ni['age_distribution']))
                    <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.06);">
                        <div style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;margin-bottom:0.625rem;display:flex;align-items:center;gap:0.375rem;">
                            <i class="fa-light fa-users" style="font-size:0.65rem;"></i> Age Distribution
                        </div>
                        @foreach($ni['age_distribution'] as $age)
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                            <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);width:3rem;text-align:right;flex-shrink:0;">{{ $age['range'] ?? '' }}</span>
                            <div style="flex:1;height:0.5rem;border-radius:0.25rem;background:rgba(255,255,255,0.06);overflow:hidden;">
                                <div style="height:100%;border-radius:0.25rem;background:linear-gradient(90deg,#7c3aed,#a855f7);width:{{ min(($age['pct'] ?? 0) / 40 * 100, 100) }}%;"></div>
                            </div>
                            <span style="font-size:0.7rem;color:#c4b5fd;font-weight:600;width:2.25rem;text-align:right;flex-shrink:0;">{{ $age['pct'] ?? 0 }}%</span>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Gender + Device Split --}}
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        @if(isset($ni['gender_split']))
                        <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.06);">
                            <div style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.375rem;">
                                <i class="fa-light fa-venus-mars" style="font-size:0.65rem;"></i> Gender Split
                            </div>
                            <div style="display:flex;height:1.25rem;border-radius:0.375rem;overflow:hidden;margin-bottom:0.375rem;">
                                <div style="width:{{ $ni['gender_split']['male'] ?? 50 }}%;background:linear-gradient(90deg,#3b82f6,#60a5fa);display:flex;align-items:center;justify-content:center;">
                                    <span style="font-size:0.6rem;color:#fff;font-weight:600;">{{ $ni['gender_split']['male'] ?? 50 }}%</span>
                                </div>
                                <div style="width:{{ $ni['gender_split']['female'] ?? 50 }}%;background:linear-gradient(90deg,#ec4899,#f472b6);display:flex;align-items:center;justify-content:center;">
                                    <span style="font-size:0.6rem;color:#fff;font-weight:600;">{{ $ni['gender_split']['female'] ?? 50 }}%</span>
                                </div>
                            </div>
                            <div style="display:flex;justify-content:space-between;">
                                <span style="font-size:0.65rem;color:#60a5fa;"><i class="fa-light fa-mars" style="margin-right:0.2rem;"></i>Male</span>
                                <span style="font-size:0.65rem;color:#f472b6;"><i class="fa-light fa-venus" style="margin-right:0.2rem;"></i>Female</span>
                            </div>
                        </div>
                        @endif

                        @if(isset($ni['device_split']))
                        <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.06);">
                            <div style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.375rem;">
                                <i class="fa-light fa-display" style="font-size:0.65rem;"></i> Device Split
                            </div>
                            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                                @php
                                    $deviceIcons = ['mobile' => 'fa-mobile', 'desktop' => 'fa-desktop', 'tablet' => 'fa-tablet', 'tv' => 'fa-tv'];
                                    $deviceColors = ['mobile' => '#22c55e', 'desktop' => '#3b82f6', 'tablet' => '#f59e0b', 'tv' => '#a855f7'];
                                @endphp
                                @foreach($ni['device_split'] as $device => $pct)
                                <div style="display:flex;align-items:center;gap:0.3rem;">
                                    <i class="fa-light {{ $deviceIcons[$device] ?? 'fa-circle' }}" style="font-size:0.7rem;color:{{ $deviceColors[$device] ?? '#fff' }};"></i>
                                    <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">{{ ucfirst($device) }}</span>
                                    <span style="font-size:0.7rem;color:{{ $deviceColors[$device] ?? '#fff' }};font-weight:600;">{{ $pct }}%</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Geographic Distribution --}}
                @if(!empty($ni['geographic_top5']))
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.06);margin-bottom:1rem;">
                    <div style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;margin-bottom:0.625rem;display:flex;align-items:center;gap:0.375rem;">
                        <i class="fa-light fa-globe" style="font-size:0.65rem;"></i> Top Geographic Markets
                    </div>
                    @foreach($ni['geographic_top5'] as $geo)
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                        <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);width:2rem;text-align:right;flex-shrink:0;font-weight:600;">{{ $geo['country'] ?? '' }}</span>
                        <div style="flex:1;height:0.5rem;border-radius:0.25rem;background:rgba(255,255,255,0.06);overflow:hidden;">
                            <div style="height:100%;border-radius:0.25rem;background:linear-gradient(90deg,#06b6d4,#22d3ee);width:{{ min(($geo['pct'] ?? 0) / 40 * 100, 100) }}%;"></div>
                        </div>
                        <span style="font-size:0.7rem;color:#22d3ee;font-weight:600;width:2.25rem;text-align:right;flex-shrink:0;">{{ $geo['pct'] ?? 0 }}%</span>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Seasonal CPM Trend --}}
                @if(!empty($ni['seasonal_cpm']))
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.06);margin-bottom:1rem;">
                    <div style="font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;margin-bottom:0.75rem;display:flex;align-items:center;gap:0.375rem;">
                        <i class="fa-light fa-chart-line" style="font-size:0.65rem;"></i> Seasonal CPM Trend
                        <span style="font-size:0.6rem;color:rgba(255,255,255,0.25);margin-left:auto;">Multiplier relative to base CPM</span>
                    </div>
                    <div style="display:flex;align-items:flex-end;gap:0.25rem;height:5rem;">
                        @php $maxV = collect($ni['seasonal_cpm'])->max('v') ?: 1; @endphp
                        @foreach($ni['seasonal_cpm'] as $sm)
                        @php
                            $barH = max(10, ($sm['v'] ?? 0.5) / $maxV * 100);
                            $isHigh = ($sm['v'] ?? 0) >= 1.0;
                            $barColor = $isHigh ? 'linear-gradient(0deg,#22c55e,#4ade80)' : 'linear-gradient(0deg,#3b82f6,#60a5fa)';
                        @endphp
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.25rem;">
                            <span style="font-size:0.55rem;color:{{ $isHigh ? '#86efac' : 'rgba(255,255,255,0.35)' }};font-weight:{{ $isHigh ? '600' : '400' }};">{{ $sm['v'] ?? '' }}x</span>
                            <div style="width:100%;height:{{ $barH }}%;border-radius:0.25rem 0.25rem 0 0;background:{{ $barColor }};min-height:0.375rem;"></div>
                            <span style="font-size:0.55rem;color:rgba(255,255,255,0.4);">{{ $sm['m'] ?? '' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Tags rows: Ad Formats, Content Types, Advertiser Categories, Interests --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    @if(isset($ni['best_ad_formats']))
                    <div>
                        <span style="font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;"><i class="fa-light fa-rectangle-ad" style="margin-right:0.25rem;"></i>Best Ad Formats</span>
                        <div style="display:flex;flex-wrap:wrap;gap:0.25rem;">
                            @foreach((is_array($ni['best_ad_formats']) ? $ni['best_ad_formats'] : [$ni['best_ad_formats']]) as $fmt)
                            <span class="aith-e-tag" style="background:rgba(139,92,246,0.15);color:#c4b5fd;font-size:0.7rem;">{{ $fmt }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(!empty($ni['best_content_types']))
                    <div>
                        <span style="font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;"><i class="fa-light fa-video" style="margin-right:0.25rem;"></i>Best Content Types</span>
                        <div style="display:flex;flex-wrap:wrap;gap:0.25rem;">
                            @foreach($ni['best_content_types'] as $ct)
                            <span class="aith-e-tag" style="background:rgba(34,197,94,0.15);color:#86efac;font-size:0.7rem;">{{ $ct }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(!empty($ni['top_advertiser_categories']))
                    <div>
                        <span style="font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;"><i class="fa-light fa-building" style="margin-right:0.25rem;"></i>Top Advertiser Verticals</span>
                        <div style="display:flex;flex-wrap:wrap;gap:0.25rem;">
                            @foreach($ni['top_advertiser_categories'] as $cat)
                            <span class="aith-e-tag" style="background:rgba(234,179,8,0.15);color:#fde047;font-size:0.7rem;">{{ $cat }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(!empty($ni['audience_interests']))
                    <div>
                        <span style="font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;"><i class="fa-light fa-sparkles" style="margin-right:0.25rem;"></i>Audience Interests</span>
                        <div style="display:flex;flex-wrap:wrap;gap:0.25rem;">
                            @foreach($ni['audience_interests'] as $interest)
                            <span class="aith-e-tag" style="background:rgba(6,182,212,0.15);color:#67e8f9;font-size:0.7rem;">{{ $interest }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Peak Months --}}
                @if(isset($ni['peak_months']))
                <div style="margin-top:0.75rem;">
                    <span style="font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;display:block;margin-bottom:0.375rem;"><i class="fa-light fa-calendar-star" style="margin-right:0.25rem;"></i>Peak Advertising Months</span>
                    <div style="display:flex;flex-wrap:wrap;gap:0.25rem;">
                        @foreach((is_array($ni['peak_months']) ? $ni['peak_months'] : [$ni['peak_months']]) as $month)
                        <span class="aith-e-tag" style="background:rgba(239,68,68,0.15);color:#fca5a5;font-size:0.7rem;">{{ $month }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            {{-- Placement Channels --}}
            @if(!empty($result['placements']))
            <div class="aith-e-section-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                    <div class="aith-e-section-card-title" style="margin-bottom:0;"><i class="fa-light fa-bullseye-pointer"></i> Placement Channels ({{ count($result['placements']) }})</div>
                </div>

                @foreach($result['placements'] as $idx => $p)
                <div class="aith-pf-channel-card">
                    {{-- Channel header --}}
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                        @if(!empty($p['thumbnail_url']))
                        <img src="{{ $p['thumbnail_url'] }}" alt="{{ $p['channel_name'] ?? '' }}" style="width:2.25rem;height:2.25rem;border-radius:50%;object-fit:cover;flex-shrink:0;border:1px solid rgba(255,255,255,0.1);">
                        @else
                        @php
                            $pInitial = strtoupper(substr($p['channel_name'] ?? '?', 0, 1));
                            $pGradients = ['A'=>'#e91e63,#9c27b0','B'=>'#2196f3,#00bcd4','C'=>'#ff9800,#f44336','D'=>'#4caf50,#009688','E'=>'#673ab7,#3f51b5','F'=>'#ff5722,#e91e63','G'=>'#00bcd4,#4caf50','H'=>'#9c27b0,#e91e63','I'=>'#3f51b5,#2196f3','J'=>'#f44336,#ff9800','K'=>'#009688,#00bcd4','L'=>'#e91e63,#ff5722','M'=>'#2196f3,#673ab7','N'=>'#ff9800,#4caf50','O'=>'#4caf50,#2196f3','P'=>'#673ab7,#e91e63','Q'=>'#00bcd4,#3f51b5','R'=>'#f44336,#673ab7','S'=>'#3f51b5,#00bcd4','T'=>'#ff5722,#ff9800','U'=>'#9c27b0,#2196f3','V'=>'#e91e63,#673ab7','W'=>'#4caf50,#ff9800','X'=>'#2196f3,#e91e63','Y'=>'#ff9800,#9c27b0','Z'=>'#00bcd4,#f44336'];
                        @endphp
                        <div class="aith-pf-avatar aith-pf-avatar-sm" style="background:linear-gradient(135deg,{{ $pGradients[$pInitial] ?? '#7c3aed,#a855f7' }});">
                            {{ $pInitial }}
                        </div>
                        @endif
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                                <span style="font-weight:700;color:#fff;font-size:0.9rem;">{{ $p['channel_name'] ?? '' }}</span>
                                @if(isset($p['handle']))
                                <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);">{{ $p['handle'] }}</span>
                                @endif
                                @if(isset($p['tier']))
                                @php $tier = strtolower($p['tier']); @endphp
                                <span class="aith-e-tag" style="font-size:0.6rem;padding:0.1rem 0.4rem;{{ $tier === 'large' ? 'background:rgba(234,179,8,0.15);color:#fde047;' : ($tier === 'medium' ? 'background:rgba(59,130,246,0.15);color:#93c5fd;' : 'background:rgba(34,197,94,0.15);color:#86efac;') }}">{{ ucfirst($tier) }}</span>
                                @endif
                            </div>
                            @if(isset($p['subscribers']))
                            <span style="font-size:0.75rem;color:rgba(255,255,255,0.35);">{{ $p['subscribers'] }} subscribers · {{ $p['content_type'] ?? '' }}</span>
                            @endif
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0;">
                            @php $rs = $p['relevance_score'] ?? 0; @endphp
                            <span class="aith-e-match-badge {{ $rs >= 80 ? 'aith-e-match-high' : ($rs >= 60 ? 'aith-e-match-medium' : 'aith-e-match-low') }}">{{ $rs }}%</span>
                            @if(isset($p['channel_url']))
                            <a href="{{ $p['channel_url'] }}" target="_blank" style="color:rgba(255,255,255,0.4);font-size:0.85rem;text-decoration:none;" title="Visit channel"><i class="fa-light fa-arrow-up-right-from-square"></i></a>
                            <button onclick="enterpriseCopy('{{ $p['channel_url'] }}', 'URL copied!')" style="background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.4);font-size:0.85rem;" title="Copy URL"><i class="fa-light fa-copy"></i></button>
                            @endif
                        </div>
                    </div>

                    {{-- Match reason --}}
                    @if(isset($p['audience_match']))
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.5rem;padding-left:3rem;">
                        {{ $p['audience_match'] }}
                    </div>
                    @endif

                    {{-- Badges row --}}
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;padding-left:3rem;">
                        @if(isset($p['estimated_cpm']))
                        <span class="aith-e-pill aith-e-pill-green"><i class="fa-light fa-dollar-sign" style="font-size:0.6rem;"></i> {{ $p['estimated_cpm'] }}</span>
                        @endif
                        @if(isset($p['recommended_ad_format']))
                        <span class="aith-e-pill aith-e-pill-blue"><i class="fa-light fa-rectangle-ad" style="font-size:0.6rem;"></i> {{ $p['recommended_ad_format'] }}</span>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Find More Button --}}
                <button wire:click="findMore" wire:loading.attr="disabled" wire:target="findMore" class="aith-pf-find-more" style="margin-top:0.5rem;">
                    <span wire:loading.remove wire:target="findMore">
                        <i class="fa-light fa-plus-circle"></i> Find 10 More Channels
                        <span style="opacity:0.6;font-size:0.75rem;margin-left:0.25rem;">3 credits</span>
                    </span>
                    <span wire:loading wire:target="findMore">
                        <i class="fa-light fa-spinner-third fa-spin"></i> Finding more channels...
                    </span>
                </button>
            </div>
            @endif

            {{-- Campaign Strategy --}}
            @if(isset($result['campaign_strategy']))
            @php $cs = $result['campaign_strategy']; @endphp
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-bullseye"></i> Google Ads Campaign Strategy</div>

                {{-- Budget & Performance --}}
                <div class="aith-e-grid-3" style="margin-bottom:1rem;">
                    <div style="text-align:center;padding:0.75rem;border-radius:0.5rem;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.15);">
                        <div style="font-size:0.65rem;color:rgba(255,255,255,0.4);text-transform:uppercase;margin-bottom:0.25rem;">Daily Budget</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#86efac;">{{ $cs['recommended_daily_budget'] ?? '-' }}</div>
                    </div>
                    <div style="text-align:center;padding:0.75rem;border-radius:0.5rem;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.15);">
                        <div style="font-size:0.65rem;color:rgba(255,255,255,0.4);text-transform:uppercase;margin-bottom:0.25rem;">Expected CPM</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#93c5fd;">{{ $cs['expected_cpm_range'] ?? '-' }}</div>
                    </div>
                    <div style="text-align:center;padding:0.75rem;border-radius:0.5rem;background:rgba(168,85,247,0.08);border:1px solid rgba(168,85,247,0.15);">
                        <div style="font-size:0.65rem;color:rgba(255,255,255,0.4);text-transform:uppercase;margin-bottom:0.25rem;">Expected CTR</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#c4b5fd;">{{ $cs['expected_ctr'] ?? '-' }}</div>
                    </div>
                </div>

                @if(isset($cs['recommended_bid_strategy']))
                <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-bottom:0.75rem;">
                    <strong style="color:rgba(255,255,255,0.7);">Bid Strategy:</strong> {{ $cs['recommended_bid_strategy'] }}
                </div>
                @endif

                {{-- Ad Group Structure --}}
                @if(!empty($cs['ad_group_structure']))
                <div style="font-size:0.75rem;color:rgba(255,255,255,0.35);text-transform:uppercase;margin-bottom:0.5rem;margin-top:0.75rem;">Suggested Ad Groups</div>
                @foreach($cs['ad_group_structure'] as $agIdx => $ag)
                <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.06);margin-bottom:0.5rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                        <span class="aith-e-step-badge">{{ $agIdx + 1 }}</span>
                        <span style="font-weight:600;color:#fff;font-size:0.85rem;">{{ $ag['group_name'] ?? '' }}</span>
                    </div>
                    @if(!empty($ag['channels']))
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-bottom:0.375rem;padding-left:2.25rem;">
                        @foreach($ag['channels'] as $agHandle)
                        <span class="aith-e-pill" style="background:rgba(139,92,246,0.12);color:#c4b5fd;font-size:0.7rem;padding:0.15rem 0.5rem;">{{ $agHandle }}</span>
                        @endforeach
                    </div>
                    @endif
                    @if(isset($ag['rationale']))
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);padding-left:2.25rem;">{{ $ag['rationale'] }}</div>
                    @endif
                </div>
                @endforeach
                @endif
            </div>
            @endif

            {{-- Google Ads Keywords --}}
            @if(!empty($result['google_ads_keywords']))
            <div class="aith-e-section-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                    <div class="aith-e-section-card-title" style="margin-bottom:0;"><i class="fa-light fa-tags"></i> Google Ads Keywords</div>
                    <button onclick="
                        var kws = @js(collect($result['google_ads_keywords'])->implode(', '));
                        enterpriseCopy(kws, 'Keywords copied!');
                    " class="aith-e-btn-copy" style="font-size:0.7rem;">
                        <i class="fa-light fa-copy"></i> Copy
                    </button>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                    @foreach($result['google_ads_keywords'] as $kw)
                    <span class="aith-e-pill" style="background:rgba(139,92,246,0.12);color:#c4b5fd;">{{ $kw }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tips --}}
            @if(!empty($result['tips']))
            <div class="aith-e-section-card">
                <div class="aith-e-section-card-title"><i class="fa-light fa-lightbulb"></i> Placement Tips</div>
                <ul class="aith-e-list">
                    @foreach($result['tips'] as $tip)
                    <li><span class="bullet"><i class="fa-solid fa-circle" style="font-size:0.35rem;"></i></span> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- How to Use --}}
            <div class="aith-e-section-card" style="background:rgba(139,92,246,0.05);border-color:rgba(139,92,246,0.15);">
                <div class="aith-e-section-card-title"><i class="fa-light fa-circle-info"></i> How to Use These Results</div>
                <ul class="aith-e-list" style="font-size:0.8rem;">
                    <li><span class="bullet" style="color:#c4b5fd;"><strong>1.</strong></span> Click <strong style="color:rgba(255,255,255,0.8);">Copy All URLs</strong> above to copy all channel URLs at once</li>
                    <li><span class="bullet" style="color:#c4b5fd;"><strong>2.</strong></span> In <strong style="color:rgba(255,255,255,0.8);">Google Ads</strong>, create a new Video campaign → Ad Group → Placements</li>
                    <li><span class="bullet" style="color:#c4b5fd;"><strong>3.</strong></span> Paste the channel URLs into the <strong style="color:rgba(255,255,255,0.8);">YouTube channels</strong> placement field</li>
                    <li><span class="bullet" style="color:#c4b5fd;"><strong>4.</strong></span> Start with the <strong style="color:rgba(255,255,255,0.8);">highest relevance</strong> channels and expand based on performance</li>
                </ul>
            </div>

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
