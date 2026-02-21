{{-- Writer's Room - Phase 2: Professional Multi-Panel Writing Interface --}}
{{-- A dedicated creative workspace that brings Story Bible, Script, and Storyboard together --}}

@if($showWritersRoom ?? false)
@php
    $scenes = $script['scenes'] ?? [];
    $sceneCount = count($scenes);
@endphp
<div class="writers-room-overlay"
     x-data="writersRoom()"
     x-init="init()"
     @keydown.escape.window="$wire.closeWritersRoom()">

    <style>
        /* Writer's Room Core Styles */
        .writers-room-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000100;
            background: #0f0f14;
            display: flex;
            flex-direction: column;
            animation: wr-fadeIn 0.3s ease;
        }

        @keyframes wr-fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Header Bar */
        .wr-header {
            height: 56px;
            background: linear-gradient(135deg, rgba(30, 30, 45, 0.98) 0%, rgba(20, 20, 35, 0.98) 100%);
            border-bottom: 1px solid rgba(var(--vw-primary-rgb), 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            flex-shrink: 0;
        }

        .wr-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .wr-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--vw-primary) 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .wr-project-name {
            padding: 0.375rem 0.75rem;
            background: rgba(var(--vw-primary-rgb), 0.06);
            border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
            border-radius: 0.375rem;
            color: var(--vw-text-secondary);
            font-size: 0.875rem;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wr-header-center {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .wr-view-btn {
            padding: 0.5rem 1rem;
            background: rgba(0,0,0,0.03);
            border: 1px solid var(--vw-border);
            border-radius: 0.375rem;
            color: var(--vw-text-secondary);
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .wr-view-btn:hover {
            background: var(--vw-border);
            color: var(--vw-text);
        }

        .wr-view-btn.active {
            background: linear-gradient(135deg, rgba(var(--vw-primary-rgb), 0.12) 0%, rgba(6, 182, 212, 0.3) 100%);
            border-color: var(--vw-border-focus);
        color: var(--vw-text);
        }

        .wr-header-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .wr-btn-save {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 0.375rem;
        color: var(--vw-text);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .wr-btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .wr-btn-close {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 0.375rem;
            color: #f87171;
            cursor: pointer;
            transition: all 0.2s;
        }

        .wr-btn-close:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
        }

        /* Main Content Area */
        .wr-main {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        /* Panels */
        .wr-panel {
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .wr-panel:last-child {
            border-right: none;
        }

        .wr-panel-header {
            height: 44px;
            background: rgba(0,0,0,0.02);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            flex-shrink: 0;
        }

        .wr-panel-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--vw-text);
        }

        .wr-panel-title-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .wr-panel-actions {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .wr-panel-btn {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            border-radius: 0.25rem;
            color: var(--vw-text-secondary);
            cursor: pointer;
            transition: all 0.15s;
        }

        .wr-panel-btn:hover {
            background: var(--vw-border);
            color: var(--vw-text);
        }

        .wr-panel-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        /* Story Bible Panel (Left) */
        .wr-bible-panel {
            width: 280px;
            min-width: 280px;
            background: rgba(15, 15, 20, 0.95);
        }

        .wr-bible-section {
            margin-bottom: 1.5rem;
        }

        .wr-bible-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--vw-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            user-select: none;
        }

        .wr-bible-section-title:hover {
            color: var(--vw-text);
        }

        .wr-bible-item {
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            padding: 0.625rem;
            background: rgba(0,0,0,0.02);
            border: 1px solid rgba(0,0,0,0.03);
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.15s;
        }

        .wr-bible-item:hover {
            background: rgba(var(--vw-primary-rgb), 0.04);
            border-color: rgba(var(--vw-primary-rgb), 0.12);
        }

        .wr-bible-item-avatar {
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .wr-bible-item-avatar.character {
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
        color: var(--vw-text);
        }

        .wr-bible-item-avatar.location {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: var(--vw-text);
        }

        .wr-bible-item-info {
            flex: 1;
            min-width: 0;
        }

        .wr-bible-item-name {
            font-size: 0.85rem;
            font-weight: 600;
        color: var(--vw-text);
            margin-bottom: 0.125rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .wr-bible-item-role {
            font-size: 0.7rem;
            color: var(--vw-text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Script Panel (Center) */
        .wr-script-panel {
            flex: 1;
            background: rgba(18, 18, 24, 0.95);
            min-width: 400px;
        }

        .wr-scene-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wr-scene-card {
            background: rgba(30, 30, 40, 0.8);
            border: 1px solid rgba(0,0,0,0.04);
            border-radius: 0.75rem;
            overflow: hidden;
            transition: all 0.2s;
        }

        .wr-scene-card:hover {
            border-color: rgba(var(--vw-primary-rgb), 0.12);
        }

        .wr-scene-card.active {
            border-color: var(--vw-border-focus);
            box-shadow: 0 0 20px rgba(var(--vw-primary-rgb), 0.06);
        }

        .wr-scene-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        .wr-scene-number {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .wr-scene-badge {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--vw-primary) 0%, #06b6d4 100%);
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 700;
        color: var(--vw-text);
        }

        .wr-scene-title-input {
            background: transparent;
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
        color: var(--vw-text);
            width: 200px;
        }

        .wr-scene-title-input:focus {
            outline: none;
            background: rgba(0,0,0,0.03);
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            margin: -0.25rem -0.5rem;
        }

        .wr-scene-duration {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8rem;
            color: var(--vw-text-secondary);
        }

        .wr-scene-body {
            padding: 1rem;
        }

        .wr-scene-section {
            margin-bottom: 1rem;
        }

        .wr-scene-section:last-child {
            margin-bottom: 0;
        }

        .wr-scene-label {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--vw-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .wr-scene-textarea {
            width: 100%;
            min-height: 80px;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--vw-border);
            border-radius: 0.5rem;
            color: var(--vw-text);
            font-size: 0.9rem;
            line-height: 1.6;
            resize: vertical;
            font-family: inherit;
        }

        .wr-scene-textarea:focus {
            outline: none;
            border-color: var(--vw-border-focus);
            box-shadow: 0 0 0 3px rgba(var(--vw-primary-rgb), 0.04);
        }

        .wr-scene-textarea.narration {
            min-height: 100px;
        }

        .wr-scene-textarea.visual {
            min-height: 60px;
            color: rgba(96, 165, 250, 0.9);
        }

        .wr-scene-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.15);
            border-top: 1px solid rgba(0,0,0,0.03);
        }

        .wr-scene-references {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            flex-wrap: wrap;
        }

        .wr-ref-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .wr-ref-tag.character {
            background: rgba(236, 72, 153, 0.15);
            color: #f472b6;
        }

        .wr-ref-tag.location {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        .wr-scene-btns {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .wr-scene-btn {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.03);
            border: 1px solid var(--vw-border);
            border-radius: 0.25rem;
            color: var(--vw-text-secondary);
            cursor: pointer;
            transition: all 0.15s;
        }

        .wr-scene-btn:hover {
            background: var(--vw-border);
            color: var(--vw-text);
        }

        .wr-scene-btn.danger:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
            color: #f87171;
        }

        /* Storyboard Panel (Right) */
        .wr-storyboard-panel {
            width: 320px;
            min-width: 320px;
            background: rgba(15, 15, 20, 0.95);
        }

        .wr-storyboard-preview {
            aspect-ratio: 16/9;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--vw-border);
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wr-storyboard-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .wr-storyboard-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            color: var(--vw-text-secondary);
        }

        .wr-storyboard-placeholder svg {
            width: 48px;
            height: 48px;
            opacity: 0.5;
        }

        .wr-storyboard-thumbnails {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        .wr-thumb {
            aspect-ratio: 16/9;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid transparent;
            border-radius: 0.375rem;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.15s;
            position: relative;
        }

        .wr-thumb:hover {
            border-color: var(--vw-border-focus);
        }

        .wr-thumb.active {
            border-color: var(--vw-primary);
        }

        .wr-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .wr-thumb-number {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
        color: var(--vw-text);
        }

        .wr-thumb-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--vw-border);
            font-size: 0.7rem;
        }

        /* Stats Bar */
        .wr-stats-bar {
            height: 36px;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            padding: 0 1.5rem;
            flex-shrink: 0;
        }

        .wr-stat {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            color: var(--vw-text-secondary);
        }

        .wr-stat-value {
            color: var(--vw-primary);
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .wr-storyboard-panel {
                display: none;
            }
        }

        @media (max-width: 900px) {
            .wr-bible-panel {
                display: none;
            }
        }

        /* Reference Popover */
        .wr-popover {
            position: fixed;
            z-index: 1000150;
            background: rgba(30, 30, 45, 0.98);
            border: 1px solid var(--vw-border-accent);
            border-radius: 0.75rem;
            padding: 1rem;
            width: 280px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: wr-popIn 0.2s ease;
        }

        @keyframes wr-popIn {
            from { opacity: 0; transform: scale(0.95) translateY(-5px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .wr-popover-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--vw-border);
        }

        .wr-popover-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 600;
        color: var(--vw-text);
        }

        .wr-popover-avatar.character {
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
        }

        .wr-popover-avatar.location {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        .wr-popover-name {
            font-size: 1rem;
            font-weight: 600;
        color: var(--vw-text);
        }

        .wr-popover-role {
            font-size: 0.75rem;
            color: var(--vw-text-secondary);
        }

        .wr-popover-desc {
            font-size: 0.85rem;
            color: var(--vw-text);
            line-height: 1.5;
        }

        .wr-popover-traits {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            margin-top: 0.75rem;
        }

        .wr-trait-tag {
            padding: 0.25rem 0.5rem;
            background: rgba(var(--vw-primary-rgb), 0.06);
            border-radius: 0.25rem;
            font-size: 0.7rem;
            color: var(--vw-text-secondary);
        }
    </style>

    {{-- Header Bar --}}
    <div class="wr-header">
        <div class="wr-header-left">
            <div class="wr-logo">
                <span>✍️</span>
                <span>Writer's Room</span>
            </div>
            @if($projectName ?? null)
                <div class="wr-project-name">{{ $projectName }}</div>
            @endif
        </div>

        <div class="wr-header-center">
            <button class="wr-view-btn" :class="{ 'active': view === 'full' }" @click="view = 'full'">
                Full View
            </button>
            <button class="wr-view-btn" :class="{ 'active': view === 'script' }" @click="view = 'script'">
                Script Only
            </button>
            <button class="wr-view-btn" :class="{ 'active': view === 'focus' }" @click="view = 'focus'">
                Focus Mode
            </button>
        </div>

        <div class="wr-header-right">
            <button class="wr-btn-save" wire:click="saveProject" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveProject">💾 Save</span>
                <span wire:loading wire:target="saveProject">⏳ Saving...</span>
            </button>
            <button class="wr-btn-close" wire:click="closeWritersRoom" title="Exit Writer's Room">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="wr-main">
        {{-- Story Bible Panel (Left) --}}
        <div class="wr-panel wr-bible-panel" x-show="view === 'full'" x-transition>
            <div class="wr-panel-header">
                <div class="wr-panel-title">
                    <span class="wr-panel-title-icon">📖</span>
                    <span>Story Bible</span>
                </div>
                <div class="wr-panel-actions">
                    <button class="wr-panel-btn" wire:click="openStoryBibleModal" title="Edit Story Bible">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="wr-panel-content">
                {{-- Story Info --}}
                @if(!empty($storyBible['title']))
                    <div class="wr-bible-section">
                        <div style="font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 0.375rem;">
                            {{ $storyBible['title'] }}
                        </div>
                        @if(!empty($storyBible['logline']))
                            <div style="font-size: 0.8rem; color: var(--vw-text-secondary); line-height: 1.5;">
                                {{ $storyBible['logline'] }}
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Characters --}}
                <div class="wr-bible-section">
                    <div class="wr-bible-section-title" x-data="{ open: true }" @click="open = !open">
                        <span>Characters ({{ count($storyBible['characters'] ?? []) }})</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="{ transform: open ? 'rotate(180deg)' : 'rotate(0)' }">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div x-show="open" x-collapse>
                        @forelse($storyBible['characters'] ?? [] as $index => $character)
                            <div class="wr-bible-item"
                                 @mouseenter="showPopover('character', {{ $index }}, $event)"
                                 @mouseleave="hidePopover()">
                                <div class="wr-bible-item-avatar character">
                                    {{ strtoupper(substr($character['name'] ?? 'C', 0, 1)) }}
                                </div>
                                <div class="wr-bible-item-info">
                                    <div class="wr-bible-item-name">{{ $character['name'] ?? 'Unnamed' }}</div>
                                    <div class="wr-bible-item-role">{{ ucfirst($character['role'] ?? 'character') }}</div>
                                </div>
                            </div>
                        @empty
                            <div style="padding: 1rem; text-align: center; color: var(--vw-text-secondary); font-size: 0.8rem;">
                                No characters defined.<br>
                                <button wire:click="openStoryBibleModal" style="color: var(--vw-primary); cursor: pointer; background: none; border: none; margin-top: 0.5rem;">
                                    + Add from Story Bible
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Locations --}}
                <div class="wr-bible-section">
                    <div class="wr-bible-section-title" x-data="{ open: true }" @click="open = !open">
                        <span>Locations ({{ count($storyBible['locations'] ?? []) }})</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="{ transform: open ? 'rotate(180deg)' : 'rotate(0)' }">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div x-show="open" x-collapse>
                        @forelse($storyBible['locations'] ?? [] as $index => $location)
                            <div class="wr-bible-item"
                                 @mouseenter="showPopover('location', {{ $index }}, $event)"
                                 @mouseleave="hidePopover()">
                                <div class="wr-bible-item-avatar location">
                                    📍
                                </div>
                                <div class="wr-bible-item-info">
                                    <div class="wr-bible-item-name">{{ $location['name'] ?? 'Unnamed' }}</div>
                                    <div class="wr-bible-item-role">{{ ucfirst($location['type'] ?? 'location') }}</div>
                                </div>
                            </div>
                        @empty
                            <div style="padding: 1rem; text-align: center; color: var(--vw-text-secondary); font-size: 0.8rem;">
                                No locations defined.<br>
                                <button wire:click="openStoryBibleModal" style="color: var(--vw-primary); cursor: pointer; background: none; border: none; margin-top: 0.5rem;">
                                    + Add from Story Bible
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Visual Style --}}
                @if(!empty($storyBible['visualStyle']))
                    <div class="wr-bible-section">
                        <div class="wr-bible-section-title" x-data="{ open: false }" @click="open = !open">
                            <span>Visual Style</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="{ transform: open ? 'rotate(180deg)' : 'rotate(0)' }">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div x-show="open" x-collapse>
                            <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 0.5rem; font-size: 0.8rem; color: var(--vw-text); line-height: 1.5;">
                                @if(!empty($storyBible['visualStyle']['mode']))
                                    <div style="margin-bottom: 0.5rem;">
                                        <span style="color: var(--vw-text-secondary);">Mode:</span>
                                        {{ ucfirst($storyBible['visualStyle']['mode']) }}
                                    </div>
                                @endif
                                @if(!empty($storyBible['visualStyle']['lighting']))
                                    <div style="margin-bottom: 0.5rem;">
                                        <span style="color: var(--vw-text-secondary);">Lighting:</span>
                                        {{ $storyBible['visualStyle']['lighting'] }}
                                    </div>
                                @endif
                                @if(!empty($storyBible['visualStyle']['colorPalette']))
                                    <div style="display: flex; align-items: center; gap: 0.375rem; flex-wrap: wrap;">
                                        <span style="color: var(--vw-text-secondary);">Colors:</span>
                                        @foreach($storyBible['visualStyle']['colorPalette'] as $color)
                                            <span style="width: 20px; height: 20px; border-radius: 4px; background: {{ $color }}; border: 1px solid var(--vw-border);"></span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Script Panel (Center) --}}
        <div class="wr-panel wr-script-panel">
            <div class="wr-panel-header">
                <div class="wr-panel-title">
                    <span class="wr-panel-title-icon">📝</span>
                    <span>Script</span>
                    <span style="font-size: 0.7rem; color: var(--vw-text-secondary); margin-left: 0.5rem;">
                        {{ count($scenes) }} scenes
                    </span>
                </div>
                <div class="wr-panel-actions">
                    <button class="wr-panel-btn" wire:click="addScene" title="Add Scene">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                    <button class="wr-panel-btn" wire:click="regenerateAllScenes" title="Regenerate All">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="wr-panel-content">
                <div class="wr-scene-list">
                    @forelse($scenes as $index => $scene)
                        <div class="wr-scene-card {{ $writersRoomActiveScene === $index ? 'active' : '' }}"
                             wire:key="wr-scene-{{ $scene['id'] ?? $index }}"
                             @click="$wire.set('writersRoomActiveScene', {{ $index }})">

                            <div class="wr-scene-header">
                                <div class="wr-scene-number">
                                    <div class="wr-scene-badge">{{ $index + 1 }}</div>
                                    <input type="text"
                                           class="wr-scene-title-input"
                                           wire:model.blur="scenes.{{ $index }}.title"
                                           placeholder="Scene Title">
                                </div>
                                <div class="wr-scene-duration">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    {{ $scene['duration'] ?? 10 }}s
                                </div>
                            </div>

                            <div class="wr-scene-body">
                                {{-- Narration --}}
                                <div class="wr-scene-section">
                                    <div class="wr-scene-label">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                        </svg>
                                        Narration
                                    </div>
                                    <textarea class="wr-scene-textarea narration"
                                              wire:model.blur="scenes.{{ $index }}.narration"
                                              placeholder="What the narrator says..."></textarea>
                                </div>

                                {{-- Visual Description --}}
                                <div class="wr-scene-section">
                                    <div class="wr-scene-label">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                        Visual Description
                                    </div>
                                    <textarea class="wr-scene-textarea visual"
                                              wire:model.blur="scenes.{{ $index }}.visualDescription"
                                              placeholder="Visual elements, setting, mood..."></textarea>
                                </div>
                            </div>

                            <div class="wr-scene-actions">
                                <div class="wr-scene-references">
                                    {{-- Character/Location reference tags would go here --}}
                                    {{-- These can be auto-detected from narration --}}
                                </div>
                                <div class="wr-scene-btns">
                                    <button class="wr-scene-btn"
                                            wire:click="regenerateScene({{ $index }})"
                                            title="Regenerate Scene">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="23 4 23 10 17 10"></polyline>
                                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                        </svg>
                                    </button>
                                    <button class="wr-scene-btn"
                                            wire:click="moveSceneUp({{ $index }})"
                                            {{ $index === 0 ? 'disabled' : '' }}
                                            title="Move Up">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="18 15 12 9 6 15"></polyline>
                                        </svg>
                                    </button>
                                    <button class="wr-scene-btn"
                                            wire:click="moveSceneDown({{ $index }})"
                                            {{ $index === count($scenes) - 1 ? 'disabled' : '' }}
                                            title="Move Down">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </button>
                                    <button class="wr-scene-btn danger"
                                            wire:click="deleteScene({{ $index }})"
                                            wire:confirm="Are you sure you want to delete this scene?"
                                            title="Delete Scene">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 3rem; color: var(--vw-text-secondary);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin: 0 auto 1rem; opacity: 0.5;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <div style="font-size: 1rem; margin-bottom: 0.5rem;">No scenes yet</div>
                            <div style="font-size: 0.85rem; margin-bottom: 1rem;">Generate a script to start writing</div>
                            <button wire:click="closeWritersRoom"
                                    style="padding: 0.5rem 1rem; background: linear-gradient(135deg, var(--vw-primary), #06b6d4); border: none; border-radius: 0.375rem; color: white; cursor: pointer;">
                                Generate Script First
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Storyboard Panel (Right) --}}
        <div class="wr-panel wr-storyboard-panel" x-show="view === 'full'" x-transition>
            <div class="wr-panel-header">
                <div class="wr-panel-title">
                    <span class="wr-panel-title-icon">🎬</span>
                    <span>Storyboard</span>
                </div>
                <div class="wr-panel-actions">
                    <button class="wr-panel-btn" title="Generate Images">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="wr-panel-content">
                {{-- Main Preview --}}
                <div class="wr-storyboard-preview">
                    @if(isset($scenes[$writersRoomActiveScene ?? 0]['image']))
                        <img src="{{ $scenes[$writersRoomActiveScene ?? 0]['image'] }}" alt="Scene preview">
                    @else
                        <div class="wr-storyboard-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <span style="font-size: 0.8rem;">No image generated</span>
                        </div>
                    @endif
                </div>

                {{-- Thumbnails Grid --}}
                <div class="wr-storyboard-thumbnails">
                    @foreach($scenes as $index => $scene)
                        <div class="wr-thumb {{ ($writersRoomActiveScene ?? 0) === $index ? 'active' : '' }}"
                             wire:click="$set('writersRoomActiveScene', {{ $index }})">
                            <div class="wr-thumb-number">{{ $index + 1 }}</div>
                            @if(!empty($scene['image']))
                                <img src="{{ $scene['image'] }}" alt="Scene {{ $index + 1 }}">
                            @else
                                <div class="wr-thumb-empty">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    </svg>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="wr-stats-bar">
        <div class="wr-stat">
            <span>Scenes:</span>
            <span class="wr-stat-value">{{ count($scenes) }}</span>
        </div>
        <div class="wr-stat">
            <span>Total Duration:</span>
            <span class="wr-stat-value">{{ array_sum(array_column($scenes, 'duration')) }}s</span>
        </div>
        <div class="wr-stat">
            <span>Words:</span>
            <span class="wr-stat-value">{{ collect($scenes)->sum(fn($s) => str_word_count($s['narration'] ?? '')) }}</span>
        </div>
        <div class="wr-stat">
            <span>Characters:</span>
            <span class="wr-stat-value">{{ count($storyBible['characters'] ?? []) }}</span>
        </div>
        <div class="wr-stat">
            <span>Locations:</span>
            <span class="wr-stat-value">{{ count($storyBible['locations'] ?? []) }}</span>
        </div>
        @if(!empty($storyBible['status']) && $storyBible['status'] === 'ready')
        <div class="wr-stat" style="border-left: 1px solid var(--vw-border); padding-left: 1rem; margin-left: 0.5rem;">
            <span style="color: #10b981;">Context-Aware</span>
            <span class="wr-stat-value" style="color: #10b981;" title="Scene regeneration uses full Story Bible + surrounding scenes for better continuity">✓</span>
        </div>
        @endif
    </div>

    {{-- Reference Popover (Alpine.js managed) --}}
    <div class="wr-popover"
         x-show="popover.show"
         x-transition
         :style="{ top: popover.y + 'px', left: popover.x + 'px' }">
        <template x-if="popover.type === 'character' && popover.data">
            <div>
                <div class="wr-popover-header">
                    <div class="wr-popover-avatar character" x-text="popover.data.name?.charAt(0).toUpperCase()"></div>
                    <div>
                        <div class="wr-popover-name" x-text="popover.data.name"></div>
                        <div class="wr-popover-role" x-text="popover.data.role"></div>
                    </div>
                </div>
                <div class="wr-popover-desc" x-text="popover.data.visualDescription"></div>
                <div class="wr-popover-traits" x-show="popover.data.traits?.length">
                    <template x-for="trait in popover.data.traits || []">
                        <span class="wr-trait-tag" x-text="trait"></span>
                    </template>
                </div>
            </div>
        </template>
        <template x-if="popover.type === 'location' && popover.data">
            <div>
                <div class="wr-popover-header">
                    <div class="wr-popover-avatar location">📍</div>
                    <div>
                        <div class="wr-popover-name" x-text="popover.data.name"></div>
                        <div class="wr-popover-role" x-text="popover.data.type"></div>
                    </div>
                </div>
                <div class="wr-popover-desc" x-text="popover.data.visualDescription"></div>
            </div>
        </template>
    </div>

    <script>
        function writersRoom() {
            return {
                view: 'full',
                popover: {
                    show: false,
                    type: null,
                    data: null,
                    x: 0,
                    y: 0
                },

                init() {
                    console.log('Writer\'s Room initialized');
                },

                showPopover(type, index, event) {
                    const data = type === 'character'
                        ? @json($storyBible['characters'] ?? [])[index]
                        : @json($storyBible['locations'] ?? [])[index];

                    if (!data) return;

                    const rect = event.target.getBoundingClientRect();
                    this.popover = {
                        show: true,
                        type: type,
                        data: data,
                        x: rect.right + 10,
                        y: rect.top
                    };

                    // Adjust if would go off screen
                    if (this.popover.x + 280 > window.innerWidth) {
                        this.popover.x = rect.left - 290;
                    }
                    if (this.popover.y + 200 > window.innerHeight) {
                        this.popover.y = window.innerHeight - 210;
                    }
                },

                hidePopover() {
                    this.popover.show = false;
                }
            }
        }
    </script>
</div>
@endif
