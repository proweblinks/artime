{{-- Social Content: Viral Idea Generator --}}
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
</style>

<div class="vw-social-concept" x-data="{ viralTheme: '' }">
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
                        <div class="vw-idea-title">{{ $idea['title'] ?? 'Untitled' }}</div>
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
</div>
