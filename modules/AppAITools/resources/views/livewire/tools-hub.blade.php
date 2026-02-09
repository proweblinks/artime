<div class="aith">
    <style>
        /* ===== AI Tools Hub - Scoped Styles ===== */
        .aith {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            padding: 2rem 1.5rem 3rem;
        }

        /* Aurora Background Blobs */
        .aith-aurora { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
        .aith-aurora-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
        }
        .aith-aurora-blob--1 {
            top: -10rem; right: -10rem; width: 20rem; height: 20rem;
            background: radial-gradient(circle, #7c3aed, transparent 70%);
            opacity: 0.2;
            animation: aithAurora1 8s ease-in-out infinite;
        }
        .aith-aurora-blob--2 {
            top: 33%; left: -5rem; width: 15rem; height: 15rem;
            background: radial-gradient(circle, #06b6d4, transparent 70%);
            opacity: 0.15;
            animation: aithAurora2 12s ease-in-out infinite;
        }
        .aith-aurora-blob--3 {
            bottom: 5rem; right: 25%; width: 18rem; height: 18rem;
            background: radial-gradient(circle, #ec4899, transparent 70%);
            opacity: 0.1;
            animation: aithAurora3 10s ease-in-out infinite;
        }
        @keyframes aithAurora1 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(-30px,20px) scale(1.1)} }
        @keyframes aithAurora2 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(20px,-30px) scale(1.15)} }
        @keyframes aithAurora3 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(-20px,-20px) scale(1.05)} }

        /* Content layer */
        .aith-content { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; }

        /* Welcome */
        .aith-welcome { margin-bottom: 2rem; }
        .aith-welcome h1 { font-size: 1.75rem; font-weight: 700; color: #fff; margin: 0; }
        .aith-welcome-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-top: 0.5rem; }
        .aith-welcome-email { color: rgba(255,255,255,0.5); font-size: 0.875rem; }
        .aith-plan-badge {
            display: inline-flex; align-items: center; gap: 0.25rem;
            padding: 0.25rem 0.75rem; border-radius: 9999px;
            font-size: 0.75rem; font-weight: 700;
            background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(236,72,153,0.3));
            border: 1px solid rgba(139,92,246,0.4);
            color: #c4b5fd;
        }

        /* Usage Stats Carousel */
        .aith-stats { margin-bottom: 2.5rem; }
        .aith-stats-scroll {
            display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem;
            -ms-overflow-style: none; scrollbar-width: none;
        }
        .aith-stats-scroll::-webkit-scrollbar { display: none; }
        .aith-stat-card {
            flex-shrink: 0; width: 13rem; padding: 1rem; border-radius: 0.75rem;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        .aith-stat-card:hover { background: rgba(255,255,255,0.08); }
        .aith-stat-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
        .aith-stat-emoji { font-size: 1.25rem; }
        .aith-stat-name { color: rgba(255,255,255,0.7); font-size: 0.8rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .aith-stat-count { margin-bottom: 0.5rem; }
        .aith-stat-used { font-size: 1.5rem; font-weight: 700; color: #fff; }
        .aith-stat-limit { color: rgba(255,255,255,0.3); font-size: 0.875rem; }
        .aith-stat-bar { width: 100%; height: 6px; border-radius: 9999px; background: rgba(255,255,255,0.1); overflow: hidden; }
        .aith-stat-bar-fill { height: 100%; border-radius: 9999px; transition: width 0.5s ease; }

        /* Gradient fills for stat bars */
        .aith-grad-blue-purple { background: linear-gradient(90deg, #3b82f6, #9333ea); }
        .aith-grad-red-orange { background: linear-gradient(90deg, #ef4444, #ea580c); }
        .aith-grad-cyan-blue { background: linear-gradient(90deg, #06b6d4, #2563eb); }
        .aith-grad-pink-rose { background: linear-gradient(90deg, #ec4899, #e11d48); }
        .aith-grad-emerald-teal { background: linear-gradient(90deg, #10b981, #0d9488); }

        /* Section Title */
        .aith-section-title { font-size: 1.125rem; font-weight: 700; color: #fff; margin-bottom: 1.5rem; }

        /* Tools Grid */
        .aith-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        @media (max-width: 992px) { .aith-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .aith-grid { grid-template-columns: 1fr; gap: 1rem; } }

        /* Tool Card */
        .aith-tool-card {
            display: block;
            padding: 1.5rem;
            border-radius: 1rem;
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            transition: all 0.3s;
            text-decoration: none !important;
            height: 100%;
        }
        .aith-tool-card:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.15);
            transform: scale(1.02);
            box-shadow: 0 8px 32px rgba(124,58,237,0.05);
        }

        /* Tool Icon */
        .aith-tool-icon {
            width: 3.5rem; height: 3.5rem;
            border-radius: 1rem;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.75rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .aith-icon-blue-purple { background: linear-gradient(135deg, #3b82f6, #9333ea); }
        .aith-icon-red-orange { background: linear-gradient(135deg, #ef4444, #ea580c); }
        .aith-icon-cyan-blue { background: linear-gradient(135deg, #06b6d4, #2563eb); }
        .aith-icon-pink-rose { background: linear-gradient(135deg, #ec4899, #e11d48); }
        .aith-icon-emerald-teal { background: linear-gradient(135deg, #10b981, #0d9488); }
        .aith-icon-purple-indigo { background: linear-gradient(135deg, #a855f7, #4f46e5); }

        .aith-tool-card:hover .aith-tool-icon { transform: scale(1.1); transition: transform 0.3s; }

        /* Tool Text */
        .aith-tool-name { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .aith-tool-desc { color: rgba(255,255,255,0.4); font-size: 0.875rem; line-height: 1.5; margin-bottom: 1rem; min-height: 2.5rem; }

        /* Tool CTA */
        .aith-tool-cta { font-size: 0.875rem; font-weight: 600; transition: transform 0.2s; display: inline-flex; align-items: center; gap: 0.25rem; }
        .aith-tool-card:hover .aith-tool-cta { transform: translateX(4px); }
        .aith-cta-blue { color: #60a5fa; }
        .aith-cta-red { color: #f87171; }
        .aith-cta-cyan { color: #22d3ee; }
        .aith-cta-pink { color: #f472b6; }
        .aith-cta-emerald { color: #34d399; }
        .aith-cta-purple { color: #c084fc; }

        /* Recent Activity */
        .aith-activity { border-radius: 0.75rem; overflow: hidden; border: 1px solid rgba(255,255,255,0.06); }
        .aith-activity-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.875rem 1.25rem;
            background: rgba(255,255,255,0.04);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            transition: background 0.2s;
        }
        .aith-activity-item:last-child { border-bottom: none; }
        .aith-activity-item:hover { background: rgba(255,255,255,0.07); }
        .aith-activity-left { display: flex; align-items: center; gap: 0.75rem; min-width: 0; flex: 1; }
        .aith-activity-emoji { font-size: 1.25rem; flex-shrink: 0; }
        .aith-activity-info { min-width: 0; flex: 1; }
        .aith-activity-label { color: rgba(255,255,255,0.7); font-size: 0.8rem; font-weight: 500; }
        .aith-activity-title { color: rgba(255,255,255,0.4); font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .aith-activity-time { color: rgba(255,255,255,0.25); font-size: 0.8rem; flex-shrink: 0; margin-left: 0.75rem; }

        /* History Link */
        .aith-history-link { text-align: center; margin-top: 1.25rem; }
        .aith-history-link span { color: rgba(255,255,255,0.3); font-size: 0.875rem; }

        /* Empty State */
        .aith-empty { text-align: center; padding: 3rem 0; }
        .aith-empty-card {
            display: inline-block; padding: 2rem 2.5rem; border-radius: 1rem;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .aith-empty-icon { font-size: 3rem; margin-bottom: 1rem; }
        .aith-empty-title { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .aith-empty-text { color: rgba(255,255,255,0.4); font-size: 0.875rem; }

        @media (min-width: 768px) {
            .aith { padding: 2.5rem 2rem 3rem; }
            .aith-welcome h1 { font-size: 2rem; }
            .aith-stat-card { width: 14rem; }
        }
    </style>

    {{-- Aurora Background --}}
    <div class="aith-aurora">
        <div class="aith-aurora-blob aith-aurora-blob--1"></div>
        <div class="aith-aurora-blob aith-aurora-blob--2"></div>
        <div class="aith-aurora-blob aith-aurora-blob--3"></div>
    </div>

    <div class="aith-content">

        {{-- Welcome Header --}}
        <div class="aith-welcome">
            <h1>Welcome back! &#10024;</h1>
            <div class="aith-welcome-meta">
                <span class="aith-welcome-email">{{ auth()->user()->email ?? '' }}</span>
                @if(isset($user) && $user->plan)
                    <span class="aith-plan-badge">&#11088; {{ $user->plan->name }}</span>
                @endif
            </div>
        </div>

        {{-- Usage Stats Carousel --}}
        @if(count($usageStats) > 0)
        <div class="aith-stats">
            <div class="aith-stats-scroll">
                @foreach($usageStats as $stat)
                @php
                    $gradMap = [
                        'from-blue-500 to-purple-600'  => 'aith-grad-blue-purple',
                        'from-red-500 to-orange-600'   => 'aith-grad-red-orange',
                        'from-cyan-500 to-blue-600'    => 'aith-grad-cyan-blue',
                        'from-pink-500 to-rose-600'    => 'aith-grad-pink-rose',
                        'from-emerald-500 to-teal-600' => 'aith-grad-emerald-teal',
                    ];
                    $gradClass = $gradMap[$stat['gradient']] ?? 'aith-grad-blue-purple';
                @endphp
                <div class="aith-stat-card">
                    <div class="aith-stat-header">
                        <span class="aith-stat-emoji">{{ $stat['emoji'] }}</span>
                        <span class="aith-stat-name">{{ $stat['name'] }}</span>
                    </div>
                    <div class="aith-stat-count">
                        <span class="aith-stat-used">{{ $stat['used'] }}</span>
                        <span class="aith-stat-limit">/ {{ $stat['limit'] }}</span>
                    </div>
                    <div class="aith-stat-bar">
                        <div class="aith-stat-bar-fill {{ $gradClass }}"
                             style="width: {{ $stat['percent'] }}%;{{ $stat['used'] > 0 ? ' min-width: 8px;' : '' }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- AI Tools Section --}}
        <div class="aith-section-title">&#128736;&#65039; AI Tools</div>

        <div class="aith-grid">
            @php
                $iconClasses = [
                    'video_optimizer'     => 'aith-icon-blue-purple',
                    'competitor_analysis' => 'aith-icon-red-orange',
                    'trend_predictor'     => 'aith-icon-cyan-blue',
                    'ai_thumbnails'       => 'aith-icon-pink-rose',
                    'channel_audit'       => 'aith-icon-emerald-teal',
                    'more_tools'          => 'aith-icon-purple-indigo',
                ];
                $ctaClasses = [
                    'video_optimizer'     => 'aith-cta-blue',
                    'competitor_analysis' => 'aith-cta-red',
                    'trend_predictor'     => 'aith-cta-cyan',
                    'ai_thumbnails'       => 'aith-cta-pink',
                    'channel_audit'       => 'aith-cta-emerald',
                    'more_tools'          => 'aith-cta-purple',
                ];
            @endphp

            @foreach($tools as $key => $tool)
            <a href="{{ route($tool['route']) }}" class="aith-tool-card">
                <div class="aith-tool-icon {{ $iconClasses[$key] ?? 'aith-icon-blue-purple' }}">
                    {{ $tool['emoji'] }}
                </div>
                <div class="aith-tool-name">{{ __($tool['name']) }}</div>
                <div class="aith-tool-desc">{{ __($tool['description']) }}</div>
                <span class="aith-tool-cta {{ $ctaClasses[$key] ?? 'aith-cta-blue' }}">
                    {{ __($tool['cta_text']) }} <span>&#8594;</span>
                </span>
            </a>
            @endforeach
        </div>

        {{-- Recent Activity --}}
        @if(count($recentActivity) > 0)
        <div>
            <div class="aith-section-title">&#128203; Recent Activity</div>
            <div class="aith-activity">
                @foreach($recentActivity as $activity)
                <div class="aith-activity-item">
                    <div class="aith-activity-left">
                        <span class="aith-activity-emoji">{{ $activity['emoji'] }}</span>
                        <div class="aith-activity-info">
                            <div class="aith-activity-label">{{ $activity['tool_label'] }}</div>
                            <div class="aith-activity-title">{{ $activity['title'] }}</div>
                        </div>
                    </div>
                    <span class="aith-activity-time">{{ $activity['time_ago'] }}</span>
                </div>
                @endforeach
            </div>
            <div class="aith-history-link">
                <span>&#128220; View All History</span>
            </div>
        </div>
        @else
        {{-- Empty State --}}
        <div class="aith-empty">
            <div class="aith-empty-card">
                <div class="aith-empty-icon">&#128640;</div>
                <div class="aith-empty-title">Ready to create?</div>
                <div class="aith-empty-text">Choose a tool above to get started with AI-powered content optimization.</div>
            </div>
        </div>
        @endif

    </div>
</div>
