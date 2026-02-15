{{-- Social Content: Viral Idea Generator + Video Concept Cloner --}}
<style>
    .vw-social-concept .vw-viral-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .vw-social-concept .vw-viral-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .vw-social-concept .vw-viral-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #f1f5f9;
    }
    .vw-social-concept .vw-viral-subtitle {
        font-size: 0.875rem;
        color: #94a3b8;
        margin-top: 0.25rem;
    }
    .vw-social-concept .vw-theme-input-row {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        align-items: stretch;
    }
    .vw-social-concept .vw-theme-input {
        flex: 1;
        background: rgba(30, 30, 50, 0.8);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        color: #e2e8f0;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s;
    }
    .vw-social-concept .vw-theme-input:focus {
        border-color: rgba(139, 92, 246, 0.6);
    }
    .vw-social-concept .vw-theme-input::placeholder {
        color: #64748b;
    }
    .vw-social-concept .vw-generate-viral-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
        color: white;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .vw-social-concept .vw-generate-viral-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
    }
    .vw-social-concept .vw-generate-viral-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .vw-social-concept .vw-ideas-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 768px) {
        .vw-social-concept .vw-ideas-grid {
            grid-template-columns: 1fr;
        }
    }
    .vw-social-concept .vw-idea-card {
        background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 40, 0.98) 100%);
        border: 2px solid rgba(100, 100, 140, 0.2);
        border-radius: 1rem;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .vw-social-concept .vw-idea-card:hover {
        border-color: rgba(139, 92, 246, 0.4);
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.15);
    }
    .vw-social-concept .vw-idea-card.selected {
        border-color: #8b5cf6;
        box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
    }
    .vw-social-concept .vw-idea-card.selected::after {
        content: '\2713';
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        width: 24px;
        height: 24px;
        background: #8b5cf6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .vw-social-concept .vw-idea-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #f1f5f9;
        margin-bottom: 0.5rem;
        padding-right: 2rem;
    }
    .vw-social-concept .vw-idea-desc {
        font-size: 0.85rem;
        color: #cbd5e1;
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }
    .vw-social-concept .vw-idea-character {
        font-size: 0.8rem;
        color: #a78bfa;
        margin-bottom: 0.5rem;
    }
    .vw-social-concept .vw-idea-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 0.75rem;
    }
    .vw-social-concept .vw-idea-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .vw-social-concept .vw-idea-badge.audio-music {
        background: rgba(168, 85, 247, 0.2);
        color: #c084fc;
        border: 1px solid rgba(168, 85, 247, 0.3);
    }
    .vw-social-concept .vw-idea-badge.audio-voice {
        background: rgba(34, 211, 238, 0.15);
        color: #67e8f9;
        border: 1px solid rgba(34, 211, 238, 0.3);
    }
    .vw-social-concept .vw-idea-badge.mood-funny { background: rgba(250, 204, 21, 0.15); color: #fde047; border: 1px solid rgba(250, 204, 21, 0.25); }
    .vw-social-concept .vw-idea-badge.mood-absurd { background: rgba(249, 115, 22, 0.15); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.25); }
    .vw-social-concept .vw-idea-badge.mood-wholesome { background: rgba(52, 211, 153, 0.15); color: #6ee7b7; border: 1px solid rgba(52, 211, 153, 0.25); }
    .vw-social-concept .vw-idea-badge.mood-chaotic { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.25); }
    .vw-social-concept .vw-idea-badge.mood-cute { background: rgba(236, 72, 153, 0.15); color: #f9a8d4; border: 1px solid rgba(236, 72, 153, 0.25); }
    .vw-social-concept .vw-idea-badge.source-cloned {
        background: rgba(20, 184, 166, 0.15);
        color: #5eead4;
        border: 1px solid rgba(20, 184, 166, 0.3);
    }
    .vw-social-concept .vw-idea-hook {
        font-size: 0.78rem;
        color: #94a3b8;
        font-style: italic;
        line-height: 1.3;
    }
    .vw-social-concept .vw-generate-more-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.25rem;
        background: transparent;
        color: #a78bfa;
        border: 1px solid rgba(139, 92, 246, 0.4);
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-concept .vw-generate-more-btn:hover:not(:disabled) {
        background: rgba(139, 92, 246, 0.1);
        border-color: rgba(139, 92, 246, 0.6);
    }
    .vw-social-concept .vw-generate-more-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .vw-social-concept .vw-skeleton-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 768px) {
        .vw-social-concept .vw-skeleton-grid { grid-template-columns: 1fr; }
    }
    .vw-social-concept .vw-skeleton-card {
        background: rgba(30, 30, 50, 0.6);
        border: 1px solid rgba(100, 100, 140, 0.15);
        border-radius: 1rem;
        padding: 1.25rem;
        animation: vw-skeleton-pulse 1.5s ease-in-out infinite;
    }
    .vw-social-concept .vw-skeleton-line {
        height: 0.75rem;
        background: rgba(100, 100, 140, 0.2);
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
    }
    .vw-social-concept .vw-skeleton-line.short { width: 60%; }
    .vw-social-concept .vw-skeleton-line.medium { width: 80%; }
    .vw-social-concept .vw-skeleton-line.title { height: 1rem; width: 70%; margin-bottom: 0.75rem; }
    @keyframes vw-skeleton-pulse {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 1; }
    }
    .vw-social-concept .vw-engine-selector { margin-bottom: 1.5rem; }
    .vw-social-concept .vw-engine-selector h3 {
        font-size: 0.85rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;
    }
    .vw-social-concept .vw-engine-cards {
        display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;
    }
    @media (max-width: 640px) { .vw-social-concept .vw-engine-cards { grid-template-columns: 1fr; } }
    .vw-social-concept .vw-engine-card {
        background: rgba(30, 30, 50, 0.8); border: 2px solid rgba(100, 100, 140, 0.2); border-radius: 0.75rem;
        padding: 1rem; cursor: pointer; transition: all 0.2s; position: relative;
    }
    .vw-social-concept .vw-engine-card:hover { border-color: rgba(139, 92, 246, 0.4); transform: translateY(-1px); }
    .vw-social-concept .vw-engine-card.active { border-color: #8b5cf6; box-shadow: 0 0 15px rgba(139, 92, 246, 0.2); }
    .vw-social-concept .vw-engine-card .vw-engine-icon { font-size: 1.5rem; margin-bottom: 0.5rem; }
    .vw-social-concept .vw-engine-card h4 { font-size: 1rem; font-weight: 700; color: #f1f5f9; margin-bottom: 0.35rem; }
    .vw-social-concept .vw-engine-card p { font-size: 0.78rem; color: #94a3b8; line-height: 1.4; margin-bottom: 0.5rem; }
    .vw-social-concept .vw-engine-card .vw-engine-badge {
        display: inline-block; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.65rem;
        font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;
        background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3);
    }

    /* Source Tabs */
    .vw-social-concept .vw-source-tabs { margin-bottom: 1.5rem; }
    .vw-social-concept .vw-tab-row {
        display: flex;
        gap: 0.25rem;
        margin-bottom: 1.25rem;
        background: rgba(20, 20, 40, 0.6);
        border-radius: 0.75rem;
        padding: 0.25rem;
    }
    .vw-social-concept .vw-tab-btn {
        flex: 1;
        padding: 0.65rem 1rem;
        background: transparent;
        color: #94a3b8;
        border: none;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .vw-social-concept .vw-tab-btn:hover { color: #e2e8f0; background: rgba(139, 92, 246, 0.1); }
    .vw-social-concept .vw-tab-btn.active {
        background: rgba(139, 92, 246, 0.2);
        color: #e2e8f0;
        border: 1px solid rgba(139, 92, 246, 0.3);
    }

    /* Clone Video UI */
    .vw-social-concept .vw-upload-dropzone {
        border: 2px dashed rgba(139, 92, 246, 0.3);
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        transition: all 0.2s;
        background: rgba(20, 20, 40, 0.5);
        cursor: pointer;
        position: relative;
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-upload-dropzone:hover,
    .vw-social-concept .vw-upload-dropzone.dragging {
        border-color: rgba(139, 92, 246, 0.6);
        background: rgba(139, 92, 246, 0.05);
    }
    .vw-social-concept .vw-dropzone-content { color: #94a3b8; }
    .vw-social-concept .vw-dropzone-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
    .vw-social-concept .vw-dropzone-content p { font-size: 0.95rem; color: #cbd5e1; margin-bottom: 0.25rem; }
    .vw-social-concept .vw-dropzone-content small { font-size: 0.78rem; color: #64748b; }
    .vw-social-concept .vw-video-preview {
        max-height: 280px;
        width: 100%;
        border-radius: 0.75rem;
        object-fit: contain;
        background: #000;
    }
    .vw-social-concept .vw-remove-video {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.75rem;
        padding: 0.4rem 0.8rem;
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 0.5rem;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .vw-social-concept .vw-remove-video:hover { background: rgba(239, 68, 68, 0.25); }
    .vw-social-concept .vw-analyze-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
        color: white;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
        justify-content: center;
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-analyze-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4); }
    .vw-social-concept .vw-analysis-progress {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 1.25rem;
        background: rgba(30, 30, 50, 0.8);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        color: #cbd5e1;
        font-size: 0.9rem;
    }
    .vw-social-concept .vw-progress-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid rgba(139, 92, 246, 0.3);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }
    @keyframes vw-spin { to { transform: rotate(360deg); } }
    .vw-social-concept .vw-analysis-error {
        padding: 0.75rem 1rem;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 0.75rem;
        color: #fca5a5;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-cloned-idea-card {
        background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 40, 0.98) 100%);
        border: 2px solid rgba(20, 184, 166, 0.3);
        border-radius: 1rem;
        padding: 1.25rem;
        position: relative;
        margin-bottom: 1rem;
    }
    .vw-social-concept .vw-cloned-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        background: rgba(20, 184, 166, 0.15);
        color: #5eead4;
        border: 1px solid rgba(20, 184, 166, 0.3);
        margin-bottom: 0.75rem;
    }
    .vw-social-concept .vw-cloned-prompt-preview {
        background: rgba(15, 15, 30, 0.6);
        border: 1px solid rgba(100, 100, 140, 0.15);
        border-radius: 0.5rem;
        padding: 0.75rem;
        margin: 0.75rem 0;
        font-size: 0.8rem;
        color: #94a3b8;
        line-height: 1.4;
    }
    .vw-social-concept .vw-cloned-prompt-preview strong { color: #cbd5e1; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; }
    .vw-social-concept .vw-cloned-prompt-preview p { margin-top: 0.35rem; }
    .vw-social-concept .vw-use-concept-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
        color: white;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
        justify-content: center;
        margin-top: 0.5rem;
    }
    .vw-social-concept .vw-use-concept-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(20, 184, 166, 0.4); }
</style>

<div class="vw-social-concept" x-data="{ viralTheme: '', activeTab: 'generate' }">
    <div class="vw-concept-card">
        {{-- Error Message --}}
        @if($error)
            <div class="vw-error-alert">
                <span style="margin-right: 0.5rem;">&#9888;&#65039;</span>
                {{ $error }}
            </div>
        @endif

        {{-- Header --}}
        <div class="vw-viral-header">
            <div class="vw-viral-icon">&#128293;</div>
            <div>
                <h2 class="vw-viral-title">{{ __('Create Viral Content') }}</h2>
                <p class="vw-viral-subtitle">{{ __('AI generates trending video ideas — pick one and bring it to life') }}</p>
            </div>
        </div>

        {{-- Video Engine Selector --}}
        <div class="vw-engine-selector">
            <h3>{{ __('Choose Your Video Style') }}</h3>
            <div class="vw-engine-cards">
                <div class="vw-engine-card {{ $videoEngine === 'seedance' ? 'active' : '' }}"
                     wire:click="setVideoEngine('seedance')">
                    <div class="vw-engine-icon">&#127916;</div>
                    <h4>{{ __('Cinematic Scene') }}</h4>
                    <p>{{ __('AI generates video + voice + sound effects from a single prompt. Perfect for visual gags, animals in situations, dramatic scenes.') }}</p>
                    <span class="vw-engine-badge">{{ __('Auto Audio') }}</span>
                </div>
                <div class="vw-engine-card {{ $videoEngine === 'infinitetalk' ? 'active' : '' }}"
                     wire:click="setVideoEngine('infinitetalk')">
                    <div class="vw-engine-icon">&#128483;&#65039;</div>
                    <h4>{{ __('Lip-Sync Talking') }}</h4>
                    <p>{{ __('Characters speak with precise lip-sync from custom voices. Perfect for dialogue, narration, character conversations.') }}</p>
                    <span class="vw-engine-badge">{{ __('Custom Voices') }}</span>
                </div>
            </div>
        </div>

        {{-- Source Tabs: AI Generate vs Clone Video --}}
        <div class="vw-source-tabs">
            <div class="vw-tab-row">
                <button class="vw-tab-btn" :class="{ 'active': activeTab === 'generate' }"
                        @click="activeTab = 'generate'">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    {{ __('AI Generate') }}
                </button>
                <button class="vw-tab-btn" :class="{ 'active': activeTab === 'clone' }"
                        @click="activeTab = 'clone'">
                    <i class="fa-solid fa-clone"></i>
                    {{ __('Clone Video') }}
                </button>
            </div>

            {{-- ========================== AI Generate Tab ========================== --}}
            <div x-show="activeTab === 'generate'" x-cloak>
                {{-- Theme Input + Generate Button --}}
                <div class="vw-theme-input-row">
                    <input type="text"
                           class="vw-theme-input"
                           x-model="viralTheme"
                           placeholder="{{ __('Describe a theme (e.g., cats, cooking, gym life) or leave blank for random ideas...') }}"
                           @keydown.enter="$wire.generateViralIdeas(viralTheme)" />
                    <button class="vw-generate-viral-btn"
                            wire:click="generateViralIdeas(viralTheme)"
                            x-on:click="$wire.generateViralIdeas(viralTheme)"
                            wire:loading.attr="disabled"
                            @if($isLoading) disabled @endif>
                        <span wire:loading.remove wire:target="generateViralIdeas">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            {{ __('Generate Viral Ideas') }}
                        </span>
                        <span wire:loading wire:target="generateViralIdeas">
                            <span class="vw-loading-inner"></span>
                            {{ __('Generating...') }}
                        </span>
                    </button>
                </div>

                {{-- Loading Skeleton --}}
                @if($isLoading && empty($conceptVariations))
                    <div class="vw-skeleton-grid">
                        @for($i = 0; $i < 6; $i++)
                            <div class="vw-skeleton-card">
                                <div class="vw-skeleton-line title"></div>
                                <div class="vw-skeleton-line medium"></div>
                                <div class="vw-skeleton-line"></div>
                                <div class="vw-skeleton-line short"></div>
                            </div>
                        @endfor
                    </div>
                @endif

                {{-- Idea Cards Grid --}}
                @if(!empty($conceptVariations))
                    <div class="vw-ideas-grid">
                        @foreach($conceptVariations as $index => $idea)
                            <div class="vw-idea-card {{ $selectedConceptIndex === $index ? 'selected' : '' }}"
                                 wire:click="selectViralIdea({{ $index }})">
                                <div class="vw-idea-title">
                                    {{ $idea['title'] ?? 'Untitled' }}
                                </div>
                                <div class="vw-idea-character">
                                    @if(($idea['speechType'] ?? '') === 'dialogue' && !empty($idea['characters']))
                                        @foreach($idea['characters'] as $ci => $char)
                                            {{ $char['name'] ?? '' }}{{ $ci < count($idea['characters']) - 1 ? ' vs ' : '' }}
                                        @endforeach
                                        &mdash; {{ $idea['situation'] ?? '' }}
                                    @else
                                        {{ $idea['character'] ?? '' }} &mdash; {{ $idea['situation'] ?? '' }}
                                    @endif
                                </div>
                                <div class="vw-idea-badges">
                                    @if(($idea['source'] ?? '') === 'cloned')
                                        <span class="vw-idea-badge source-cloned">
                                            <i class="fa-solid fa-clone"></i> Cloned
                                        </span>
                                    @endif
                                    @if(($idea['speechType'] ?? '') === 'dialogue')
                                        <span class="vw-idea-badge audio-voice">
                                            <i class="fa-solid fa-comments"></i> Dialogue
                                        </span>
                                    @elseif(($idea['audioType'] ?? '') === 'music-lipsync')
                                        <span class="vw-idea-badge audio-music">
                                            <i class="fa-solid fa-music"></i> Music Lip-Sync
                                        </span>
                                    @else
                                        <span class="vw-idea-badge audio-voice">
                                            <i class="fa-solid fa-microphone"></i> Monologue
                                        </span>
                                    @endif
                                    @php $mood = strtolower($idea['mood'] ?? 'funny'); @endphp
                                    <span class="vw-idea-badge mood-{{ $mood }}">
                                        {{ ucfirst($mood) }}
                                    </span>
                                </div>
                                @if(($idea['speechType'] ?? '') === 'dialogue' && !empty($idea['dialogueLines']))
                                    <div class="vw-idea-desc" style="font-size: 0.8rem;">
                                        @foreach(array_slice($idea['dialogueLines'], 0, 3) as $line)
                                            <div style="margin-bottom: 0.2rem;"><strong>{{ $line['speaker'] ?? '' }}:</strong> "{{ $line['text'] ?? '' }}"</div>
                                        @endforeach
                                        @if(count($idea['dialogueLines']) > 3)
                                            <div style="color: #64748b;">+ {{ count($idea['dialogueLines']) - 3 }} more...</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="vw-idea-desc">{{ $idea['audioDescription'] ?? '' }}</div>
                                @endif
                                <div class="vw-idea-hook">{{ $idea['viralHook'] ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Generate More Button --}}
                    <div style="display: flex; justify-content: center;">
                        <button class="vw-generate-more-btn"
                                wire:click="generateViralIdeas(viralTheme)"
                                x-on:click="$wire.generateViralIdeas(viralTheme)"
                                wire:loading.attr="disabled"
                                @if($isLoading) disabled @endif>
                            <span wire:loading.remove wire:target="generateViralIdeas">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                {{ __('Generate More Ideas') }}
                            </span>
                            <span wire:loading wire:target="generateViralIdeas">
                                <span class="vw-loading-inner"></span>
                                {{ __('Generating...') }}
                            </span>
                        </button>
                    </div>
                @endif
            </div>

            {{-- ========================== Clone Video Tab ========================== --}}
            <div x-show="activeTab === 'clone'" x-cloak>
                <div class="vw-clone-zone"
                     x-data="{ isDragging: false }">

                    {{-- Upload Area --}}
                    <div class="vw-upload-dropzone"
                         :class="{ 'dragging': isDragging }"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; $refs.videoInput.files = $event.dataTransfer.files; $refs.videoInput.dispatchEvent(new Event('change'))"
                         @click="$refs.videoInput.click()">

                        @if($conceptVideoUpload)
                            {{-- Video Preview --}}
                            <video class="vw-video-preview" controls onclick="event.stopPropagation()">
                                <source src="{{ $conceptVideoUpload->temporaryUrl() }}" type="{{ $conceptVideoUpload->getMimeType() }}">
                            </video>
                            <div>
                                <button class="vw-remove-video"
                                        wire:click="$set('conceptVideoUpload', null)"
                                        onclick="event.stopPropagation()">
                                    <i class="fa-solid fa-trash-can"></i> {{ __('Remove') }}
                                </button>
                            </div>
                        @else
                            <div class="vw-dropzone-content">
                                <div class="vw-dropzone-icon"><i class="fa-solid fa-video"></i></div>
                                <p>{{ __('Drop a short video here or click to upload') }}</p>
                                <small>{{ __('MP4, MOV, WebM up to 100MB') }}</small>
                            </div>
                        @endif

                        <input type="file"
                               x-ref="videoInput"
                               wire:model="conceptVideoUpload"
                               accept="video/mp4,video/quicktime,video/webm,video/x-msvideo"
                               style="display: none;" />
                    </div>

                    {{-- Upload Progress --}}
                    <div wire:loading wire:target="conceptVideoUpload" class="vw-analysis-progress">
                        <div class="vw-progress-spinner"></div>
                        <span>{{ __('Uploading video...') }}</span>
                    </div>

                    {{-- Analyze Button --}}
                    @if($conceptVideoUpload && !$videoAnalysisStage)
                        <button class="vw-analyze-btn" wire:click="analyzeUploadedVideo">
                            <i class="fa-solid fa-magnifying-glass-chart"></i>
                            {{ __('Analyze & Clone Concept') }}
                        </button>
                    @endif

                    {{-- Progress Indicator --}}
                    @if($videoAnalysisStage)
                        <div class="vw-analysis-progress">
                            <div class="vw-progress-spinner"></div>
                            <span>
                                @if($videoAnalysisStage === 'analyzing')
                                    {{ __('Analyzing video with AI vision...') }}
                                @elseif($videoAnalysisStage === 'transcribing')
                                    {{ __('Transcribing audio...') }}
                                @elseif($videoAnalysisStage === 'synthesizing')
                                    {{ __('Building concept...') }}
                                @else
                                    {{ __('Processing...') }}
                                @endif
                            </span>
                        </div>
                    @endif

                    {{-- Error --}}
                    @if($videoAnalysisError)
                        <div class="vw-analysis-error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            {{ $videoAnalysisError }}
                        </div>
                    @endif

                    {{-- Analysis Result Card --}}
                    @if($videoAnalysisResult)
                        <div class="vw-cloned-idea-card">
                            <div class="vw-cloned-badge">
                                <i class="fa-solid fa-clone"></i> {{ __('Cloned Concept') }}
                            </div>
                            <div class="vw-idea-title">{{ $videoAnalysisResult['title'] ?? 'Cloned Concept' }}</div>
                            <div class="vw-idea-character">
                                @if(($videoAnalysisResult['speechType'] ?? '') === 'dialogue' && !empty($videoAnalysisResult['characters']))
                                    @foreach($videoAnalysisResult['characters'] as $c)
                                        {{ $c['name'] ?? '' }}@if(!$loop->last) vs @endif
                                    @endforeach
                                    &mdash; {{ $videoAnalysisResult['situation'] ?? '' }}
                                @else
                                    {{ $videoAnalysisResult['character'] ?? '' }} &mdash; {{ $videoAnalysisResult['situation'] ?? '' }}
                                @endif
                            </div>
                            <div class="vw-idea-desc">{{ $videoAnalysisResult['concept'] ?? '' }}</div>
                            @if(!empty($videoAnalysisResult['videoPrompt']))
                                <div class="vw-cloned-prompt-preview">
                                    <strong>{{ __('Video Prompt') }}</strong>
                                    <p>{{ Str::limit($videoAnalysisResult['videoPrompt'], 200) }}</p>
                                </div>
                            @endif
                            <div class="vw-idea-hook">{{ $videoAnalysisResult['viralHook'] ?? '' }}</div>

                            <button class="vw-use-concept-btn" wire:click="useAnalyzedConcept">
                                <i class="fa-solid fa-check"></i>
                                {{ __('Use This Concept') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
