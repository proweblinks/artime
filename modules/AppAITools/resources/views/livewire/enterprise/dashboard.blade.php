<div x-data="enterpriseDashboard()" class="aith-enterprise">
    <style>
        /* ===== Enterprise Suite Dashboard - Scoped Styles ===== */
        .aith-enterprise {
            min-height: 100%;
            position: relative;
            overflow: hidden;
            padding: 2rem 1.5rem 3rem;
        }

        /* Aurora Background */
        .aith-e-aurora { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
        .aith-e-aurora-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
        }
        .aith-e-aurora-blob--1 {
            top: -10rem; right: -10rem; width: 20rem; height: 20rem;
            background: radial-gradient(circle, #7c3aed, transparent 70%);
            opacity: 0.18;
            animation: aithEAurora1 8s ease-in-out infinite;
        }
        .aith-e-aurora-blob--2 {
            top: 40%; left: -5rem; width: 15rem; height: 15rem;
            background: radial-gradient(circle, #06b6d4, transparent 70%);
            opacity: 0.12;
            animation: aithEAurora2 12s ease-in-out infinite;
        }
        .aith-e-aurora-blob--3 {
            bottom: 5rem; right: 20%; width: 18rem; height: 18rem;
            background: radial-gradient(circle, #ec4899, transparent 70%);
            opacity: 0.08;
            animation: aithEAurora3 10s ease-in-out infinite;
        }
        @keyframes aithEAurora1 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(-30px,20px) scale(1.1)} }
        @keyframes aithEAurora2 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(20px,-30px) scale(1.15)} }
        @keyframes aithEAurora3 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(-20px,-20px) scale(1.05)} }

        /* Content */
        .aith-e-content { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; }

        /* Header */
        .aith-e-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .aith-e-header-left { display: flex; align-items: center; gap: 1rem; }
        .aith-e-back {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1rem; border-radius: 0.5rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6);
            font-size: 0.875rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        .aith-e-back:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .aith-e-title {
            font-size: 1.75rem; font-weight: 800; color: #fff; margin: 0;
            background: linear-gradient(135deg, #fff, #c4b5fd);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .aith-e-subtitle { color: rgba(255,255,255,0.4); font-size: 0.875rem; margin-top: 0.25rem; }

        /* Search Trigger */
        .aith-e-search-trigger {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.75rem 1.25rem; border-radius: 0.75rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.35);
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 280px;
        }
        .aith-e-search-trigger:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.2);
        }
        .aith-e-search-kbd {
            margin-left: auto;
            padding: 0.125rem 0.5rem; border-radius: 0.25rem;
            background: rgba(255,255,255,0.1);
            font-size: 0.75rem; color: rgba(255,255,255,0.3);
            font-family: monospace;
        }

        /* Smart Dashboard */
        .aith-e-section { margin-bottom: 2.5rem; }
        .aith-e-section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .aith-e-section-title {
            font-size: 1rem; font-weight: 600; color: rgba(255,255,255,0.7);
            display: flex; align-items: center; gap: 0.5rem;
        }
        .aith-e-section-action {
            font-size: 0.8rem; color: rgba(255,255,255,0.3);
            cursor: pointer; transition: color 0.2s;
        }
        .aith-e-section-action:hover { color: rgba(255,255,255,0.6); }

        /* Tool Cards - Dashboard (compact) */
        .aith-e-dash-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        @media (max-width: 992px) { .aith-e-dash-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .aith-e-dash-grid { grid-template-columns: 1fr; } }

        .aith-e-dash-card {
            display: flex; flex-direction: column; align-items: center;
            padding: 1.25rem 1rem; border-radius: 0.75rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none !important;
        }
        .aith-e-dash-card:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(139,92,246,0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(139,92,246,0.1);
        }
        .aith-e-dash-card-emoji { font-size: 2rem; margin-bottom: 0.5rem; }
        .aith-e-dash-card-name { font-size: 0.875rem; font-weight: 600; color: #fff; }
        .aith-e-dash-card-desc { font-size: 0.75rem; color: rgba(255,255,255,0.35); margin-top: 0.25rem; line-height: 1.4; }
        .aith-e-dash-card.recommended {
            background: rgba(139,92,246,0.08);
            border-color: rgba(139,92,246,0.2);
        }

        /* Browse All Button */
        .aith-e-browse-all {
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            width: 100%; padding: 0.875rem;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, rgba(139,92,246,0.15), rgba(236,72,153,0.15));
            border: 1px solid rgba(139,92,246,0.3);
            color: #c4b5fd;
            font-size: 0.95rem; font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .aith-e-browse-all:hover {
            background: linear-gradient(135deg, rgba(139,92,246,0.25), rgba(236,72,153,0.25));
            border-color: rgba(139,92,246,0.5);
            transform: translateY(-1px);
        }

        /* Category Tabs */
        .aith-e-tabs {
            display: flex; gap: 0.5rem; margin-bottom: 1.5rem;
            overflow-x: auto; padding-bottom: 0.25rem;
            -ms-overflow-style: none; scrollbar-width: none;
        }
        .aith-e-tabs::-webkit-scrollbar { display: none; }
        .aith-e-tab {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1rem; border-radius: 0.5rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem; font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .aith-e-tab:hover {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.7);
        }
        .aith-e-tab.active {
            background: rgba(139,92,246,0.2);
            border-color: rgba(139,92,246,0.4);
            color: #c4b5fd;
        }
        .aith-e-tab-count {
            padding: 0.125rem 0.375rem; border-radius: 9999px;
            background: rgba(255,255,255,0.1);
            font-size: 0.7rem;
        }
        .aith-e-tab.active .aith-e-tab-count {
            background: rgba(139,92,246,0.3);
        }

        /* Tool Cards - Grid (full) */
        .aith-e-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }
        @media (max-width: 992px) { .aith-e-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .aith-e-grid { grid-template-columns: 1fr; } }

        .aith-e-card {
            display: flex; flex-direction: column;
            padding: 1.5rem; border-radius: 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            transition: all 0.3s;
            text-decoration: none !important;
            height: 100%;
        }
        .aith-e-card:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.15);
            transform: scale(1.02);
            box-shadow: 0 8px 32px rgba(124,58,237,0.08);
        }
        .aith-e-card-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.75rem; }
        .aith-e-card-icon {
            width: 3rem; height: 3rem; border-radius: 0.75rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }
        .aith-e-card-badge {
            padding: 0.2rem 0.6rem; border-radius: 9999px;
            font-size: 0.65rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .aith-e-badge-optimization { background: rgba(59,130,246,0.15); color: #93c5fd; }
        .aith-e-badge-analytics { background: rgba(249,115,22,0.15); color: #fdba74; }
        .aith-e-badge-monetization { background: rgba(34,197,94,0.15); color: #86efac; }
        .aith-e-badge-content { background: rgba(245,158,11,0.15); color: #fcd34d; }

        .aith-e-card-name { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 0.375rem; }
        .aith-e-card-desc { color: rgba(255,255,255,0.4); font-size: 0.8rem; line-height: 1.5; flex: 1; }
        .aith-e-card-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.06); }
        .aith-e-card-credits { font-size: 0.75rem; color: rgba(255,255,255,0.3); display: flex; align-items: center; gap: 0.25rem; }
        .aith-e-card-launch {
            font-size: 0.8rem; font-weight: 600; color: #c4b5fd;
            display: flex; align-items: center; gap: 0.25rem;
            transition: transform 0.2s;
        }
        .aith-e-card:hover .aith-e-card-launch { transform: translateX(4px); }

        /* Color classes for card icons */
        .aith-e-icon-blue-indigo { background: linear-gradient(135deg, #3b82f6, #4f46e5); }
        .aith-e-icon-purple-violet { background: linear-gradient(135deg, #a855f7, #7c3aed); }
        .aith-e-icon-pink-rose { background: linear-gradient(135deg, #ec4899, #e11d48); }
        .aith-e-icon-green-emerald { background: linear-gradient(135deg, #22c55e, #059669); }
        .aith-e-icon-amber-orange { background: linear-gradient(135deg, #f59e0b, #ea580c); }
        .aith-e-icon-purple-pink { background: linear-gradient(135deg, #a855f7, #ec4899); }
        .aith-e-icon-blue-cyan { background: linear-gradient(135deg, #3b82f6, #06b6d4); }
        .aith-e-icon-orange-red { background: linear-gradient(135deg, #f97316, #ef4444); }
        .aith-e-icon-indigo-purple { background: linear-gradient(135deg, #6366f1, #a855f7); }
        .aith-e-icon-yellow-orange { background: linear-gradient(135deg, #eab308, #f97316); }
        .aith-e-icon-teal-cyan { background: linear-gradient(135deg, #14b8a6, #06b6d4); }
        .aith-e-icon-rose-pink { background: linear-gradient(135deg, #f43f5e, #ec4899); }

        /* Command Palette */
        .aith-e-palette-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex; justify-content: center;
            padding-top: 10vh;
        }
        .aith-e-palette {
            width: 100%; max-width: 580px; height: fit-content;
            background: rgba(30,27,75,0.95);
            border: 1px solid rgba(139,92,246,0.3);
            border-radius: 1rem;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            overflow: hidden;
        }
        .aith-e-palette-header {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .aith-e-palette-header i { color: rgba(255,255,255,0.3); font-size: 1rem; }
        .aith-e-palette-input {
            flex: 1; background: none; border: none; outline: none;
            color: #fff; font-size: 1rem;
        }
        .aith-e-palette-input::placeholder { color: rgba(255,255,255,0.3); }
        .aith-e-palette-esc {
            padding: 0.25rem 0.5rem; border-radius: 0.25rem;
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.3);
            font-size: 0.7rem; cursor: pointer;
        }
        .aith-e-palette-body {
            max-height: 400px; overflow-y: auto;
            padding: 0.5rem;
        }
        .aith-e-palette-body::-webkit-scrollbar { width: 4px; }
        .aith-e-palette-body::-webkit-scrollbar-track { background: transparent; }
        .aith-e-palette-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }
        .aith-e-palette-section {
            padding: 0.5rem 0.75rem 0.25rem;
            font-size: 0.7rem; font-weight: 600;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase; letter-spacing: 1px;
        }
        .aith-e-palette-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.625rem 0.75rem; border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .aith-e-palette-item:hover { background: rgba(139,92,246,0.15); }
        .aith-e-palette-item-icon { font-size: 1.25rem; width: 2rem; text-align: center; }
        .aith-e-palette-item-content { flex: 1; min-width: 0; }
        .aith-e-palette-item-name { font-size: 0.875rem; font-weight: 600; color: #fff; }
        .aith-e-palette-item-desc { font-size: 0.75rem; color: rgba(255,255,255,0.35); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .aith-e-palette-item-hint { font-size: 0.7rem; color: rgba(255,255,255,0.2); }
        .aith-e-palette-item.recent { border-left: 2px solid rgba(139,92,246,0.4); }
        .aith-e-palette-footer {
            display: flex; align-items: center; gap: 1.5rem;
            padding: 0.75rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            font-size: 0.7rem; color: rgba(255,255,255,0.25);
        }
        .aith-e-palette-footer kbd {
            padding: 0.125rem 0.375rem; border-radius: 0.2rem;
            background: rgba(255,255,255,0.08);
            font-family: monospace;
        }

        /* Recent Activity */
        .aith-e-activity { border-radius: 0.75rem; overflow: hidden; border: 1px solid rgba(255,255,255,0.06); }
        .aith-e-activity-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.2s;
        }
        .aith-e-activity-item:last-child { border-bottom: none; }
        .aith-e-activity-item:hover { background: rgba(255,255,255,0.06); }
        .aith-e-activity-left { display: flex; align-items: center; gap: 0.75rem; min-width: 0; flex: 1; }
        .aith-e-activity-dot { width: 8px; height: 8px; border-radius: 50%; background: #7c3aed; flex-shrink: 0; }
        .aith-e-activity-info { min-width: 0; flex: 1; }
        .aith-e-activity-label { color: rgba(255,255,255,0.6); font-size: 0.8rem; font-weight: 500; }
        .aith-e-activity-title { color: rgba(255,255,255,0.35); font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .aith-e-activity-time { color: rgba(255,255,255,0.2); font-size: 0.75rem; flex-shrink: 0; margin-left: 0.75rem; }

        /* Empty state */
        .aith-e-empty { text-align: center; padding: 3rem 0; }
        .aith-e-empty-icon { font-size: 3rem; margin-bottom: 0.75rem; }
        .aith-e-empty-title { font-size: 1.1rem; font-weight: 600; color: rgba(255,255,255,0.6); margin-bottom: 0.25rem; }
        .aith-e-empty-text { font-size: 0.8rem; color: rgba(255,255,255,0.3); }

        /* View toggle */
        .aith-e-view-toggle {
            display: inline-flex; align-items: center; gap: 0.25rem;
            padding: 0.25rem; border-radius: 0.5rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .aith-e-view-btn {
            padding: 0.375rem 0.75rem; border-radius: 0.375rem;
            font-size: 0.8rem; color: rgba(255,255,255,0.4);
            cursor: pointer; transition: all 0.2s;
        }
        .aith-e-view-btn:hover { color: rgba(255,255,255,0.6); }
        .aith-e-view-btn.active {
            background: rgba(139,92,246,0.2);
            color: #c4b5fd;
        }

        @media (min-width: 768px) {
            .aith-enterprise { padding: 2.5rem 2rem 3rem; }
            .aith-e-title { font-size: 2rem; }
        }
    </style>

    {{-- Aurora Background --}}
    <div class="aith-e-aurora">
        <div class="aith-e-aurora-blob aith-e-aurora-blob--1"></div>
        <div class="aith-e-aurora-blob aith-e-aurora-blob--2"></div>
        <div class="aith-e-aurora-blob aith-e-aurora-blob--3"></div>
    </div>

    <div class="aith-e-content">

        {{-- Header --}}
        <div class="aith-e-header">
            <div>
                <div class="aith-e-header-left">
                    <a href="{{ route('app.ai-tools.index') }}" class="aith-e-back">
                        <i class="fa-light fa-arrow-left"></i> Hub
                    </a>
                    <h1 class="aith-e-title">Enterprise Suite</h1>
                </div>
                <p class="aith-e-subtitle">15 premium AI tools to grow and monetize your channel</p>
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem;">
                {{-- View Toggle --}}
                <div class="aith-e-view-toggle">
                    <div class="aith-e-view-btn {{ $viewMode === 'dashboard' ? 'active' : '' }}"
                         wire:click="setViewMode('dashboard')">
                        <i class="fa-light fa-grid-2"></i>
                    </div>
                    <div class="aith-e-view-btn {{ $viewMode === 'grid' ? 'active' : '' }}"
                         wire:click="setViewMode('grid')">
                        <i class="fa-light fa-list"></i>
                    </div>
                </div>
                {{-- Search Trigger --}}
                <div class="aith-e-search-trigger" @click="openPalette()">
                    <i class="fa-light fa-magnifying-glass"></i>
                    <span>Search tools...</span>
                    <span class="aith-e-search-kbd">Ctrl+K</span>
                </div>
            </div>
        </div>

        {{-- ============ SMART DASHBOARD VIEW ============ --}}
        @if($viewMode === 'dashboard')

            {{-- Recent Tools --}}
            @if(count($recentTools ?? []) > 0 || true)
            <div class="aith-e-section" x-show="recentTools.length > 0">
                <div class="aith-e-section-header">
                    <span class="aith-e-section-title">
                        <i class="fa-light fa-clock-rotate-left"></i> Recent Tools
                    </span>
                    <span class="aith-e-section-action" @click="clearRecent()">Clear</span>
                </div>
                <div class="aith-e-dash-grid">
                    <template x-for="tool in recentTools.slice(0, 4)" :key="tool.key">
                        <a :href="tool.route" class="aith-e-dash-card">
                            <div class="aith-e-dash-card-emoji" x-text="tool.emoji"></div>
                            <div class="aith-e-dash-card-name" x-text="tool.name"></div>
                        </a>
                    </template>
                </div>
            </div>
            @endif

            {{-- Recommended Tools --}}
            <div class="aith-e-section">
                <div class="aith-e-section-header">
                    <span class="aith-e-section-title">
                        <i class="fa-light fa-sparkles"></i> Recommended for You
                    </span>
                </div>
                <div class="aith-e-dash-grid">
                    <template x-for="tool in recommendedTools.slice(0, 4)" :key="tool.key">
                        <a :href="tool.route" class="aith-e-dash-card recommended">
                            <div class="aith-e-dash-card-emoji" x-text="tool.emoji"></div>
                            <div class="aith-e-dash-card-name" x-text="tool.name"></div>
                            <div class="aith-e-dash-card-desc" x-text="tool.description"></div>
                        </a>
                    </template>
                </div>
            </div>

            {{-- Browse All --}}
            <div class="aith-e-section">
                <div class="aith-e-browse-all" wire:click="browseAll">
                    <i class="fa-light fa-grid-2-plus"></i>
                    Browse All 15 Tools
                </div>
            </div>

            {{-- Recent Activity --}}
            @if(count($recentActivity) > 0)
            <div class="aith-e-section">
                <div class="aith-e-section-header">
                    <span class="aith-e-section-title">
                        <i class="fa-light fa-clock"></i> Recent Activity
                    </span>
                </div>
                <div class="aith-e-activity">
                    @foreach($recentActivity as $activity)
                    <div class="aith-e-activity-item">
                        <div class="aith-e-activity-left">
                            <div class="aith-e-activity-dot"></div>
                            <div class="aith-e-activity-info">
                                <div class="aith-e-activity-label">{{ $activity['tool_label'] }}</div>
                                <div class="aith-e-activity-title">{{ $activity['title'] }}</div>
                            </div>
                        </div>
                        <span class="aith-e-activity-time">{{ $activity['time_ago'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        @else

        {{-- ============ GRID VIEW ============ --}}

            {{-- Category Tabs --}}
            <div class="aith-e-tabs">
                @foreach($categories as $catKey => $cat)
                @php
                    $count = $catKey === 'all'
                        ? count($tools)
                        : count(array_filter($tools, fn($t) => ($t['category'] ?? '') === $catKey));
                @endphp
                <div class="aith-e-tab {{ $activeCategory === $catKey ? 'active' : '' }}"
                     wire:click="setCategory('{{ $catKey }}')">
                    <i class="{{ $cat['icon'] }}"></i>
                    {{ $cat['name'] }}
                    <span class="aith-e-tab-count">{{ $count }}</span>
                </div>
                @endforeach
            </div>

            {{-- Tools Grid --}}
            <div class="aith-e-grid">
                @php
                    $iconClasses = [
                        'bulk-optimizer'          => 'aith-e-icon-blue-indigo',
                        'placement-finder'        => 'aith-e-icon-purple-violet',
                        'viral-predictor'         => 'aith-e-icon-pink-rose',
                        'monetization-analyzer'   => 'aith-e-icon-green-emerald',
                        'script-writer'           => 'aith-e-icon-amber-orange',
                        'sponsorship-calculator'  => 'aith-e-icon-purple-pink',
                        'revenue-diversification' => 'aith-e-icon-blue-cyan',
                        'cpm-booster'             => 'aith-e-icon-green-emerald',
                        'audience-profiler'       => 'aith-e-icon-orange-red',
                        'digital-product-architect' => 'aith-e-icon-indigo-purple',
                        'affiliate-finder'        => 'aith-e-icon-yellow-orange',
                        'multi-income-converter'  => 'aith-e-icon-teal-cyan',
                        'brand-deal-matchmaker'   => 'aith-e-icon-rose-pink',
                        'licensing-scout'         => 'aith-e-icon-teal-cyan',
                        'revenue-automation'      => 'aith-e-icon-orange-red',
                    ];
                    $badgeClasses = [
                        'optimization'  => 'aith-e-badge-optimization',
                        'analytics'     => 'aith-e-badge-analytics',
                        'monetization'  => 'aith-e-badge-monetization',
                        'content'       => 'aith-e-badge-content',
                    ];
                @endphp

                @foreach($filteredTools as $key => $tool)
                <a href="{{ route($tool['route']) }}" class="aith-e-card"
                   @click="trackRecent('{{ $key }}')">
                    <div class="aith-e-card-top">
                        <div class="aith-e-card-icon {{ $iconClasses[$key] ?? 'aith-e-icon-blue-indigo' }}">
                            {{ $tool['emoji'] }}
                        </div>
                        <span class="aith-e-card-badge {{ $badgeClasses[$tool['category'] ?? ''] ?? 'aith-e-badge-optimization' }}">
                            {{ ucfirst($tool['category'] ?? 'tool') }}
                        </span>
                    </div>
                    <div class="aith-e-card-name">{{ __($tool['name']) }}</div>
                    <div class="aith-e-card-desc">{{ __($tool['description']) }}</div>
                    <div class="aith-e-card-footer">
                        <span class="aith-e-card-credits">
                            <i class="fa-light fa-coins"></i> {{ $tool['credits'] }} credits
                        </span>
                        <span class="aith-e-card-launch">
                            Launch <i class="fa-light fa-arrow-right"></i>
                        </span>
                    </div>
                </a>
                @endforeach
            </div>

        @endif

    </div>

    {{-- ============ COMMAND PALETTE ============ --}}
    <div x-show="paletteOpen" x-transition.opacity
         class="aith-e-palette-overlay" @click.self="closePalette()" @keydown.escape.window="closePalette()" style="display:none;">
        <div class="aith-e-palette" @click.stop>
            <div class="aith-e-palette-header">
                <i class="fa-light fa-magnifying-glass"></i>
                <input type="text" class="aith-e-palette-input"
                       placeholder="Search tools..."
                       x-model="paletteQuery"
                       x-ref="paletteInput"
                       @keydown.enter="selectFirst()">
                <span class="aith-e-palette-esc" @click="closePalette()">ESC</span>
            </div>
            <div class="aith-e-palette-body">
                {{-- Recent section --}}
                <template x-if="paletteQuery === '' && recentTools.length > 0">
                    <div>
                        <div class="aith-e-palette-section">Recent</div>
                        <template x-for="tool in recentTools.slice(0, 3)" :key="'r-' + tool.key">
                            <a :href="tool.route" class="aith-e-palette-item recent" style="text-decoration:none;">
                                <span class="aith-e-palette-item-icon" x-text="tool.emoji"></span>
                                <div class="aith-e-palette-item-content">
                                    <div class="aith-e-palette-item-name" x-text="tool.name"></div>
                                    <div class="aith-e-palette-item-desc" x-text="tool.description"></div>
                                </div>
                                <span class="aith-e-palette-item-hint">Enter</span>
                            </a>
                        </template>
                    </div>
                </template>
                {{-- All / Filtered tools --}}
                <div>
                    <div class="aith-e-palette-section" x-text="paletteQuery ? 'Results' : 'All Tools'"></div>
                    <template x-for="tool in filteredPaletteTools" :key="'p-' + tool.key">
                        <a :href="tool.route" class="aith-e-palette-item" style="text-decoration:none;">
                            <span class="aith-e-palette-item-icon" x-text="tool.emoji"></span>
                            <div class="aith-e-palette-item-content">
                                <div class="aith-e-palette-item-name" x-text="tool.name"></div>
                                <div class="aith-e-palette-item-desc" x-text="tool.description"></div>
                            </div>
                            <span class="aith-e-palette-item-hint">Enter</span>
                        </a>
                    </template>
                    <template x-if="paletteQuery && filteredPaletteTools.length === 0">
                        <div style="padding:2rem; text-align:center; color:rgba(255,255,255,0.3); font-size:0.875rem;">
                            No tools found for "<span x-text="paletteQuery"></span>"
                        </div>
                    </template>
                </div>
            </div>
            <div class="aith-e-palette-footer">
                <span><kbd>&uarr;</kbd><kbd>&darr;</kbd> Navigate</span>
                <span><kbd>Enter</kbd> Select</span>
                <span><kbd>Esc</kbd> Close</span>
            </div>
        </div>
    </div>
</div>

<script>
function enterpriseDashboard() {
    // Build tools data from server config
    const allTools = @json(collect($tools)->map(function($tool, $key) {
        return [
            'key' => $key,
            'name' => $tool['name'],
            'description' => $tool['description'],
            'emoji' => $tool['emoji'],
            'category' => $tool['category'] ?? '',
            'route' => route($tool['route']),
        ];
    })->values()->toArray());

    return {
        paletteOpen: false,
        paletteQuery: '',
        allTools: allTools,

        // Recent tools from localStorage
        get recentTools() {
            const keys = JSON.parse(localStorage.getItem('aith_enterprise_recent') || '[]');
            return keys.map(k => this.allTools.find(t => t.key === k)).filter(Boolean);
        },

        // Recommended: tools not in recent, monetization first
        get recommendedTools() {
            const recentKeys = JSON.parse(localStorage.getItem('aith_enterprise_recent') || '[]');
            return this.allTools
                .filter(t => !recentKeys.includes(t.key))
                .sort((a, b) => {
                    if (a.category === 'monetization' && b.category !== 'monetization') return -1;
                    if (a.category !== 'monetization' && b.category === 'monetization') return 1;
                    return 0;
                });
        },

        // Filtered tools for command palette
        get filteredPaletteTools() {
            if (!this.paletteQuery) return this.allTools;
            const q = this.paletteQuery.toLowerCase();
            return this.allTools.filter(t =>
                t.name.toLowerCase().includes(q) ||
                t.description.toLowerCase().includes(q) ||
                t.key.toLowerCase().includes(q)
            );
        },

        trackRecent(key) {
            let recent = JSON.parse(localStorage.getItem('aith_enterprise_recent') || '[]');
            recent = recent.filter(k => k !== key);
            recent.unshift(key);
            if (recent.length > 4) recent = recent.slice(0, 4);
            localStorage.setItem('aith_enterprise_recent', JSON.stringify(recent));
        },

        clearRecent() {
            localStorage.removeItem('aith_enterprise_recent');
        },

        openPalette() {
            this.paletteOpen = true;
            this.paletteQuery = '';
            this.$nextTick(() => this.$refs.paletteInput?.focus());
        },

        closePalette() {
            this.paletteOpen = false;
            this.paletteQuery = '';
        },

        selectFirst() {
            const tools = this.filteredPaletteTools;
            if (tools.length > 0) {
                this.trackRecent(tools[0].key);
                window.location.href = tools[0].route;
            }
        },

        // Keyboard shortcut
        init() {
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    if (this.paletteOpen) {
                        this.closePalette();
                    } else {
                        this.openPalette();
                    }
                }
            });
        }
    };
}
</script>
