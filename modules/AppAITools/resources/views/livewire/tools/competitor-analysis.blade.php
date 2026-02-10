<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0,
    step: 0,
    tipIndex: 0,
    tips: [
        'Top creators study competitors for 2+ hours per week',
        'Competitor titles reveal what keywords are working right now',
        'Weak spots in competitor videos are your biggest opportunities',
        'Analyzing top 10 competitors can reveal niche gaps',
        'Better thumbnails alone can double your CTR'
    ],
    steps: [
        { label: 'Resolving Channel', icon: 'fa-download' },
        { label: 'Fetching Videos', icon: 'fa-film' },
        { label: 'Computing Metrics', icon: 'fa-calculator' },
        { label: 'Analyzing Strategy', icon: 'fa-user-magnifying-glass' },
        { label: 'Finding Weaknesses', icon: 'fa-crosshairs' },
        { label: 'Building Spy Report', icon: 'fa-chess' }
    ],
    interval: null, tipInterval: null,
    startLoading() {
        this.progress = 0; this.step = 0; this.tipIndex = 0;
        this.interval = setInterval(() => {
            if (this.progress < 30) this.progress += 2;
            else if (this.progress < 60) this.progress += 1;
            else if (this.progress < 85) this.progress += 0.5;
            else if (this.progress < 95) this.progress += 0.2;
            this.step = Math.min(Math.floor(this.progress / (100 / 6)), 5);
        }, 200);
        this.tipInterval = setInterval(() => { this.tipIndex = (this.tipIndex + 1) % this.tips.length; }, 4000);
    },
    stopLoading() {
        this.progress = 100; this.step = 6;
        clearInterval(this.interval); clearInterval(this.tipInterval);
    }
}"
x-init="
    Livewire.hook('message.processed', (msg, comp) => {
        if (comp.id === $wire.__instance.id && !$wire.isLoading) stopLoading();
    });
">

    {{-- Navigation --}}
    <div class="aith-nav">
        <a href="{{ route('app.ai-tools.index') }}" class="aith-nav-btn">
            <i class="fa-light fa-arrow-left"></i> {{ __('Back') }}
        </a>
        <div class="aith-nav-spacer"></div>
        @if(count($history) > 0)
        <button class="aith-nav-btn" onclick="document.getElementById('aith-history-panel').classList.toggle('aith-open')">
            <i class="fa-light fa-clock-rotate-left"></i> {{ __('History') }}
        </button>
        @endif
    </div>

    @if(!$result)
    {{-- ======================== FORM ======================== --}}
    <div class="aith-card">
        <h2 class="aith-card-title"><span class="aith-emoji">🎯</span> {{ __('Competitor Analysis') }}</h2>

        <div class="aith-feature-box aith-feat-red">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Full channel-level competitive intelligence') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Steal Their Strategy — title patterns, hooks, formula') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Content Gap Radar — find what they\'re missing') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Weakness Exploits — actionable attack vectors') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Head-to-head comparison with your channel') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('12-week Battle Plan to outperform them') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Competitor Channel or Video URL') }}</label>
            <input type="url" wire:model="competitorUrl" class="aith-input" placeholder="https://youtube.com/@channel or video URL">
            @error('competitorUrl') <div class="aith-field-error">{{ $message }}</div> @enderror
            <div style="font-size: 0.6875rem; color: #94a3b8; margin-top: 0.375rem;">{{ __('Supports @handle, /channel/ID, or any video URL (we\'ll find the channel)') }}</div>
        </div>

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Your Channel URL') }} <span class="aith-label-hint">({{ __('Optional — enables head-to-head comparison') }})</span></label>
            <input type="url" wire:model="myUrl" class="aith-input" placeholder="https://youtube.com/@yourchannel">
            @error('myUrl') <div class="aith-field-error">{{ $message }}</div> @enderror
        </div>

        <button wire:click="analyze" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="analyze">
                <i class="fa-light fa-crosshairs"></i> {{ __('Analyze Competitor') }}
            </span>
            <span wire:loading wire:target="analyze">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Analyzing...') }}
            </span>
        </button>

        {{-- Loading State --}}
        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">🎯</span> {{ __('Building spy report...') }}</div>
                <div class="aith-progress-pct" x-text="Math.round(progress) + '%'"></div>
            </div>
            <div class="aith-progress-bar">
                <div class="aith-progress-fill" :style="'width:' + progress + '%'"></div>
            </div>
            <div class="aith-steps-grid">
                <template x-for="(s, i) in steps" :key="i">
                    <div class="aith-step" :class="{ 'aith-step-done': i < step, 'aith-step-active': i === step }">
                        <span class="aith-step-icon">
                            <i :class="i < step ? 'fa-light fa-check' : (i === step ? 'fa-light fa-spinner-third fa-spin' : 'fa-light ' + s.icon)"></i>
                        </span>
                        <span x-text="s.label"></span>
                    </div>
                </template>
            </div>
            <div class="aith-tip"><span class="aith-emoji">💡</span> <span x-text="tips[tipIndex]"></span></div>
        </div>
    </div>
    @endif

    {{-- ======================== RESULTS ======================== --}}
    @if($result)
    @php
        $ci = $result['competitor_info'] ?? [];
        $cm = $result['computed_metrics'] ?? [];
        $sw = $result['swot'] ?? [];
        $sts = $result['steal_their_strategy'] ?? [];
        $cgr = $result['content_gap_radar'] ?? [];
        $we = $result['weakness_exploits'] ?? [];
        $h2h = $result['head_to_head'] ?? [];
        $ca = $result['content_analysis'] ?? [];
        $mi = $result['monetization_intel'] ?? [];
        $bp = $result['battle_plan'] ?? [];
        $myInfo = $result['my_info'] ?? [];
        $myMetrics = $result['my_metrics'] ?? [];
        $grade = $result['competitor_grade'] ?? '';
        $gradeClass = match(true) {
            str_starts_with($grade, 'A') => 'aith-grade-a',
            str_starts_with($grade, 'B') => 'aith-grade-b',
            str_starts_with($grade, 'C') => 'aith-grade-c',
            default => 'aith-grade-d',
        };
        $threatLevel = $result['threat_level'] ?? 5;
        $threatLabel = $result['threat_label'] ?? 'Moderate';
        $threatClass = match(true) {
            $threatLevel >= 9 => 'aith-threat-extreme',
            $threatLevel >= 7 => 'aith-threat-high',
            $threatLevel >= 4 => 'aith-threat-med',
            default => 'aith-threat-low',
        };
        $threatColor = match(true) {
            $threatLevel >= 9 => '#991b1b',
            $threatLevel >= 7 => '#dc2626',
            $threatLevel >= 4 => '#d97706',
            default => '#059669',
        };
        $channelAge = '';
        if (!empty($ci['published_at'])) {
            try {
                $created = new \DateTime($ci['published_at']);
                $now = new \DateTime();
                $diff = $now->diff($created);
                if ($diff->y > 0) $channelAge = $diff->y . 'y ' . $diff->m . 'mo';
                else $channelAge = $diff->m . ' months';
            } catch (\Exception $e) {}
        }
    @endphp

    <div id="competitor-results-container">

    {{-- ========== 1. COMPETITOR PROFILE HEADER ========== --}}
    <div class="aith-card">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; margin-bottom:1.25rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">🎯</span> {{ __('Spy Report') }}</h2>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <button class="aith-btn-pdf" onclick="aithExportCompetitorPdf(this)">
                    <i class="fa-light fa-file-pdf"></i> {{ __('Export PDF') }}
                </button>
                <button class="aith-btn-secondary" wire:click="resetForm">
                    <i class="fa-light fa-arrow-rotate-left"></i> {{ __('New') }}
                </button>
            </div>
        </div>

        {{-- Channel Header --}}
        <div class="aith-channel-header">
            @if(!empty($ci['thumbnail']))
            <img src="{{ $ci['thumbnail'] }}" alt="" class="aith-channel-avatar" style="border-color:#fecaca;">
            @endif
            <div class="aith-channel-meta">
                <h3 class="aith-channel-name">{{ $ci['title'] ?? 'Competitor' }}</h3>
                <div class="aith-channel-stats">
                    <span class="aith-channel-stat"><i class="fa-light fa-users"></i> <strong>{{ number_format($ci['subscribers'] ?? 0) }}</strong> {{ __('subs') }}</span>
                    <span class="aith-channel-stat"><i class="fa-light fa-eye"></i> <strong>{{ number_format($ci['total_views'] ?? 0) }}</strong> {{ __('views') }}</span>
                    <span class="aith-channel-stat"><i class="fa-light fa-video"></i> <strong>{{ number_format($ci['video_count'] ?? 0) }}</strong> {{ __('videos') }}</span>
                    @if(!empty($ci['country']))
                    <span class="aith-channel-stat"><i class="fa-light fa-globe"></i> {{ $ci['country'] }}</span>
                    @endif
                    @if($channelAge)
                    <span class="aith-channel-stat"><i class="fa-light fa-calendar"></i> {{ $channelAge }}</span>
                    @endif
                </div>
            </div>
            @if(!empty($result['detected_niche']))
            <span class="aith-badge" style="background:#fef2f2; color:#dc2626; font-size:0.75rem; padding:0.375rem 0.75rem;">{{ $result['detected_niche'] }}</span>
            @endif
        </div>

        {{-- ========== 2. THREAT LEVEL & SCORE ========== --}}
        <div class="aith-threat" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9); border:1px solid #e2e8f0;">
            {{-- Threat gauge --}}
            <div class="aith-threat-gauge">
                <svg viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                    <circle cx="50" cy="50" r="40" fill="none" stroke="{{ $threatColor }}" stroke-width="8" stroke-linecap="round"
                        stroke-dasharray="251" stroke-dashoffset="{{ 251 - (251 * $threatLevel / 10) }}"
                        transform="rotate(-90 50 50)"/>
                </svg>
                <div class="aith-threat-num" style="color:{{ $threatColor }};">{{ $threatLevel }}</div>
            </div>
            <div style="flex:1;">
                <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; margin-bottom:0.5rem;">
                    <div style="font-size:1.125rem; font-weight:700; color:#1e293b;">{{ __('Threat Level') }}</div>
                    <span class="aith-threat-label {{ $threatClass }}">{{ $threatLabel }}</span>
                </div>
                @if(!empty($result['executive_summary']))
                <p style="font-size:0.8125rem; color:#64748b; margin:0; line-height:1.5;">{{ $result['executive_summary'] }}</p>
                @endif
            </div>
            <div style="text-align:center; flex-shrink:0;">
                <div class="aith-score-gauge" style="width:80px; height:80px;">
                    <svg viewBox="0 0 120 120">
                        <circle class="aith-gauge-bg" cx="60" cy="60" r="50"/>
                        <circle class="aith-gauge-fill" cx="60" cy="60" r="50"
                            stroke="{{ ($result['competitor_score'] ?? 0) >= 70 ? '#10b981' : (($result['competitor_score'] ?? 0) >= 40 ? '#f59e0b' : '#ef4444') }}"
                            stroke-dasharray="314"
                            stroke-dashoffset="{{ 314 - (314 * ($result['competitor_score'] ?? 0) / 100) }}"/>
                    </svg>
                    <div class="aith-score-val" style="font-size:1.25rem; color:{{ ($result['competitor_score'] ?? 0) >= 70 ? '#10b981' : (($result['competitor_score'] ?? 0) >= 40 ? '#f59e0b' : '#ef4444') }};">{{ $result['competitor_score'] ?? 0 }}</div>
                </div>
                @if($grade)
                <div class="aith-grade {{ $gradeClass }}" style="width:40px; height:40px; font-size:1rem; margin:0.375rem auto 0; border-radius:8px;">{{ $grade }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ========== 3. HEAD-TO-HEAD COMPARISON ========== --}}
    @if(!empty($h2h) && !empty($myInfo))
    <div class="aith-card aith-card-accent aith-card-accent-red">
        <div class="aith-section-header">
            <h3 class="aith-section-title" style="color:#dc2626;"><i class="fa-light fa-swords"></i> {{ __('Head-to-Head Comparison') }}</h3>
        </div>

        {{-- Verdict badge --}}
        @if(!empty($h2h['verdict']))
        <div style="text-align:center; margin-bottom:1rem;">
            @php
                $verdictColor = match(true) {
                    str_contains($h2h['verdict'], 'ahead') && str_contains($h2h['verdict'], 'You') => '#059669',
                    str_contains($h2h['verdict'], 'Neck') => '#d97706',
                    default => '#dc2626',
                };
            @endphp
            <span style="display:inline-flex; padding:0.5rem 1.25rem; border-radius:999px; font-size:0.9375rem; font-weight:700; color:{{ $verdictColor }}; background:{{ $verdictColor }}10; border:2px solid {{ $verdictColor }}30;">
                {{ $h2h['verdict'] }}
            </span>
        </div>
        @endif

        {{-- Side-by-side metrics --}}
        <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem; font-size:0.75rem; font-weight:600;">
            <div style="flex:0 0 120px;"></div>
            <div style="flex:1; text-align:center; color:#7c3aed;"><i class="fa-light fa-user"></i> {{ $myInfo['title'] ?? __('You') }}</div>
            <div style="flex:1; text-align:center; color:#dc2626;"><i class="fa-light fa-user-secret"></i> {{ $ci['title'] ?? __('Them') }}</div>
        </div>

        @php
            $h2hRows = [
                ['label' => 'Subscribers', 'you' => $myInfo['subscribers'] ?? 0, 'them' => $ci['subscribers'] ?? 0, 'format' => 'number'],
                ['label' => 'Avg Views', 'you' => $myMetrics['avg_views'] ?? 0, 'them' => $cm['avg_views'] ?? 0, 'format' => 'number'],
                ['label' => 'Engagement', 'you' => $myMetrics['engagement_rate'] ?? 0, 'them' => $cm['engagement_rate'] ?? 0, 'format' => 'pct'],
                ['label' => 'Upload Freq', 'you' => $myMetrics['posting_frequency'] ?? 0, 'them' => $cm['posting_frequency'] ?? 0, 'format' => 'freq'],
                ['label' => 'View Trend', 'you' => $myMetrics['view_trend_pct'] ?? 0, 'them' => $cm['view_trend_pct'] ?? 0, 'format' => 'trend'],
            ];
        @endphp
        @foreach($h2hRows as $row)
        @php
            $youVal = $row['you'];
            $themVal = $row['them'];
            $youWins = $youVal > $themVal;
            $themWins = $themVal > $youVal;
            $youDisplay = match($row['format']) {
                'pct' => number_format($youVal, 2) . '%',
                'freq' => $youVal . '/mo',
                'trend' => ($youVal >= 0 ? '+' : '') . number_format($youVal, 1) . '%',
                default => number_format($youVal),
            };
            $themDisplay = match($row['format']) {
                'pct' => number_format($themVal, 2) . '%',
                'freq' => $themVal . '/mo',
                'trend' => ($themVal >= 0 ? '+' : '') . number_format($themVal, 1) . '%',
                default => number_format($themVal),
            };
        @endphp
        <div class="aith-h2h-row">
            <div class="aith-h2h-label">{{ $row['label'] }}</div>
            <div class="aith-h2h-val aith-h2h-you {{ $youWins ? 'aith-h2h-winner' : '' }}">{{ $youDisplay }} @if($youWins)<i class="fa-light fa-crown" style="font-size:0.625rem; color:#7c3aed;"></i>@endif</div>
            <div class="aith-h2h-val aith-h2h-them {{ $themWins ? 'aith-h2h-winner' : '' }}">{{ $themDisplay }} @if($themWins)<i class="fa-light fa-crown" style="font-size:0.625rem; color:#dc2626;"></i>@endif</div>
        </div>
        @endforeach

        {{-- Win probability --}}
        @if(!empty($h2h['win_probability']))
        <div style="margin-top:1rem;">
            <div style="font-size:0.75rem; font-weight:600; color:#64748b; margin-bottom:0.375rem;">{{ __('Win Probability') }}</div>
            @php $winPct = (int) preg_replace('/[^0-9]/', '', $h2h['win_probability']); @endphp
            <div class="aith-win-meter">
                <div class="aith-win-fill" style="width:{{ min($winPct, 100) }}%; background:linear-gradient(90deg,{{ $winPct >= 50 ? '#7c3aed,#a78bfa' : '#ef4444,#f87171' }});"></div>
            </div>
            <div style="font-size:0.75rem; color:#475569; margin-top:0.25rem;">{{ $h2h['win_probability'] }}</div>
        </div>
        @endif

        {{-- Advantages --}}
        <div class="aith-grid-2" style="margin-top:1rem;">
            @if(!empty($h2h['your_advantages']))
            <div>
                <div style="font-size:0.75rem; font-weight:600; color:#059669; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-shield-check"></i> {{ __('Your Advantages') }}</div>
                @foreach($h2h['your_advantages'] as $adv)
                <div class="aith-list-icon-item"><i class="fa-light fa-circle-check" style="color:#10b981;"></i> {{ $adv }}</div>
                @endforeach
            </div>
            @endif
            @if(!empty($h2h['their_advantages']))
            <div>
                <div style="font-size:0.75rem; font-weight:600; color:#dc2626; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-shield-exclamation"></i> {{ __('Their Advantages') }}</div>
                @foreach($h2h['their_advantages'] as $adv)
                <div class="aith-list-icon-item"><i class="fa-light fa-circle-xmark" style="color:#ef4444;"></i> {{ $adv }}</div>
                @endforeach
            </div>
            @endif
        </div>

        @if(!empty($h2h['key_battleground']))
        <div style="margin-top:0.75rem; padding:0.625rem 0.875rem; background:#f5f3ff; border:1px solid #ede9fe; border-radius:8px; font-size:0.8125rem; color:#6d28d9;">
            <i class="fa-light fa-crosshairs"></i> <strong>{{ __('Key Battleground:') }}</strong> {{ $h2h['key_battleground'] }}
        </div>
        @endif
    </div>
    @endif

    {{-- ========== 4. CATEGORY SCORES (3x2 grid) ========== --}}
    @if(isset($result['categories']) && count($result['categories']) > 0)
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-chart-pie"></i> {{ __('Competitor Breakdown') }}</h3>
            <div class="aith-section-desc">{{ __('Scored across 6 critical dimensions') }}</div>
        </div>
        <div class="aith-grid-3">
            @foreach($result['categories'] as $cat)
            @php
                $score = $cat['score'] ?? 0;
                $colorKey = $score >= 70 ? 'green' : ($score >= 40 ? 'yellow' : 'red');
                $barClass = 'aith-bar-' . $colorKey;
            @endphp
            <div class="aith-cat-card aith-cat-{{ $colorKey }}">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
                    <div class="aith-cat-icon aith-cat-icon-{{ $colorKey }}">
                        <i class="fa-light {{ $cat['icon'] ?? 'fa-chart-simple' }}"></i>
                    </div>
                    <span style="font-size:1.5rem; font-weight:700; color:{{ $score >= 70 ? '#10b981' : ($score >= 40 ? '#f59e0b' : '#ef4444') }};">{{ $score }}</span>
                </div>
                <div style="font-size:0.8125rem; font-weight:600; color:#1e293b; margin-bottom:0.375rem;">{{ $cat['name'] ?? '' }}</div>
                <div class="aith-bar-track" style="margin-bottom:0.375rem;">
                    <div class="aith-bar-value {{ $barClass }}" style="width:{{ $score }}%;"></div>
                </div>
                @if(!empty($cat['summary']))
                <p style="font-size:0.75rem; color:#64748b; margin:0; line-height:1.4;">{{ $cat['summary'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ========== 5. STEAL THEIR STRATEGY ========== --}}
    @if(!empty($sts))
    <div class="aith-card aith-card-accent-red">
        <div class="aith-section-header">
            <h3 class="aith-section-title" style="color:#dc2626;"><i class="fa-light fa-user-secret"></i> {{ __('Steal Their Strategy') }}</h3>
            <div class="aith-section-desc">{{ __('Intelligence extracted from their content patterns') }}</div>
        </div>

        {{-- Content Formula --}}
        @if(!empty($sts['content_formula']))
        <div style="padding:0.875rem; background:linear-gradient(135deg,#fef2f2,#fff7ed); border-radius:10px; border:1px solid #fecaca; margin-bottom:1rem;">
            <div style="font-size:0.6875rem; font-weight:600; color:#dc2626; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.375rem;"><i class="fa-light fa-flask"></i> {{ __('Content Formula') }}</div>
            <div style="font-size:0.8125rem; color:#1e293b; line-height:1.5;">{{ $sts['content_formula'] }}</div>
        </div>
        @endif

        {{-- Title Patterns --}}
        @if(!empty($sts['title_patterns']))
        <div style="margin-bottom:1rem;">
            <div style="font-size:0.6875rem; font-weight:600; color:#dc2626; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.5rem;"><i class="fa-light fa-text"></i> {{ __('Title Patterns') }}</div>
            @foreach($sts['title_patterns'] as $i => $pattern)
            <div style="display:flex; align-items:flex-start; gap:0.5rem; padding:0.5rem 0; {{ !$loop->last ? 'border-bottom:1px solid #f1f5f9;' : '' }}">
                <span style="width:20px; height:20px; border-radius:50%; background:#dc2626; color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.625rem; font-weight:700; flex-shrink:0;">{{ $i + 1 }}</span>
                <span style="font-size:0.8125rem; color:#475569; line-height:1.4;">{{ $pattern }}</span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Upload Strategy + Engagement Hooks + Thumbnail Style --}}
        <div class="aith-grid-3" style="margin-bottom:1rem;">
            @if(!empty($sts['upload_strategy']))
            <div class="aith-metric-card" style="text-align:left;">
                <div style="font-size:0.6875rem; font-weight:600; color:#64748b; text-transform:uppercase; margin-bottom:0.375rem;"><i class="fa-light fa-calendar-clock"></i> {{ __('Upload Strategy') }}</div>
                <div style="font-size:0.8125rem; color:#475569;">{{ $sts['upload_strategy'] }}</div>
            </div>
            @endif
            @if(!empty($sts['thumbnail_style']))
            <div class="aith-metric-card" style="text-align:left;">
                <div style="font-size:0.6875rem; font-weight:600; color:#64748b; text-transform:uppercase; margin-bottom:0.375rem;"><i class="fa-light fa-image"></i> {{ __('Thumbnail Style') }}</div>
                <div style="font-size:0.8125rem; color:#475569;">{{ $sts['thumbnail_style'] }}</div>
            </div>
            @endif
            @if(!empty($sts['engagement_hooks']))
            <div class="aith-metric-card" style="text-align:left;">
                <div style="font-size:0.6875rem; font-weight:600; color:#64748b; text-transform:uppercase; margin-bottom:0.375rem;"><i class="fa-light fa-hook"></i> {{ __('Engagement Hooks') }}</div>
                @foreach($sts['engagement_hooks'] as $hook)
                <div style="font-size:0.75rem; color:#475569; padding:0.125rem 0;">- {{ $hook }}</div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- What to Copy vs What to Avoid --}}
        <div class="aith-grid-2">
            @if(!empty($sts['what_to_copy']))
            <div>
                <div style="font-size:0.75rem; font-weight:600; color:#059669; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-circle-check"></i> {{ __('What to Copy') }}</div>
                @foreach($sts['what_to_copy'] as $item)
                <div class="aith-list-icon-item"><i class="fa-light fa-check" style="color:#10b981;"></i> {{ $item }}</div>
                @endforeach
            </div>
            @endif
            @if(!empty($sts['what_to_avoid']))
            <div>
                <div style="font-size:0.75rem; font-weight:600; color:#dc2626; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-circle-xmark"></i> {{ __('What to Avoid') }}</div>
                @foreach($sts['what_to_avoid'] as $item)
                <div class="aith-list-icon-item"><i class="fa-light fa-xmark" style="color:#ef4444;"></i> {{ $item }}</div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ========== 6. CONTENT GAP RADAR ========== --}}
    @if(!empty($cgr))
    <div class="aith-card aith-card-accent-orange">
        <div class="aith-section-header">
            <h3 class="aith-section-title" style="color:#f97316;"><i class="fa-light fa-radar"></i> {{ __('Content Gap Radar') }}</h3>
            <div class="aith-section-desc">{{ __('Topics and formats they\'re missing — your opportunity') }}</div>
        </div>
        @foreach($cgr as $i => $gap)
        <div class="aith-gap-item">
            <div class="aith-gap-num">{{ $i + 1 }}</div>
            <div class="aith-gap-info">
                <div class="aith-gap-title">{{ $gap['gap'] ?? '' }}</div>
                @if(!empty($gap['example_title']))
                <div class="aith-gap-desc"><i class="fa-light fa-lightbulb"></i> {{ __('Try:') }} "{{ $gap['example_title'] }}"</div>
                @endif
                <div class="aith-gap-meta">
                    @if(!empty($gap['opportunity_size']))
                    @php
                        $oppClass = match($gap['opportunity_size']) { 'high' => 'aith-badge-high', 'medium' => 'aith-badge-medium', default => 'aith-badge-low' };
                    @endphp
                    <span class="aith-badge {{ $oppClass }}">{{ $gap['opportunity_size'] }} opp.</span>
                    @endif
                    @if(!empty($gap['difficulty']))
                    <span class="aith-badge aith-badge-ghost">{{ $gap['difficulty'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ========== 7. WEAKNESS EXPLOITS ========== --}}
    @if(!empty($we))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title" style="color:#dc2626;"><i class="fa-light fa-crosshairs"></i> {{ __('Weakness Exploits') }}</h3>
            <div class="aith-section-desc">{{ __('Their vulnerabilities — and how to attack each one') }}</div>
        </div>
        @foreach($we as $i => $exploit)
        <div class="aith-exploit-item">
            <div class="aith-exploit-icon"><i class="fa-light fa-crosshairs"></i></div>
            <div class="aith-exploit-info">
                <div class="aith-exploit-title">{{ $exploit['weakness'] ?? '' }}</div>
                <div class="aith-exploit-text">{{ $exploit['how_to_exploit'] ?? '' }}</div>
                @if(!empty($exploit['potential_impact']))
                <div style="margin-top:0.375rem;">
                    <span class="aith-badge {{ $exploit['potential_impact'] === 'high' ? 'aith-badge-high' : 'aith-badge-medium' }}">{{ $exploit['potential_impact'] }} impact</span>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ========== 8. CONTENT INTELLIGENCE ========== --}}
    @if(!empty($ca))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-film"></i> {{ __('Content Intelligence') }}</h3>
            <div class="aith-section-desc">{{ __('How they structure and schedule their content') }}</div>
        </div>

        @if(!empty($ca['content_pillars']))
        <div style="margin-bottom:1rem;">
            <div style="font-size:0.75rem; font-weight:600; color:#7c3aed; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-columns-3"></i> {{ __('Content Pillars') }}</div>
            <div class="aith-tags-wrap">
                @foreach($ca['content_pillars'] as $pillar)
                <span class="aith-tag">{{ $pillar }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($ca['best_performing_topics']))
        <div style="margin-bottom:1rem;">
            <div style="font-size:0.75rem; font-weight:600; color:#059669; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-arrow-up"></i> {{ __('Best Performing Topics') }}</div>
            <div class="aith-tags-wrap">
                @foreach($ca['best_performing_topics'] as $topic)
                <span class="aith-tag aith-tag-green">{{ $topic }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($ca['format_breakdown']))
        <div style="margin-bottom:1rem;">
            <div style="font-size:0.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-chart-bar"></i> {{ __('Format Breakdown') }}</div>
            @foreach($ca['format_breakdown'] as $fmt)
            <div class="aith-bar-wrap">
                <div class="aith-bar-label">
                    <span>{{ $fmt['format'] ?? '' }}</span>
                    <span>{{ $fmt['percentage'] ?? 0 }}% — {{ $fmt['avg_performance'] ?? 'average' }}</span>
                </div>
                <div class="aith-bar-track">
                    @php
                        $fmtBarClass = match($fmt['avg_performance'] ?? 'average') {
                            'above average' => 'aith-bar-green',
                            'below average' => 'aith-bar-red',
                            default => 'aith-bar-purple',
                        };
                    @endphp
                    <div class="aith-bar-value {{ $fmtBarClass }}" style="width:{{ min($fmt['percentage'] ?? 0, 100) }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="aith-grid-2">
            @if(!empty($ca['optimal_length']))
            <div class="aith-metric-card" style="text-align:left;">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.375rem;">
                    <i class="fa-light fa-timer" style="color:#7c3aed; font-size:1rem;"></i>
                    <span style="font-size:0.75rem; font-weight:600; color:#1e293b;">{{ __('Optimal Video Length') }}</span>
                </div>
                <div style="font-size:0.875rem; color:#475569; font-weight:500;">{{ $ca['optimal_length'] }}</div>
            </div>
            @endif
            @if(!empty($ca['posting_schedule']))
            <div class="aith-metric-card" style="text-align:left;">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.375rem;">
                    <i class="fa-light fa-clock" style="color:#7c3aed; font-size:1rem;"></i>
                    <span style="font-size:0.75rem; font-weight:600; color:#1e293b;">{{ __('Posting Schedule') }}</span>
                </div>
                <div style="font-size:0.875rem; color:#475569; font-weight:500;">{{ $ca['posting_schedule'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ========== 9. KEY METRICS DASHBOARD ========== --}}
    @if(!empty($cm))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-chart-mixed"></i> {{ __('Competitor Metrics') }}</h3>
            <div class="aith-section-desc">{{ __('Computed from the last 20 videos — real data') }}</div>
        </div>
        <div class="aith-grid-4">
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['avg_views'] ?? 0) }}</div>
                <div class="aith-metric-label">{{ __('Avg Views') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['engagement_rate'] ?? 0, 2) }}%</div>
                <div class="aith-metric-label">{{ __('Engagement Rate') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['views_to_subs_ratio'] ?? 0, 1) }}%</div>
                <div class="aith-metric-label">{{ __('Views / Subs') }}</div>
            </div>
            <div class="aith-metric-card">
                @php $trend = $cm['view_trend_pct'] ?? 0; @endphp
                <div class="aith-metric-value {{ $trend >= 5 ? 'aith-trend-up' : ($trend <= -5 ? 'aith-trend-down' : 'aith-trend-stable') }}">
                    <i class="fa-light {{ $trend >= 5 ? 'fa-arrow-trend-up' : ($trend <= -5 ? 'fa-arrow-trend-down' : 'fa-minus') }}"></i>
                    {{ ($trend >= 0 ? '+' : '') . number_format($trend, 1) }}%
                </div>
                <div class="aith-metric-label">{{ __('View Trend') }}</div>
            </div>
        </div>
        <div class="aith-grid-4" style="margin-top:0.75rem;">
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['est_monthly_views'] ?? 0) }}</div>
                <div class="aith-metric-label">{{ __('Est. Monthly Views') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['avg_view_velocity'] ?? 0) }}</div>
                <div class="aith-metric-label">{{ __('Views / Day') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ $cm['posting_frequency'] ?? 0 }}/mo</div>
                <div class="aith-metric-label">{{ __('Upload Freq') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ round(($cm['avg_duration_seconds'] ?? 0) / 60) }}m</div>
                <div class="aith-metric-label">{{ __('Avg Duration') }}</div>
            </div>
        </div>
    </div>
    @endif

    {{-- ========== 10. TOP & BOTTOM VIDEOS ========== --}}
    @if(!empty($cm['top_videos']) || !empty($cm['bottom_videos']))
    <div class="aith-grid-2">
        @if(!empty($cm['top_videos']))
        <div class="aith-card" style="margin-bottom:0;">
            <h3 class="aith-section-title" style="color:#059669;"><i class="fa-light fa-trophy"></i> {{ __('Their Best Videos') }}</h3>
            @foreach($cm['top_videos'] as $i => $video)
            <div class="aith-video-perf">
                <div class="aith-video-rank aith-rank-top">#{{ $i + 1 }}</div>
                @if(!empty($video['thumbnail']))
                <img src="{{ $video['thumbnail'] }}" alt="" loading="lazy">
                @endif
                <div class="aith-video-perf-info">
                    <div class="aith-video-perf-title" title="{{ $video['title'] ?? '' }}">{{ $video['title'] ?? '' }}</div>
                    <div class="aith-video-perf-stats">
                        <i class="fa-light fa-eye"></i> {{ number_format($video['views'] ?? 0) }} &nbsp;
                        <i class="fa-light fa-thumbs-up"></i> {{ number_format($video['likes'] ?? 0) }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        @if(!empty($cm['bottom_videos']))
        <div class="aith-card" style="margin-bottom:0;">
            <h3 class="aith-section-title" style="color:#dc2626;"><i class="fa-light fa-chart-line-down"></i> {{ __('Their Worst Videos') }}</h3>
            @foreach($cm['bottom_videos'] as $i => $video)
            <div class="aith-video-perf">
                <div class="aith-video-rank aith-rank-bottom">#{{ $i + 1 }}</div>
                @if(!empty($video['thumbnail']))
                <img src="{{ $video['thumbnail'] }}" alt="" loading="lazy">
                @endif
                <div class="aith-video-perf-info">
                    <div class="aith-video-perf-title" title="{{ $video['title'] ?? '' }}">{{ $video['title'] ?? '' }}</div>
                    <div class="aith-video-perf-stats">
                        <i class="fa-light fa-eye"></i> {{ number_format($video['views'] ?? 0) }} &nbsp;
                        <i class="fa-light fa-thumbs-up"></i> {{ number_format($video['likes'] ?? 0) }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- ========== 11. MONETIZATION INTEL ========== --}}
    @if(!empty($mi))
    <div class="aith-card aith-card-accent aith-card-accent-amber">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-sack-dollar"></i> {{ __('Monetization Intel') }}</h3>
            <div class="aith-section-desc">{{ __('Estimated revenue and sponsorship intelligence') }}</div>
        </div>
        <div class="aith-grid-3" style="margin-bottom:1rem;">
            @if(!empty($mi['estimated_monthly_revenue']))
            <div class="aith-metric-card">
                <div class="aith-metric-value" style="color:#059669;">{{ $mi['estimated_monthly_revenue'] }}</div>
                <div class="aith-metric-label">{{ __('Monthly Revenue') }}</div>
            </div>
            @endif
            @if(!empty($mi['estimated_cpm_range']))
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ $mi['estimated_cpm_range'] }}</div>
                <div class="aith-metric-label">{{ __('CPM Range') }}</div>
            </div>
            @endif
            @if(!empty($mi['estimated_sponsorship_rate']))
            <div class="aith-metric-card">
                <div class="aith-metric-value" style="color:#7c3aed;">{{ $mi['estimated_sponsorship_rate'] }}</div>
                <div class="aith-metric-label">{{ __('Sponsorship Rate') }}</div>
            </div>
            @endif
        </div>
        <div class="aith-grid-2">
            @if(!empty($mi['sponsorship_likelihood']))
            @php
                $spClass = match($mi['sponsorship_likelihood']) {
                    'High' => 'aith-health-good',
                    'Medium' => 'aith-health-avg',
                    default => 'aith-health-poor',
                };
            @endphp
            <div class="aith-metric-card" style="text-align:left;">
                <div style="font-size:0.6875rem; font-weight:600; color:#64748b; text-transform:uppercase; margin-bottom:0.375rem;">{{ __('Sponsorship Likelihood') }}</div>
                <span class="aith-health-item {{ $spClass }}" style="display:inline-flex;">{{ $mi['sponsorship_likelihood'] }}</span>
            </div>
            @endif
            @if(!empty($mi['revenue_streams']))
            <div>
                <div style="font-size:0.75rem; font-weight:600; color:#059669; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;">{{ __('Revenue Streams') }}</div>
                @foreach($mi['revenue_streams'] as $stream)
                <div class="aith-list-icon-item"><i class="fa-light fa-circle-dollar" style="color:#059669;"></i> {{ $stream }}</div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ========== 12. SWOT ANALYSIS ========== --}}
    @if(!empty($sw))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-grid-2"></i> {{ __('SWOT Analysis') }}</h3>
            <div class="aith-section-desc">{{ __('Strategic overview of the competitor\'s position') }}</div>
        </div>
        <div class="aith-grid-2">
            @if(!empty($sw['strengths']))
            <div class="aith-sw-box aith-sw-strength">
                <h5><i class="fa-light fa-shield-check"></i> {{ __('Strengths') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($sw['strengths'] as $item)
                    <li>{{ is_array($item) ? ($item['text'] ?? $item['title'] ?? '') : $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(!empty($sw['weaknesses']))
            <div class="aith-sw-box aith-sw-weakness">
                <h5><i class="fa-light fa-triangle-exclamation"></i> {{ __('Weaknesses') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($sw['weaknesses'] as $item)
                    <li>{{ is_array($item) ? ($item['text'] ?? $item['title'] ?? '') : $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(!empty($sw['opportunities']))
            <div class="aith-sw-box aith-sw-opportunity">
                <h5><i class="fa-light fa-lightbulb"></i> {{ __('Opportunities') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($sw['opportunities'] as $item)
                    <li>{{ is_array($item) ? ($item['text'] ?? $item['title'] ?? '') : $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(!empty($sw['threats']))
            <div class="aith-sw-box aith-sw-threat">
                <h5><i class="fa-light fa-shield-exclamation"></i> {{ __('Threats') }}</h5>
                <ul class="aith-sw-list">
                    @foreach($sw['threats'] as $item)
                    <li>{{ is_array($item) ? ($item['text'] ?? $item['title'] ?? '') : $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ========== 13. BATTLE PLAN ========== --}}
    @if(!empty($bp))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title" style="color:#dc2626;"><i class="fa-light fa-chess"></i> {{ __('Battle Plan') }}</h3>
            <div class="aith-section-desc">{{ __('12-week tactical plan to outperform this competitor') }}</div>
        </div>
        @foreach($bp as $i => $phase)
        <div class="aith-phase aith-phase-{{ min($i + 1, 4) }}">
            <div class="aith-phase-name">{{ $phase['phase'] ?? '' }}</div>
            @if(!empty($phase['goal']))
            <div class="aith-phase-goal"><i class="fa-light fa-bullseye"></i> {{ $phase['goal'] }}</div>
            @endif
            @if(!empty($phase['actions']))
            <ul class="aith-phase-actions">
                @foreach($phase['actions'] as $action)
                <li>{{ $action }}</li>
                @endforeach
            </ul>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- ========== 14. RECOMMENDATIONS (Collapsible) ========== --}}
    @if(!empty($result['recommendations']))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-lightbulb"></i> {{ __('Tactical Recommendations') }}</h3>
            <div class="aith-section-desc">{{ __('Click any recommendation to expand full details') }}</div>
        </div>
        @foreach($result['recommendations'] as $idx => $rec)
        @php
            $priority = is_array($rec) ? ($rec['priority'] ?? 'low') : 'medium';
            $priClass = $priority === 'high' ? 'aith-badge-high' : ($priority === 'medium' ? 'aith-badge-medium' : 'aith-badge-low');
            $catBadge = is_array($rec) && !empty($rec['category']) ? $rec['category'] : '';
            $recTitle = is_array($rec) && isset($rec['title']) ? $rec['title'] : 'Recommendation';
            $recDesc = is_array($rec) ? ($rec['description'] ?? $rec['text'] ?? '') : $rec;
        @endphp
        <div x-data="{ open: false }">
            <div class="aith-collapse-header" :class="{ 'aith-collapse-open': open }" @click="open = !open">
                <div class="aith-collapse-left">
                    <span class="aith-badge {{ $priClass }}">{{ strtoupper($priority) }}</span>
                    <div style="min-width:0;">
                        <div class="aith-collapse-title">{{ $recTitle }}</div>
                        <div class="aith-collapse-desc" x-show="!open">{{ Str::limit($recDesc, 80) }}</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    @if($catBadge)
                    <span class="aith-badge aith-badge-ghost">{{ $catBadge }}</span>
                    @endif
                    <i class="fa-light fa-chevron-down aith-collapse-chevron"></i>
                </div>
            </div>
            <div class="aith-collapse-body" :class="{ 'aith-collapse-open': open }">
                <div class="aith-result-text" style="line-height:1.6;">{{ $recDesc }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    </div>{{-- /competitor-results-container --}}

    @endif

    {{-- PDF Export Script --}}
    <script>
    function aithLoadScript(url) {
        return new Promise(function(ok, fail) {
            if (document.querySelector('script[src="'+url+'"]')) { ok(); return; }
            var s = document.createElement('script');
            s.src = url;
            s.onload = ok;
            s.onerror = function() { fail(new Error('Failed to load ' + url)); };
            document.head.appendChild(s);
        });
    }

    function aithImgToDataUrl(blob, maxDim) {
        return new Promise(function(resolve, reject) {
            var url = URL.createObjectURL(blob);
            var img = new Image();
            img.onload = function() {
                var r = Math.min(maxDim / img.width, maxDim / img.height, 1);
                var c = document.createElement('canvas');
                c.width = Math.round(img.width * r);
                c.height = Math.round(img.height * r);
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                URL.revokeObjectURL(url);
                resolve(c.toDataURL('image/jpeg', 0.5));
            };
            img.onerror = function() { URL.revokeObjectURL(url); reject(); };
            img.src = url;
        });
    }

    async function aithExportCompetitorPdf(btn) {
        var origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-light fa-spinner-third fa-spin"></i> Generating...';
        btn.disabled = true;
        function done() { btn.innerHTML = origHtml; btn.disabled = false; }

        var el = document.getElementById('competitor-results-container');
        if (!el) { done(); return; }

        var imgBackups = [];

        try {
            await Promise.all([
                typeof html2canvas !== 'undefined' ? Promise.resolve() :
                    aithLoadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'),
                typeof window.jspdf !== 'undefined' ? Promise.resolve() :
                    aithLoadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js')
            ]);

            var imgs = el.querySelectorAll('img');
            await Promise.all(Array.from(imgs).map(async function(img) {
                var src = img.getAttribute('src') || '';
                if (!src.startsWith('http') || src.startsWith(location.origin)) return;
                try {
                    var resp = await fetch(src, { mode: 'cors' });
                    var blob = await resp.blob();
                    var dataUrl = await aithImgToDataUrl(blob, 160);
                    imgBackups.push({ el: img, src: src });
                    img.src = dataUrl;
                } catch(e) {
                    imgBackups.push({ el: img, src: src, hidden: true });
                    img.style.visibility = 'hidden';
                }
            }));

            var canvas = await html2canvas(el, {
                scale: 1.5,
                logging: false,
                backgroundColor: '#f1f5f9',
                imageTimeout: 0
            });

            imgBackups.forEach(function(b) {
                b.el.src = b.src;
                if (b.hidden) b.el.style.visibility = '';
            });
            imgBackups = [];

            var pdf = new window.jspdf.jsPDF('p', 'mm', 'a4');
            var pw = pdf.internal.pageSize.getWidth();
            var ph = pdf.internal.pageSize.getHeight();
            var m = 8;
            var w = pw - m * 2;
            var h = (canvas.height * w) / canvas.width;
            var usable = ph - m * 2;
            var pages = Math.ceil(h / usable);
            var jpgData = canvas.toDataURL('image/jpeg', 0.55);

            for (var i = 0; i < pages; i++) {
                if (i > 0) pdf.addPage();
                pdf.addImage(jpgData, 'JPEG', m, m - (i * usable), w, h);
            }

            var nameEl = el.querySelector('.aith-channel-name') || el.querySelector('h3');
            var name = (nameEl ? nameEl.textContent : 'competitor').trim();
            pdf.save('competitor-' + name.replace(/[^a-z0-9]/gi, '-').toLowerCase() + '.pdf');
        } catch(e) {
            console.error('PDF export error:', e);
            imgBackups.forEach(function(b) {
                b.el.src = b.src;
                if (b.hidden) b.el.style.visibility = '';
            });
            alert('PDF export failed: ' + (e.message || 'Unknown error. Please try again.'));
        }
        done();
    }
    </script>

    {{-- History --}}
    @include('appaitools::livewire.partials._tool-history')

</div>
</div>
