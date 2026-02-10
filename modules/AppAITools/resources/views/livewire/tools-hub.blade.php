<div class="aith">
    <style>
        /* ===== AI Tools Hub - Scoped Styles ===== */
        .aith {
            min-height: 100%;
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

        /* Aggregate Stats Row */
        .aith-agg-row { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:2rem; }
        @media (max-width:768px) { .aith-agg-row { grid-template-columns:repeat(2,1fr); } }
        .aith-agg-card { padding:1rem 1.25rem; border-radius:0.75rem; background:rgba(255,255,255,0.05); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.1); text-align:center; transition:all 0.3s; }
        .aith-agg-card:hover { background:rgba(255,255,255,0.08); }
        .aith-agg-emoji { font-size:1.5rem; display:block; margin-bottom:0.25rem; }
        .aith-agg-num { font-size:1.75rem; font-weight:800; color:#fff; }
        .aith-agg-label { font-size:0.75rem; color:rgba(255,255,255,0.4); margin-top:0.25rem; text-transform:uppercase; letter-spacing:0.05em; }

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

        /* Pinned Tools Row */
        .aith-pinned-row { display:flex; gap:0.75rem; overflow-x:auto; margin-bottom:2rem; padding-bottom:0.5rem; }
        .aith-pinned-row::-webkit-scrollbar { display:none; }
        .aith-pinned-card { flex-shrink:0; display:flex; align-items:center; gap:0.625rem; padding:0.625rem 1rem; border-radius:0.75rem; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); cursor:pointer; transition:all 0.2s; text-decoration:none!important; }
        .aith-pinned-card:hover { background:rgba(255,255,255,0.1); border-color:rgba(255,255,255,0.2); }
        .aith-pinned-emoji { font-size:1.25rem; }
        .aith-pinned-name { color:#fff; font-size:0.8125rem; font-weight:600; white-space:nowrap; }
        .aith-pinned-unpin { color:rgba(255,255,255,0.3); font-size:0.75rem; cursor:pointer; margin-left:0.25rem; transition:color 0.2s; }
        .aith-pinned-unpin:hover { color:#f87171; }

        /* Recommended Row */
        .aith-rec-row { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:2rem; }
        @media (max-width:768px) { .aith-rec-row { grid-template-columns:1fr; } }
        .aith-rec-card { display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1rem; border-radius:0.75rem; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.2); cursor:pointer; transition:all 0.2s; text-decoration:none!important; }
        .aith-rec-card:hover { background:rgba(139,92,246,0.15); border-color:rgba(139,92,246,0.35); }
        .aith-rec-emoji { font-size:1.5rem; }
        .aith-rec-info { flex:1; min-width:0; }
        .aith-rec-name { color:#fff; font-size:0.875rem; font-weight:600; }
        .aith-rec-reason { color:rgba(255,255,255,0.4); font-size:0.75rem; }

        /* Suggestion Engine */
        .aith-suggest { margin-bottom:2rem; padding:1.25rem; border-radius:1rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); }
        .aith-suggest-title { color:rgba(255,255,255,0.6); font-size:0.875rem; font-weight:600; margin-bottom:0.75rem; }
        .aith-suggest-chips { display:flex; flex-wrap:wrap; gap:0.5rem; }
        .aith-suggest-chip { padding:0.375rem 0.875rem; border-radius:9999px; font-size:0.8125rem; cursor:pointer; transition:all 0.2s; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:rgba(255,255,255,0.6); }
        .aith-suggest-chip:hover { background:rgba(139,92,246,0.15); border-color:rgba(139,92,246,0.3); color:#c4b5fd; }
        .aith-suggest-chip.active { background:rgba(139,92,246,0.25); border-color:rgba(139,92,246,0.5); color:#e9d5ff; }
        .aith-suggest-results { margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid rgba(255,255,255,0.06); display:flex; flex-wrap:wrap; gap:0.5rem; }
        .aith-suggest-result { display:inline-flex; align-items:center; gap:0.375rem; padding:0.375rem 0.75rem; border-radius:0.5rem; background:rgba(139,92,246,0.12); border:1px solid rgba(139,92,246,0.25); color:#c4b5fd; font-size:0.8125rem; font-weight:600; text-decoration:none!important; transition:all 0.2s; }
        .aith-suggest-result:hover { background:rgba(139,92,246,0.2); border-color:rgba(139,92,246,0.4); color:#e9d5ff; }

        /* Category Tabs */
        .aith-cat-tabs { display:flex; gap:0.5rem; overflow-x:auto; margin-bottom:1.5rem; padding-bottom:0.25rem; }
        .aith-cat-tabs::-webkit-scrollbar { display:none; }
        .aith-cat-tab { padding:0.5rem 1rem; border-radius:9999px; font-size:0.8125rem; font-weight:600; white-space:nowrap; cursor:pointer; transition:all 0.2s; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:rgba(255,255,255,0.5); }
        .aith-cat-tab:hover { background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.7); }
        .aith-cat-tab.active { background:rgba(139,92,246,0.2); border-color:rgba(139,92,246,0.4); color:#c4b5fd; }

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
            position: relative;
            overflow: hidden;
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
        .aith-tool-card.aith-highlighted {
            border-color: rgba(139,92,246,0.5);
            box-shadow: 0 0 20px rgba(139,92,246,0.15);
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
        .aith-icon-amber-yellow { background: linear-gradient(135deg, #f59e0b, #eab308); }

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
        .aith-cta-amber { color: #fbbf24; }

        /* Badge: Credit Cost */
        .aith-badge-credit { position:absolute; top:0.75rem; right:0.75rem; padding:0.2rem 0.5rem; border-radius:9999px; font-size:0.6875rem; font-weight:700; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.15); color:rgba(255,255,255,0.6); z-index:1; }

        /* Badge: NEW */
        .aith-badge-new { position:absolute; top:0.75rem; left:0.75rem; padding:0.15rem 0.5rem; border-radius:9999px; font-size:0.625rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; background:linear-gradient(135deg,#8b5cf6,#ec4899); color:#fff; animation:aithPulseNew 2s ease-in-out infinite; z-index:1; }
        @keyframes aithPulseNew { 0%,100%{opacity:1} 50%{opacity:0.7} }

        /* Badge: Pin */
        .aith-badge-pin { position:absolute; top:0.75rem; right:3.25rem; width:1.5rem; height:1.5rem; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.6875rem; background:rgba(255,255,255,0.06); color:rgba(255,255,255,0.3); border:1px solid transparent; transition:all 0.2s; z-index:4; }
        .aith-badge-pin:hover { background:rgba(255,255,255,0.12); color:#fbbf24; border-color:rgba(251,191,36,0.3); }
        .aith-badge-pin.pinned { color:#fbbf24; background:rgba(251,191,36,0.15); border-color:rgba(251,191,36,0.3); }

        /* Last Result Preview */
        .aith-last-preview { margin-top:0.5rem; padding-top:0.5rem; border-top:1px solid rgba(255,255,255,0.06); }
        .aith-last-snippet { font-size:0.75rem; color:rgba(255,255,255,0.35); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .aith-last-time { font-size:0.625rem; color:rgba(255,255,255,0.2); }

        /* Quick Action Overlay */
        .aith-quick-actions { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; gap:0.75rem; background:rgba(0,0,0,0.75); backdrop-filter:blur(4px); opacity:0; transition:opacity 0.25s; border-radius:1rem; z-index:3; }
        .aith-tool-card:hover .aith-quick-actions { opacity:1; }
        .aith-quick-btn { padding:0.5rem 1rem; border-radius:0.5rem; font-size:0.8125rem; font-weight:600; cursor:pointer; transition:all 0.2s; text-decoration:none!important; display:inline-flex; align-items:center; gap:0.375rem; }
        .aith-quick-primary { background:linear-gradient(135deg,#7c3aed,#6d28d9); color:#fff; border:none; }
        .aith-quick-primary:hover { background:linear-gradient(135deg,#8b5cf6,#7c3aed); transform:scale(1.05); color:#fff; }
        .aith-quick-secondary { background:rgba(255,255,255,0.1); color:rgba(255,255,255,0.8); border:1px solid rgba(255,255,255,0.15); }
        .aith-quick-secondary:hover { background:rgba(255,255,255,0.15); color:#fff; }

        /* Enterprise Banner */
        .aith-enterprise-banner {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1.75rem;
            margin-bottom: 3rem;
            padding: 1.75rem 2rem;
            border-radius: 1.25rem;
            overflow: hidden;
            text-decoration: none !important;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            background:
                linear-gradient(135deg,
                    rgba(251,191,36,0.12) 0%,
                    rgba(245,158,11,0.08) 30%,
                    rgba(139,92,246,0.1) 70%,
                    rgba(236,72,153,0.08) 100%);
            border: 1px solid rgba(251,191,36,0.2);
        }
        .aith-enterprise-banner:hover {
            border-color: rgba(251,191,36,0.4);
            transform: translateY(-3px);
            box-shadow:
                0 12px 40px rgba(251,191,36,0.15),
                0 0 0 1px rgba(251,191,36,0.1);
        }
        .aith-enterprise-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                105deg,
                transparent 40%,
                rgba(251,191,36,0.06) 45%,
                rgba(251,191,36,0.1) 50%,
                rgba(251,191,36,0.06) 55%,
                transparent 60%
            );
            background-size: 200% 100%;
            animation: aithShimmer 3s ease-in-out infinite;
        }
        @keyframes aithShimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .aith-enterprise-crown {
            position: relative;
            flex-shrink: 0;
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 8px 32px rgba(245,158,11,0.35);
        }
        .aith-enterprise-crown::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 1.35rem;
            background: conic-gradient(
                from 0deg,
                rgba(251,191,36,0.6),
                rgba(245,158,11,0),
                rgba(251,191,36,0.6),
                rgba(245,158,11,0),
                rgba(251,191,36,0.6)
            );
            z-index: -1;
            animation: aithCrownGlow 4s linear infinite;
        }
        @keyframes aithCrownGlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .aith-enterprise-body {
            position: relative;
            flex: 1;
            min-width: 0;
        }
        .aith-enterprise-label {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.2rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: linear-gradient(135deg, rgba(251,191,36,0.25), rgba(245,158,11,0.15));
            border: 1px solid rgba(251,191,36,0.3);
            color: #fbbf24;
            margin-bottom: 0.5rem;
        }
        .aith-enterprise-title {
            font-size: 1.375rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.375rem;
            line-height: 1.2;
        }
        .aith-enterprise-title span {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .aith-enterprise-desc {
            color: rgba(255,255,255,0.45);
            font-size: 0.875rem;
            line-height: 1.4;
        }
        .aith-enterprise-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            margin-top: 0.625rem;
        }
        .aith-enterprise-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 500;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.5);
        }
        .aith-enterprise-arrow {
            position: relative;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(251,191,36,0.2), rgba(245,158,11,0.1));
            border: 1px solid rgba(251,191,36,0.25);
            color: #fbbf24;
            font-size: 1.125rem;
            transition: all 0.3s;
        }
        .aith-enterprise-banner:hover .aith-enterprise-arrow {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            transform: translateX(4px);
            box-shadow: 0 4px 20px rgba(245,158,11,0.35);
        }
        @media (max-width: 640px) {
            .aith-enterprise-banner {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
                padding: 1.5rem;
            }
            .aith-enterprise-tags { justify-content: center; }
            .aith-enterprise-arrow { display: none; }
        }

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

        {{-- ============================================= --}}
        {{-- Feature 9: Aggregate Stats Row               --}}
        {{-- ============================================= --}}
        @if(!empty($aggregateStats))
        <div class="aith-agg-row">
            <div class="aith-agg-card">
                <span class="aith-agg-emoji">&#128202;</span>
                <div class="aith-agg-num">{{ number_format($aggregateStats['total_analyses'] ?? 0) }}</div>
                <div class="aith-agg-label">Total Analyses</div>
            </div>
            <div class="aith-agg-card">
                <span class="aith-agg-emoji">&#9889;</span>
                <div class="aith-agg-num">{{ number_format($aggregateStats['credits_used'] ?? 0) }}</div>
                <div class="aith-agg-label">Credits Used</div>
            </div>
            <div class="aith-agg-card">
                <span class="aith-agg-emoji">{{ $aggregateStats['most_used_emoji'] ?? '&#128736;' }}</span>
                <div class="aith-agg-num" style="font-size:1.125rem;">{{ $aggregateStats['most_used'] ?? '-' }}</div>
                <div class="aith-agg-label">Most Used Tool</div>
            </div>
            <div class="aith-agg-card">
                <span class="aith-agg-emoji">&#128293;</span>
                <div class="aith-agg-num">{{ $aggregateStats['streak'] ?? 0 }}<span style="font-size:0.875rem;color:rgba(255,255,255,0.4);"> days</span></div>
                <div class="aith-agg-label">Activity Streak</div>
            </div>
        </div>
        @endif

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

        {{-- ============================================= --}}
        {{-- Feature 4: Pinned Tools Row                  --}}
        {{-- ============================================= --}}
        @if(count($pinnedTools) > 0)
        <div class="aith-section-title">&#11088; Pinned Tools</div>
        <div class="aith-pinned-row">
            @foreach($pinnedTools as $pinnedKey)
                @php $pt = $tools[$pinnedKey] ?? null; @endphp
                @if($pt)
                <div class="aith-pinned-card" style="display:flex;">
                    <a href="{{ route($pt['route']) }}" style="display:flex;align-items:center;gap:0.625rem;text-decoration:none!important;color:inherit;">
                        <span class="aith-pinned-emoji">{{ $pt['emoji'] }}</span>
                        <span class="aith-pinned-name">{{ $pt['name'] }}</span>
                    </a>
                    <span class="aith-pinned-unpin" wire:click.prevent="togglePin('{{ $pinnedKey }}')" title="Unpin">
                        <i class="fa-solid fa-xmark"></i>
                    </span>
                </div>
                @endif
            @endforeach
        </div>
        @endif

        {{-- ============================================= --}}
        {{-- Feature 2: Recommended For You               --}}
        {{-- ============================================= --}}
        @if(count($recommendedTools) > 0)
        <div class="aith-section-title">&#128161; Recommended For You</div>
        <div class="aith-rec-row">
            @foreach($recommendedTools as $recKey)
                @php $rt = $tools[$recKey] ?? null; @endphp
                @if($rt)
                <a href="{{ route($rt['route']) }}" class="aith-rec-card">
                    <span class="aith-rec-emoji">{{ $rt['emoji'] }}</span>
                    <div class="aith-rec-info">
                        <div class="aith-rec-name">{{ $rt['name'] }}</div>
                        <div class="aith-rec-reason">Based on your usage</div>
                    </div>
                    <span style="color:rgba(255,255,255,0.3);">&#8594;</span>
                </a>
                @endif
            @endforeach
        </div>
        @endif

        {{-- ============================================= --}}
        {{-- Feature 10: Tool Suggestion Engine           --}}
        {{-- ============================================= --}}
        @if(!empty($suggestionEngine['questions']))
        <div class="aith-suggest" x-data="{ activeQ: null, activeTools: [] }">
            <div class="aith-suggest-title">&#129300; What should I do next?</div>
            <div class="aith-suggest-chips">
                @foreach($suggestionEngine['questions'] as $idx => $sq)
                <span class="aith-suggest-chip"
                      :class="{ 'active': activeQ === {{ $idx }} }"
                      @click="activeQ = activeQ === {{ $idx }} ? null : {{ $idx }}; activeTools = activeQ === {{ $idx }} ? {{ json_encode($sq['tools']) }} : []">
                    {{ $sq['q'] }}
                </span>
                @endforeach
            </div>
            <template x-if="activeTools.length > 0">
                <div class="aith-suggest-results">
                    @foreach($tools as $tKey => $tVal)
                    <template x-if="activeTools.includes('{{ $tKey }}')">
                        <a href="{{ route($tVal['route']) }}" class="aith-suggest-result">
                            {{ $tVal['emoji'] }} {{ $tVal['name'] }} &#8594;
                        </a>
                    </template>
                    @endforeach
                </div>
            </template>
        </div>
        @endif

        {{-- ============================================= --}}
        {{-- Feature 6: Category Filter Tabs              --}}
        {{-- ============================================= --}}
        @php
            $isNewThreshold = now()->subDays(30)->format('Y-m-d');
        @endphp

        <div x-data="{ activeCategory: 'all', highlightedTools: [] }" x-init="$watch('activeCategory', () => highlightedTools = [])">

            {{-- Category Tabs --}}
            @if(!empty($hubCategories))
            <div class="aith-cat-tabs">
                @foreach($hubCategories as $catKey => $cat)
                <span class="aith-cat-tab"
                      :class="{ 'active': activeCategory === '{{ $catKey }}' }"
                      @click="activeCategory = '{{ $catKey }}'">
                    {{ $cat['emoji'] }} {{ $cat['name'] }}
                </span>
                @endforeach
            </div>
            @endif

            {{-- AI Tools Grid --}}
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
                        'enterprise_suite'    => 'aith-icon-amber-yellow',
                    ];
                    $ctaClasses = [
                        'video_optimizer'     => 'aith-cta-blue',
                        'competitor_analysis' => 'aith-cta-red',
                        'trend_predictor'     => 'aith-cta-cyan',
                        'ai_thumbnails'       => 'aith-cta-pink',
                        'channel_audit'       => 'aith-cta-emerald',
                        'more_tools'          => 'aith-cta-purple',
                        'enterprise_suite'    => 'aith-cta-amber',
                    ];
                @endphp

                @foreach($tools as $key => $tool)
                    @if($key === 'enterprise_suite') @continue @endif

                    @php
                        $toolCategory = $tool['category'] ?? 'content';
                        $isNew = isset($tool['last_updated']) && $tool['last_updated'] >= $isNewThreshold;
                        $creditCost = $tool['credits'] ?? 0;
                        $isPinned = in_array($key, $pinnedTools);
                        $lastResult = $lastResults[$key] ?? null;
                    @endphp

                    <div class="aith-tool-card"
                         data-category="{{ $toolCategory }}"
                         x-show="activeCategory === 'all' || activeCategory === '{{ $toolCategory }}'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         :class="{ 'aith-highlighted': highlightedTools.includes('{{ $key }}') }">

                        {{-- Feature 7: NEW badge --}}
                        @if($isNew)
                        <span class="aith-badge-new">NEW</span>
                        @endif

                        {{-- Feature 3: Credit badge --}}
                        @if($creditCost > 0)
                        <span class="aith-badge-credit">{{ $creditCost }} {{ $creditCost === 1 ? 'credit' : 'credits' }}</span>
                        @endif

                        {{-- Feature 4: Pin button --}}
                        <span class="aith-badge-pin {{ $isPinned ? 'pinned' : '' }}"
                              wire:click.prevent="togglePin('{{ $key }}')"
                              title="{{ $isPinned ? 'Unpin' : 'Pin to top' }}">
                            <i class="fa-{{ $isPinned ? 'solid' : 'light' }} fa-star"></i>
                        </span>

                        {{-- Feature 8: Quick action overlay --}}
                        <div class="aith-quick-actions">
                            <a href="{{ route($tool['route']) }}" class="aith-quick-btn aith-quick-primary">
                                <i class="fa-light fa-play"></i> Open Tool
                            </a>
                            @if($lastResult)
                            <a href="{{ route($tool['route']) }}" class="aith-quick-btn aith-quick-secondary">
                                <i class="fa-light fa-clock-rotate-left"></i> View Last
                            </a>
                            @endif
                        </div>

                        {{-- Card Content --}}
                        <div class="aith-tool-icon {{ $iconClasses[$key] ?? 'aith-icon-blue-purple' }}">
                            {{ $tool['emoji'] }}
                        </div>
                        <div class="aith-tool-name">{{ __($tool['name']) }}</div>
                        <div class="aith-tool-desc">{{ __($tool['description']) }}</div>
                        <span class="aith-tool-cta {{ $ctaClasses[$key] ?? 'aith-cta-blue' }}">
                            {{ __($tool['cta_text']) }} <span>&#8594;</span>
                        </span>

                        {{-- Feature 5: Last result preview --}}
                        @if($lastResult)
                        <div class="aith-last-preview">
                            <div class="aith-last-snippet">{{ $lastResult['snippet'] }}</div>
                            <div class="aith-last-time">{{ $lastResult['time_ago'] }}</div>
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>

        </div>{{-- /x-data category filter --}}

        {{-- Enterprise Suite Banner --}}
        @if(isset($tools['enterprise_suite']))
        <a href="{{ route($tools['enterprise_suite']['route']) }}" class="aith-enterprise-banner">
            <div class="aith-enterprise-crown">&#128081;</div>
            <div class="aith-enterprise-body">
                <div class="aith-enterprise-label">
                    <i class="fa-light fa-sparkles"></i> Premium Suite
                </div>
                <div class="aith-enterprise-title">Unlock <span>15 Enterprise AI Tools</span></div>
                <div class="aith-enterprise-desc">{{ __($tools['enterprise_suite']['description']) }}</div>
                <div class="aith-enterprise-tags">
                    <span class="aith-enterprise-tag">&#128176; Monetization</span>
                    <span class="aith-enterprise-tag">&#128200; Analytics</span>
                    <span class="aith-enterprise-tag">&#127775; Brand Deals</span>
                    <span class="aith-enterprise-tag">&#128640; Revenue Automation</span>
                    <span class="aith-enterprise-tag">&#128270; Audience Profiling</span>
                </div>
            </div>
            <div class="aith-enterprise-arrow">
                <i class="fa-light fa-arrow-right"></i>
            </div>
        </a>
        @endif

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
