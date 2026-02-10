<div>
@include('appaitools::livewire.partials._tool-base')

<div class="aith-tool" x-data="{
    progress: 0,
    step: 0,
    tipIndex: 0,
    tips: [
        'Channels that audit monthly grow 3x faster',
        'Consistent upload schedule boosts algorithm favor by 40%',
        'Engagement rate matters more than subscriber count',
        'SEO-optimized channels get 70% more organic traffic',
        'First 48 hours determine a video\'s lifetime performance'
    ],
    steps: [
        { label: 'Fetching Channel', icon: 'fa-download' },
        { label: 'Analyzing SEO', icon: 'fa-magnifying-glass' },
        { label: 'Checking Content', icon: 'fa-film' },
        { label: 'Measuring Engagement', icon: 'fa-heart' },
        { label: 'Evaluating Growth', icon: 'fa-chart-line' },
        { label: 'Building Roadmap', icon: 'fa-map' }
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
        <h2 class="aith-card-title"><span class="aith-emoji">📋</span> {{ __('Channel Audit Pro') }}</h2>

        <div class="aith-feature-box aith-feat-emerald">
            <button type="button" class="aith-feature-toggle" onclick="aithToggleFeature(this)">
                <span>💡</span> {{ __('What can this tool do?') }}
                <i class="fa-light fa-chevron-down aith-chevron"></i>
            </button>
            <div class="aith-feature-content">
                <div class="aith-feature-grid">
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Comprehensive SEO health check') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Content strategy evaluation') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Engagement funnel analysis') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('Competitor benchmark comparison') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('SWOT analysis & monetization insights') }}</div>
                    <div class="aith-feature-item"><i class="fa-light fa-check"></i> {{ __('90-day action plan with priorities') }}</div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="aith-error"><i class="fa-light fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        <div class="aith-form-group">
            <label class="aith-label">{{ __('Channel URL') }}</label>
            <input type="url" wire:model="channelUrl" class="aith-input" placeholder="https://youtube.com/@channel">
            @error('channelUrl') <div class="aith-field-error">{{ $message }}</div> @enderror
            <div style="font-size: 0.6875rem; color: #94a3b8; margin-top: 0.375rem;">{{ __('Supports youtube.com/@handle, /channel/ID, or /c/name formats') }}</div>
        </div>

        <button wire:click="audit" class="aith-btn-primary" {{ $isLoading ? 'disabled' : '' }}
            @click="if(!$wire.isLoading) startLoading()">
            <span wire:loading.remove wire:target="audit">
                <i class="fa-light fa-clipboard-check"></i> {{ __('Run Audit') }}
            </span>
            <span wire:loading wire:target="audit">
                <i class="fa-light fa-spinner-third fa-spin"></i> {{ __('Auditing...') }}
            </span>
        </button>

        {{-- Loading State --}}
        <div x-show="$wire.isLoading" x-cloak class="aith-loading" x-transition>
            <div class="aith-loading-header">
                <div class="aith-loading-title"><span class="aith-emoji">📋</span> {{ __('Running audit...') }}</div>
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
        $ci = $result['channel_info'] ?? [];
        $cs = $result['channel_stats'] ?? [];
        $cm = $result['computed_metrics'] ?? [];
        $ch = $result['channel_health'] ?? [];
        $ca = $result['content_analysis'] ?? [];
        $ga = $result['growth_assessment'] ?? [];
        $mi = $result['monetization_insights'] ?? [];
        $sw = $result['swot'] ?? [];
        $qw = $result['quick_wins'] ?? [];
        $ef = $result['engagement_funnel'] ?? [];
        $cb = $result['competitor_benchmarks'] ?? [];
        $grade = $result['grade'] ?? '';
        $gradeClass = match(true) {
            str_starts_with($grade, 'A') => 'aith-grade-a',
            str_starts_with($grade, 'B') => 'aith-grade-b',
            str_starts_with($grade, 'C') => 'aith-grade-c',
            default => 'aith-grade-d',
        };
        $channelAge = '';
        if (!empty($cs['published_at'])) {
            try {
                $created = new \DateTime($cs['published_at']);
                $now = new \DateTime();
                $diff = $now->diff($created);
                if ($diff->y > 0) $channelAge = $diff->y . 'y ' . $diff->m . 'mo';
                else $channelAge = $diff->m . ' months';
            } catch (\Exception $e) {}
        }
    @endphp

    <div id="audit-results-container">

    {{-- ========== 1. CHANNEL PROFILE + SCORE HEADER ========== --}}
    <div class="aith-card">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; margin-bottom:1.25rem;">
            <h2 class="aith-card-title" style="margin:0"><span class="aith-emoji">📊</span> {{ __('Audit Results') }}</h2>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <button class="aith-btn-pdf" onclick="aithExportPdf(this)">
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
            <img src="{{ $ci['thumbnail'] }}" alt="" class="aith-channel-avatar">
            @endif
            <div class="aith-channel-meta">
                <h3 class="aith-channel-name">{{ $ci['title'] ?? 'Channel' }}</h3>
                <div class="aith-channel-stats">
                    <span class="aith-channel-stat"><i class="fa-light fa-users"></i> <strong>{{ number_format($ci['subscribers'] ?? 0) }}</strong> {{ __('subs') }}</span>
                    <span class="aith-channel-stat"><i class="fa-light fa-eye"></i> <strong>{{ number_format($cs['total_views'] ?? 0) }}</strong> {{ __('views') }}</span>
                    <span class="aith-channel-stat"><i class="fa-light fa-video"></i> <strong>{{ number_format($cs['video_count'] ?? 0) }}</strong> {{ __('videos') }}</span>
                    @if(!empty($cs['country']))
                    <span class="aith-channel-stat"><i class="fa-light fa-globe"></i> {{ $cs['country'] }}</span>
                    @endif
                    @if($channelAge)
                    <span class="aith-channel-stat"><i class="fa-light fa-calendar"></i> {{ $channelAge }}</span>
                    @endif
                </div>
            </div>
            @if(!empty($result['detected_niche']))
            <span class="aith-badge aith-badge-purple" style="font-size:0.75rem; padding:0.375rem 0.75rem;">{{ $result['detected_niche'] }}</span>
            @endif
        </div>

        {{-- Score + Grade --}}
        @if(isset($result['overall_score']))
        <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; padding:1rem; background:linear-gradient(135deg,#f8fafc,#f1f5f9); border-radius:14px; border:1px solid #e2e8f0;">
            <div class="aith-score-gauge">
                <svg viewBox="0 0 120 120">
                    <circle class="aith-gauge-bg" cx="60" cy="60" r="50"/>
                    <circle class="aith-gauge-fill" cx="60" cy="60" r="50"
                        stroke="{{ $result['overall_score'] >= 70 ? '#10b981' : ($result['overall_score'] >= 40 ? '#f59e0b' : '#ef4444') }}"
                        stroke-dasharray="314"
                        stroke-dashoffset="{{ 314 - (314 * $result['overall_score'] / 100) }}"/>
                </svg>
                <div class="aith-score-val" style="color: {{ $result['overall_score'] >= 70 ? '#10b981' : ($result['overall_score'] >= 40 ? '#f59e0b' : '#ef4444') }}">{{ $result['overall_score'] }}</div>
            </div>
            @if($grade)
            <div class="aith-grade {{ $gradeClass }}">{{ $grade }}</div>
            @endif
            <div style="flex:1; min-width:200px;">
                <div style="font-size:1.125rem; font-weight:700; color:#1e293b;">{{ __('Overall Score') }}</div>
                @if(isset($result['overall_summary']))
                <p style="font-size:0.8125rem; color:#64748b; margin:0.375rem 0 0; line-height:1.5;">{{ $result['overall_summary'] }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- ========== 2. QUICK WINS BANNER ========== --}}
    @if(!empty($qw))
    <div class="aith-qw-banner">
        <div class="aith-qw-title"><i class="fa-light fa-bolt"></i> {{ __('Quick Wins — Do These This Week') }}</div>
        <div class="aith-qw-grid">
            @foreach($qw as $win)
            <div class="aith-qw-item">
                <div class="aith-qw-icon"><i class="fa-light {{ $win['icon'] ?? 'fa-bolt' }}"></i></div>
                <div class="aith-qw-action">{{ $win['action'] ?? '' }}</div>
                <div class="aith-qw-impact">
                    {{ $win['expected_impact'] ?? '' }}
                    @if(!empty($win['effort']))
                    <span class="aith-qw-effort">{{ $win['effort'] }} effort</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ========== 3. KEY TAKEAWAY ========== --}}
    @if(!empty($result['key_takeaway']))
    <div class="aith-takeaway">
        <div class="aith-takeaway-label"><i class="fa-light fa-star"></i> {{ __('Key Takeaway') }}</div>
        <div class="aith-takeaway-text">{{ $result['key_takeaway'] }}</div>
    </div>
    @endif

    {{-- ========== 4. CATEGORY SCORES (3x2 enhanced grid) ========== --}}
    @if(isset($result['categories']) && count($result['categories']) > 0)
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-chart-pie"></i> {{ __('Performance Breakdown') }}</h3>
            <div class="aith-section-desc">{{ __('Scored across 6 critical dimensions based on your channel data') }}</div>
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

    {{-- ========== 5. CHANNEL HEALTH INDICATORS ========== --}}
    @if(!empty($ch))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-heart-pulse"></i> {{ __('Channel Health Check') }}</h3>
            <div class="aith-section-desc">{{ __('Quick diagnostic of key channel signals') }}</div>
        </div>
        <div class="aith-health-grid">
            @php
                $healthItems = [
                    'upload_consistency' => ['label' => 'Upload Consistency', 'icon' => 'fa-calendar-check'],
                    'audience_retention_signal' => ['label' => 'Audience Retention', 'icon' => 'fa-users'],
                    'viral_potential' => ['label' => 'Viral Potential', 'icon' => 'fa-fire'],
                    'seo_optimization' => ['label' => 'SEO', 'icon' => 'fa-magnifying-glass'],
                    'thumbnail_quality' => ['label' => 'Thumbnails', 'icon' => 'fa-image'],
                    'title_effectiveness' => ['label' => 'Titles', 'icon' => 'fa-heading'],
                ];
                $goodValues = ['Excellent', 'Strong', 'High', 'Well Optimized', 'Professional'];
                $poorValues = ['Poor', 'Weak', 'Low', 'Needs Work', 'Needs Improvement'];
            @endphp
            @foreach($healthItems as $key => $item)
                @if(isset($ch[$key]))
                @php
                    $val = $ch[$key];
                    $hClass = in_array($val, $goodValues) ? 'aith-health-good' : (in_array($val, $poorValues) ? 'aith-health-poor' : 'aith-health-avg');
                    $hIcon = in_array($val, $goodValues) ? 'fa-circle-check' : (in_array($val, $poorValues) ? 'fa-circle-xmark' : 'fa-circle-minus');
                @endphp
                <div class="aith-health-item {{ $hClass }}">
                    <i class="fa-light {{ $hIcon }}"></i>
                    <span>{{ $item['label'] }}: <strong>{{ $val }}</strong></span>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- ========== 6. KEY METRICS DASHBOARD ========== --}}
    @if(!empty($cm))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-chart-mixed"></i> {{ __('Key Metrics') }}</h3>
            <div class="aith-section-desc">{{ __('Computed from the last 20 videos — real data, not estimates') }}</div>
        </div>
        <div class="aith-grid-4">
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['views_to_subs_ratio'] ?? 0, 1) }}%</div>
                <div class="aith-metric-label">{{ __('Views / Subs') }}</div>
                <div class="aith-metric-sub">{{ __('viral potential') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['engagement_rate'] ?? 0, 2) }}%</div>
                <div class="aith-metric-label">{{ __('Engagement Rate') }}</div>
                <div class="aith-metric-sub">{{ __('likes + comments / views') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['like_to_view_ratio'] ?? 0, 2) }}%</div>
                <div class="aith-metric-label">{{ __('Like / View') }}</div>
                <div class="aith-metric-sub">{{ __('content quality') }}</div>
            </div>
            <div class="aith-metric-card">
                @php $trend = $cm['view_trend_pct'] ?? 0; @endphp
                <div class="aith-metric-value {{ $trend >= 5 ? 'aith-trend-up' : ($trend <= -5 ? 'aith-trend-down' : 'aith-trend-stable') }}">
                    <i class="fa-light {{ $trend >= 5 ? 'fa-arrow-trend-up' : ($trend <= -5 ? 'fa-arrow-trend-down' : 'fa-minus') }}"></i>
                    {{ ($trend >= 0 ? '+' : '') . number_format($trend, 1) }}%
                </div>
                <div class="aith-metric-label">{{ __('View Trend') }}</div>
                <div class="aith-metric-sub">{{ __('newest vs oldest half') }}</div>
            </div>
        </div>

        <div class="aith-grid-4" style="margin-top:0.75rem;">
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['est_monthly_views'] ?? 0) }}</div>
                <div class="aith-metric-label">{{ __('Est. Monthly Views') }}</div>
                <div class="aith-metric-sub">{{ __('avg × frequency') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ number_format($cm['avg_view_velocity'] ?? 0) }}</div>
                <div class="aith-metric-label">{{ __('Views / Day') }}</div>
                <div class="aith-metric-sub">{{ __('avg velocity') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ $cm['posting_frequency'] ?? 0 }}/mo</div>
                <div class="aith-metric-label">{{ __('Upload Frequency') }}</div>
                <div class="aith-metric-sub">{{ __('every ') }}{{ number_format($cm['avg_days_between_uploads'] ?? 0, 0) }}{{ __(' days') }}</div>
            </div>
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ round(($cm['avg_duration_seconds'] ?? 0) / 60) }}m</div>
                <div class="aith-metric-label">{{ __('Avg Duration') }}</div>
                <div class="aith-metric-sub">±{{ number_format($cm['upload_consistency_days'] ?? 0, 0) }}d {{ __('consistency') }}</div>
            </div>
        </div>
    </div>
    @endif

    {{-- ========== 7. ENGAGEMENT FUNNEL ========== --}}
    @if(!empty($ef))
    <div class="aith-card aith-card-accent aith-card-accent-purple">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-filter"></i> {{ __('Engagement Funnel') }}</h3>
            <div class="aith-section-desc">{{ __('How viewers convert through the engagement pipeline') }}</div>
        </div>
        <div class="aith-funnel">
            @php
                $fViews = $cm['avg_views'] ?? 1;
                $fLikes = $cm['avg_likes'] ?? 0;
                $fComments = $cm['avg_comments'] ?? 0;
                $maxVal = max($fViews, 1);
                $likeW = min(max(($fLikes / $maxVal) * 100, 8), 100);
                $commentW = min(max(($fComments / $maxVal) * 100, 5), 100);
            @endphp
            <div class="aith-funnel-step">
                <div class="aith-funnel-bar-wrap">
                    <div class="aith-funnel-label">
                        <span><i class="fa-light fa-eye"></i> {{ __('Views') }}</span>
                        <span>{{ number_format($fViews) }} {{ __('avg/video') }}</span>
                    </div>
                    <div class="aith-funnel-bar aith-funnel-bar-1" style="width:100%;">
                        <span class="aith-funnel-pct">100%</span>
                    </div>
                </div>
            </div>
            <div style="text-align:center; color:#cbd5e1; margin:-0.25rem 0;"><i class="fa-light fa-arrow-down"></i></div>
            <div class="aith-funnel-step">
                <div class="aith-funnel-bar-wrap">
                    <div class="aith-funnel-label">
                        <span><i class="fa-light fa-thumbs-up"></i> {{ __('Likes') }}</span>
                        <span>{{ number_format($fLikes) }} {{ __('avg/video') }}</span>
                    </div>
                    <div class="aith-funnel-bar aith-funnel-bar-2" style="width:{{ $likeW }}%;">
                        <span class="aith-funnel-pct">{{ number_format($ef['view_to_like_pct'] ?? $cm['like_to_view_ratio'] ?? 0, 2) }}%</span>
                    </div>
                </div>
            </div>
            <div style="text-align:center; color:#cbd5e1; margin:-0.25rem 0;"><i class="fa-light fa-arrow-down"></i></div>
            <div class="aith-funnel-step">
                <div class="aith-funnel-bar-wrap">
                    <div class="aith-funnel-label">
                        <span><i class="fa-light fa-comment"></i> {{ __('Comments') }}</span>
                        <span>{{ number_format($fComments) }} {{ __('avg/video') }}</span>
                    </div>
                    <div class="aith-funnel-bar aith-funnel-bar-3" style="width:{{ $commentW }}%;">
                        <span class="aith-funnel-pct">{{ number_format($ef['view_to_comment_pct'] ?? $cm['comment_to_view_ratio'] ?? 0, 3) }}%</span>
                    </div>
                </div>
            </div>
            @if(!empty($ef['funnel_health']))
            <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.75rem;">
                @php
                    $fhClass = match($ef['funnel_health'] ?? '') { 'Healthy' => 'aith-health-good', 'Critical' => 'aith-health-poor', default => 'aith-health-avg' };
                @endphp
                <span class="aith-health-item {{ $fhClass }}">{{ __('Funnel:') }} {{ $ef['funnel_health'] }}</span>
            </div>
            @endif
            @if(!empty($ef['funnel_insight']))
            <div class="aith-funnel-insight"><i class="fa-light fa-lightbulb"></i> {{ $ef['funnel_insight'] }}</div>
            @endif
        </div>
    </div>
    @endif

    {{-- ========== 8. CONTENT PERFORMANCE — Top & Bottom ========== --}}
    @if(!empty($cm['top_videos']) || !empty($cm['bottom_videos']))
    <div class="aith-grid-2">
        @if(!empty($cm['top_videos']))
        <div class="aith-card" style="margin-bottom:0;">
            <h3 class="aith-section-title" style="color:#059669;"><i class="fa-light fa-trophy"></i> {{ __('Top Performers') }}</h3>
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
            <h3 class="aith-section-title" style="color:#dc2626;"><i class="fa-light fa-chart-line-down"></i> {{ __('Needs Improvement') }}</h3>
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

    {{-- ========== 9. COMPETITOR BENCHMARKS ========== --}}
    @if(!empty($cb))
    <div class="aith-card aith-card-accent aith-card-accent-blue">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-scale-balanced"></i> {{ __('Niche Comparison') }}</h3>
            <div class="aith-section-desc">{{ __('How this channel stacks up against similar channels in the') }} <strong>{{ $result['detected_niche'] ?? __('niche') }}</strong></div>
        </div>

        @if(!empty($cb['performance_vs_niche']))
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem; flex-wrap:wrap;">
            @php
                $perfClass = match($cb['performance_vs_niche'] ?? '') {
                    'Above Average' => 'aith-health-good',
                    'Below Average' => 'aith-health-poor',
                    default => 'aith-health-avg',
                };
            @endphp
            <span class="aith-health-item {{ $perfClass }}" style="font-size:0.8125rem; padding:0.5rem 1rem;">
                <i class="fa-light fa-chart-simple"></i>
                <strong>{{ $cb['performance_vs_niche'] }}</strong>
            </span>
            @if(!empty($cb['percentile_estimate']))
            <span class="aith-badge aith-badge-purple" style="padding:0.375rem 0.75rem;">{{ $cb['percentile_estimate'] }}</span>
            @endif
        </div>
        @endif

        <div class="aith-bench-legend">
            <span style="font-size:0.75rem; color:#64748b; display:flex; align-items:center; gap:0.25rem;">
                <span class="aith-bench-dot" style="background:#7c3aed;"></span> {{ __('This Channel') }}
            </span>
            <span style="font-size:0.75rem; color:#64748b; display:flex; align-items:center; gap:0.25rem;">
                <span class="aith-bench-dot" style="background:#cbd5e1;"></span> {{ __('Niche Average') }}
            </span>
        </div>

        @php
            // Simple visual benchmark bars — we'll normalize everything to 100
            $benchData = [];
            if (!empty($cb['niche_avg_views'])) {
                $chViews = $cm['avg_views'] ?? 0;
                $nicheViews = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '', $cb['niche_avg_views']));
                if ($nicheViews > 0 || $chViews > 0) {
                    $maxV = max($chViews, $nicheViews, 1);
                    $benchData[] = ['label' => 'Avg Views', 'you' => ($chViews / $maxV) * 100, 'niche' => ($nicheViews / $maxV) * 100];
                }
            }
            if (!empty($cb['niche_avg_engagement'])) {
                $chEng = $cm['engagement_rate'] ?? 0;
                $nicheEng = (float) preg_replace('/[^0-9.]/', '', $cb['niche_avg_engagement']);
                if ($nicheEng > 0 || $chEng > 0) {
                    $maxE = max($chEng, $nicheEng, 0.01);
                    $benchData[] = ['label' => 'Engagement', 'you' => ($chEng / $maxE) * 100, 'niche' => ($nicheEng / $maxE) * 100];
                }
            }
            if (!empty($cb['niche_avg_frequency'])) {
                $chFreq = $cm['posting_frequency'] ?? 0;
                $nicheFreq = (float) preg_replace('/[^0-9.]/', '', $cb['niche_avg_frequency']);
                if ($nicheFreq > 0 || $chFreq > 0) {
                    $maxF = max($chFreq, $nicheFreq, 0.1);
                    $benchData[] = ['label' => 'Frequency', 'you' => ($chFreq / $maxF) * 100, 'niche' => ($nicheFreq / $maxF) * 100];
                }
            }
        @endphp

        @foreach($benchData as $bd)
        <div class="aith-bench-row">
            <div class="aith-bench-label">{{ $bd['label'] }}</div>
            <div class="aith-bench-bars">
                <div class="aith-bench-bg"></div>
                <div class="aith-bench-niche" style="width:{{ min($bd['niche'], 100) }}%;"></div>
                <div class="aith-bench-you" style="width:{{ min($bd['you'], 100) }}%;"></div>
            </div>
        </div>
        @endforeach

        @if(!empty($cb['comparison_summary']))
        <p style="font-size:0.8125rem; color:#475569; margin:0.75rem 0 0; line-height:1.5; padding:0.75rem; background:#f8fafc; border-radius:8px;">
            <i class="fa-light fa-chart-simple" style="color:#7c3aed;"></i> {{ $cb['comparison_summary'] }}
        </p>
        @endif
    </div>
    @endif

    {{-- ========== 10. CONTENT ANALYSIS ========== --}}
    @if(!empty($ca))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-film"></i> {{ __('Content Analysis') }}</h3>
            <div class="aith-section-desc">{{ __('What works, what doesn\'t, and what to try next') }}</div>
        </div>

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

        @if(!empty($ca['underperforming_topics']))
        <div style="margin-bottom:1rem;">
            <div style="font-size:0.75rem; font-weight:600; color:#dc2626; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-arrow-down"></i> {{ __('Underperforming Topics') }}</div>
            <div class="aith-tags-wrap">
                @foreach($ca['underperforming_topics'] as $topic)
                <span class="aith-tag aith-tag-red">{{ $topic }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($ca['recommended_content_types']))
        <div style="margin-bottom:1rem;">
            <div style="font-size:0.75rem; font-weight:600; color:#7c3aed; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-wand-magic-sparkles"></i> {{ __('Recommended Content Types') }}</div>
            <div class="aith-tags-wrap">
                @foreach($ca['recommended_content_types'] as $type)
                <span class="aith-tag">{{ $type }}</span>
                @endforeach
            </div>
        </div>
        @endif

        <div class="aith-grid-2">
            @if(!empty($ca['optimal_upload_time']))
            <div class="aith-metric-card" style="text-align:left;">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.375rem;">
                    <i class="fa-light fa-clock" style="color:#7c3aed; font-size:1rem;"></i>
                    <span style="font-size:0.75rem; font-weight:600; color:#1e293b;">{{ __('Best Upload Time') }}</span>
                </div>
                <div style="font-size:0.875rem; color:#475569; font-weight:500;">{{ $ca['optimal_upload_time'] }}</div>
            </div>
            @endif
            @if(!empty($ca['ideal_video_length']))
            <div class="aith-metric-card" style="text-align:left;">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.375rem;">
                    <i class="fa-light fa-timer" style="color:#7c3aed; font-size:1rem;"></i>
                    <span style="font-size:0.75rem; font-weight:600; color:#1e293b;">{{ __('Ideal Video Length') }}</span>
                </div>
                <div style="font-size:0.875rem; color:#475569; font-weight:500;">{{ $ca['ideal_video_length'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ========== 11. GROWTH ASSESSMENT ========== --}}
    @if(!empty($ga))
    <div class="aith-card aith-card-accent aith-card-accent-green">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-rocket"></i> {{ __('Growth Assessment') }}</h3>
        </div>

        <div class="aith-grid-2" style="margin-bottom:1rem;">
            @if(!empty($ga['current_trajectory']))
            @php
                $trajColors = ['Accelerating' => 'aith-health-good', 'Steady' => 'aith-health-avg', 'Plateauing' => 'aith-health-avg', 'Declining' => 'aith-health-poor'];
                $trajIcons = ['Accelerating' => 'fa-arrow-trend-up', 'Steady' => 'fa-minus', 'Plateauing' => 'fa-arrow-right', 'Declining' => 'fa-arrow-trend-down'];
                $trajClass = $trajColors[$ga['current_trajectory']] ?? 'aith-health-avg';
                $trajIcon = $trajIcons[$ga['current_trajectory']] ?? 'fa-minus';
            @endphp
            <div class="aith-metric-card" style="text-align:left;">
                <div style="font-size:0.6875rem; font-weight:600; color:#64748b; text-transform:uppercase; margin-bottom:0.5rem;">{{ __('Growth Trajectory') }}</div>
                <span class="aith-health-item {{ $trajClass }}" style="display:inline-flex; font-size:0.8125rem;">
                    <i class="fa-light {{ $trajIcon }}"></i> {{ $ga['current_trajectory'] }}
                </span>
            </div>
            @endif
            @if(!empty($ga['monthly_view_potential']))
            <div class="aith-metric-card" style="text-align:left;">
                <div style="font-size:0.6875rem; font-weight:600; color:#64748b; text-transform:uppercase; margin-bottom:0.5rem;">{{ __('Monthly View Potential') }}</div>
                <div style="font-size:1.125rem; font-weight:700; color:#7c3aed;">{{ $ga['monthly_view_potential'] }}</div>
            </div>
            @endif
        </div>

        {{-- Milestone --}}
        @if(!empty($ga['subscriber_milestone']))
        <div class="aith-milestone" style="margin-bottom:1rem;">
            <div class="aith-milestone-icon"><i class="fa-light fa-flag-checkered"></i></div>
            <div class="aith-milestone-info">
                <div class="aith-milestone-text">{{ $ga['subscriber_milestone'] }}</div>
            </div>
        </div>
        @endif

        <div class="aith-grid-2">
            {{-- Growth Blockers --}}
            @if(!empty($ga['growth_blockers']))
            <div>
                <div style="font-size:0.75rem; font-weight:600; color:#dc2626; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-ban"></i> {{ __('Growth Blockers') }}</div>
                @foreach($ga['growth_blockers'] as $blocker)
                <div class="aith-list-icon-item">
                    <i class="fa-light fa-circle-xmark" style="color:#ef4444;"></i> {{ $blocker }}
                </div>
                @endforeach
            </div>
            @endif

            {{-- Growth Accelerators --}}
            @if(!empty($ga['growth_accelerators']))
            <div>
                <div style="font-size:0.75rem; font-weight:600; color:#059669; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;"><i class="fa-light fa-rocket"></i> {{ __('Growth Accelerators') }}</div>
                @foreach($ga['growth_accelerators'] as $acc)
                <div class="aith-list-icon-item">
                    <i class="fa-light fa-circle-check" style="color:#10b981;"></i> {{ $acc }}
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ========== 12. MONETIZATION INSIGHTS ========== --}}
    @if(!empty($mi))
    <div class="aith-card aith-card-accent aith-card-accent-amber">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-sack-dollar"></i> {{ __('Monetization Insights') }}</h3>
            <div class="aith-section-desc">{{ __('Revenue estimates based on niche CPM and channel performance') }}</div>
        </div>

        <div class="aith-grid-3" style="margin-bottom:1rem;">
            @if(!empty($mi['estimated_monthly_revenue']))
            <div class="aith-metric-card">
                <div class="aith-metric-value" style="color:#059669;">{{ $mi['estimated_monthly_revenue'] }}</div>
                <div class="aith-metric-label">{{ __('Monthly Revenue') }}</div>
            </div>
            @endif
            @if(!empty($mi['estimated_annual_revenue']))
            <div class="aith-metric-card">
                <div class="aith-metric-value" style="color:#059669;">{{ $mi['estimated_annual_revenue'] }}</div>
                <div class="aith-metric-label">{{ __('Annual Revenue') }}</div>
            </div>
            @endif
            @if(!empty($mi['estimated_cpm_range']))
            <div class="aith-metric-card">
                <div class="aith-metric-value">{{ $mi['estimated_cpm_range'] }}</div>
                <div class="aith-metric-label">{{ __('CPM Range') }}</div>
            </div>
            @endif
        </div>

        <div class="aith-grid-2" style="margin-bottom:1rem;">
            @if(!empty($mi['sponsorship_readiness']))
            @php
                $spClass = match($mi['sponsorship_readiness']) {
                    'Ready' => 'aith-health-good',
                    'Almost Ready' => 'aith-health-avg',
                    default => 'aith-health-poor',
                };
            @endphp
            <div class="aith-metric-card" style="text-align:left;">
                <div style="font-size:0.6875rem; font-weight:600; color:#64748b; text-transform:uppercase; margin-bottom:0.375rem;">{{ __('Sponsorship Readiness') }}</div>
                <span class="aith-health-item {{ $spClass }}" style="display:inline-flex;">{{ $mi['sponsorship_readiness'] }}</span>
                @if(!empty($mi['sponsorship_rate_estimate']))
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.375rem;">{{ __('Est. rate:') }} <strong>{{ $mi['sponsorship_rate_estimate'] }}</strong></div>
                @endif
            </div>
            @endif

            @if(!empty($mi['top_revenue_opportunities']))
            <div>
                <div style="font-size:0.75rem; font-weight:600; color:#059669; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;">{{ __('Revenue Opportunities') }}</div>
                @foreach($mi['top_revenue_opportunities'] as $opp)
                <div class="aith-list-icon-item">
                    <i class="fa-light fa-circle-dollar" style="color:#059669;"></i> {{ $opp }}
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ========== 13. SWOT ANALYSIS ========== --}}
    @if(!empty($sw))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-grid-2"></i> {{ __('SWOT Analysis') }}</h3>
            <div class="aith-section-desc">{{ __('Strategic overview of the channel\'s competitive position') }}</div>
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

    {{-- ========== 14. 90-DAY ACTION PLAN ========== --}}
    @if(!empty($result['action_plan']))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-map"></i> {{ __('90-Day Action Plan') }}</h3>
            <div class="aith-section-desc">{{ __('Step-by-step roadmap to accelerate channel growth') }}</div>
        </div>
        @foreach($result['action_plan'] as $action)
        <div class="aith-action-item">
            <div class="aith-action-week">{{ $action['week'] ?? '' }}</div>
            <div class="aith-action-info">
                <div class="aith-action-title">{{ $action['title'] ?? '' }}</div>
                <div class="aith-action-text">{{ $action['action'] ?? '' }}</div>
            </div>
            @if(!empty($action['impact']))
            @php $impClass = match($action['impact']) { 'high' => 'aith-badge-high', 'medium' => 'aith-badge-medium', default => 'aith-badge-low' }; @endphp
            <span class="aith-badge {{ $impClass }}">{{ strtoupper($action['impact']) }}</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- ========== 15. RECOMMENDATIONS (Collapsible) ========== --}}
    @if(!empty($result['recommendations']))
    <div class="aith-card">
        <div class="aith-section-header">
            <h3 class="aith-section-title"><i class="fa-light fa-lightbulb"></i> {{ __('Detailed Recommendations') }}</h3>
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

    </div>{{-- /audit-results-container --}}

    @endif

    {{-- PDF Export Script (outside @if block so it survives Livewire morphs) --}}
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

    async function aithExportPdf(btn) {
        var origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-light fa-spinner-third fa-spin"></i> Generating...';
        btn.disabled = true;
        function done() { btn.innerHTML = origHtml; btn.disabled = false; }

        var el = document.getElementById('audit-results-container');
        if (!el) { done(); return; }

        var imgBackups = [];

        try {
            // 1. Load libraries
            await Promise.all([
                typeof html2canvas !== 'undefined' ? Promise.resolve() :
                    aithLoadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'),
                typeof window.jspdf !== 'undefined' ? Promise.resolve() :
                    aithLoadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js')
            ]);

            // 2. Inline cross-origin images as compressed data URLs to avoid CORS canvas taint
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

            // 3. Capture at reasonable quality
            var canvas = await html2canvas(el, {
                scale: 1.5,
                logging: false,
                backgroundColor: '#f1f5f9',
                imageTimeout: 0
            });

            // 4. Restore original images immediately
            imgBackups.forEach(function(b) {
                b.el.src = b.src;
                if (b.hidden) b.el.style.visibility = '';
            });
            imgBackups = [];

            // 5. Build PDF with proper page splitting
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

            // Read channel name from DOM
            var nameEl = el.querySelector('.aith-channel-name') || el.querySelector('h3');
            var name = (nameEl ? nameEl.textContent : 'channel').trim();
            pdf.save('audit-' + name.replace(/[^a-z0-9]/gi, '-').toLowerCase() + '.pdf');
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
