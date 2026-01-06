{{-- Step 5: Animation & Voiceover --}}
<style>
    .vw-animation-step {
        width: 100%;
    }

    .vw-animation-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-animation-header {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-animation-icon {
        width: 42px !important;
        height: 42px !important;
        min-width: 42px !important;
        background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%) !important;
        border-radius: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.25rem !important;
    }

    .vw-animation-title {
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-animation-subtitle {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-top: 0.15rem !important;
    }

    /* Progress Pills */
    .vw-progress-pills {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
        flex-wrap: wrap;
    }

    .vw-progress-pill {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.7rem;
        border-radius: 2rem;
        font-size: 0.7rem;
    }

    .vw-progress-pill.voiceover {
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.3);
        color: #a78bfa;
    }

    .vw-progress-pill.voiceover.complete {
        background: rgba(16, 185, 129, 0.15);
        border-color: rgba(16, 185, 129, 0.3);
        color: #10b981;
    }

    .vw-progress-pill.ready {
        background: rgba(16, 185, 129, 0.15);
        border: 1px solid rgba(16, 185, 129, 0.3);
        color: #10b981;
    }

    /* Voice Settings */
    .vw-voice-settings {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-voice-settings-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-voice-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }

    @media (max-width: 768px) {
        .vw-voice-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-voice-btn {
        padding: 0.6rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.03);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        font-size: 0.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        transition: all 0.2s;
        text-align: left;
    }

    .vw-voice-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-voice-btn.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.2);
        color: white;
    }

    .vw-voice-name {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-voice-desc {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-voice-btn.selected .vw-voice-desc {
        color: rgba(255, 255, 255, 0.6);
    }

    /* Speed Control */
    .vw-speed-control {
        margin-top: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .vw-speed-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        min-width: 100px;
    }

    .vw-speed-slider {
        flex: 1;
        -webkit-appearance: none;
        height: 6px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
        outline: none;
    }

    .vw-speed-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 16px;
        height: 16px;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border-radius: 50%;
        cursor: pointer;
    }

    .vw-speed-value {
        font-size: 0.8rem;
        font-weight: 600;
        color: #a78bfa;
        min-width: 40px;
        text-align: right;
    }

    /* Bulk Actions */
    .vw-bulk-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .vw-bulk-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.65rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .vw-bulk-btn.voice {
        background: linear-gradient(135deg, #8b5cf6, #a855f7);
        color: white;
    }

    .vw-bulk-btn.voice:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    .vw-bulk-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Scene List */
    .vw-scene-list {
        margin-top: 1rem;
    }

    .vw-scene-list-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .vw-scene-list-title {
        font-size: 0.8rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Scene Item */
    .vw-scene-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
        transition: all 0.2s;
    }

    .vw-scene-item:hover {
        border-color: rgba(139, 92, 246, 0.3);
    }

    .vw-scene-item.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.05);
    }

    .vw-scene-row {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .vw-scene-thumb {
        width: 120px;
        height: 68px;
        border-radius: 0.5rem;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.3);
        flex-shrink: 0;
        position: relative;
    }

    .vw-scene-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-scene-thumb-empty {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.3);
        font-size: 1.25rem;
    }

    .vw-scene-number-badge {
        position: absolute;
        top: 0.35rem;
        left: 0.35rem;
        width: 20px;
        height: 20px;
        background: rgba(0, 0, 0, 0.7);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 600;
        color: white;
    }

    .vw-scene-content {
        flex: 1;
        min-width: 0;
    }

    .vw-scene-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.35rem;
    }

    .vw-scene-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
    }

    .vw-scene-badges {
        display: flex;
        gap: 0.35rem;
    }

    .vw-badge {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        font-weight: 500;
    }

    .vw-badge.duration {
        background: rgba(6, 182, 212, 0.2);
        color: #67e8f9;
    }

    .vw-badge.voiceover-ready {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .vw-badge.voiceover-pending {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
    }

    .vw-scene-narration {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }

    /* Audio Player */
    .vw-audio-section {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-audio-player {
        flex: 1;
        height: 32px;
    }

    .vw-audio-player audio {
        width: 100%;
        height: 100%;
    }

    .vw-audio-actions {
        display: flex;
        gap: 0.35rem;
    }

    .vw-audio-btn {
        padding: 0.4rem 0.6rem;
        border-radius: 0.35rem;
        font-size: 0.7rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.2s;
    }

    .vw-audio-btn.generate {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        color: white;
    }

    .vw-audio-btn.generate:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
    }

    .vw-audio-btn.regenerate {
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.3);
        color: #c4b5fd;
    }

    .vw-audio-btn.regenerate:hover {
        background: rgba(139, 92, 246, 0.25);
    }

    /* Generating State */
    .vw-generating-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(139, 92, 246, 0.1);
        border-radius: 0.35rem;
    }

    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    .vw-generating-indicator svg {
        width: 16px;
        height: 16px;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-generating-text {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
    }

    /* Alert */
    .vw-alert {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-alert.warning {
        background: rgba(251, 191, 36, 0.15);
        border: 1px solid rgba(251, 191, 36, 0.3);
        color: #fbbf24;
    }

    .vw-alert-icon {
        font-size: 1.25rem;
    }

    .vw-alert-text {
        font-size: 0.9rem;
    }

    /* Preview Tip */
    .vw-preview-tip {
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        background: rgba(6, 182, 212, 0.1);
        border: 1px solid rgba(6, 182, 212, 0.2);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-preview-tip-icon {
        font-size: 1rem;
    }

    .vw-preview-tip-text {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-preview-tip kbd {
        background: rgba(255, 255, 255, 0.1);
        padding: 0.15rem 0.35rem;
        border-radius: 0.25rem;
        font-size: 0.7rem;
    }
</style>

<div class="vw-animation-step">
    @if(empty($script['scenes']))
        <div class="vw-alert warning">
            <span class="vw-alert-icon">⚠️</span>
            <span class="vw-alert-text">{{ __('Please generate a script first.') }}</span>
        </div>
    @else
        @php
            $voiceoversReady = count(array_filter($animation['scenes'] ?? [], fn($s) => !empty($s['voiceoverUrl'])));
            $totalScenes = count($script['scenes']);
            $allVoiceoversReady = $voiceoversReady >= $totalScenes;
            $selectedVoice = $animation['voiceover']['voice'] ?? 'nova';
            $speed = $animation['voiceover']['speed'] ?? 1.0;
        @endphp

        {{-- Main Card --}}
        <div class="vw-animation-card">
            {{-- Header --}}
            <div class="vw-animation-header">
                <div class="vw-animation-icon">🎬</div>
                <div style="flex: 1;">
                    <h2 class="vw-animation-title">{{ __('Animation Studio') }}</h2>
                    <p class="vw-animation-subtitle">
                        {{ __('Generate voiceovers for your scenes') }}
                    </p>
                </div>
            </div>

            {{-- Progress Pills --}}
            <div class="vw-progress-pills">
                <div class="vw-progress-pill voiceover {{ $allVoiceoversReady ? 'complete' : '' }}">
                    <span>🎙️</span>
                    <span style="font-weight: 600;">{{ $voiceoversReady }}/{{ $totalScenes }}</span>
                    <span>{{ __('voiceovers') }}</span>
                </div>
                @if($allVoiceoversReady)
                    <div class="vw-progress-pill ready">
                        <span>✓</span>
                        <span style="font-weight: 600;">{{ __('Ready for assembly') }}</span>
                    </div>
                @endif
            </div>

            {{-- Voice Settings --}}
            <div class="vw-voice-settings">
                <div class="vw-voice-settings-label">
                    <span>🎙️</span>
                    <span>{{ __('Select Voice') }}</span>
                </div>
                <div class="vw-voice-grid">
                    @php
                        $voices = [
                            'alloy' => ['icon' => '🎭', 'name' => 'Alloy', 'desc' => 'Neutral, versatile'],
                            'echo' => ['icon' => '🎤', 'name' => 'Echo', 'desc' => 'Male, warm'],
                            'fable' => ['icon' => '📖', 'name' => 'Fable', 'desc' => 'Storytelling'],
                            'onyx' => ['icon' => '🎸', 'name' => 'Onyx', 'desc' => 'Deep male'],
                            'nova' => ['icon' => '✨', 'name' => 'Nova', 'desc' => 'Female, expressive'],
                            'shimmer' => ['icon' => '💫', 'name' => 'Shimmer', 'desc' => 'Bright female'],
                        ];
                    @endphp
                    @foreach($voices as $voiceId => $voice)
                        <button type="button"
                                class="vw-voice-btn {{ $selectedVoice === $voiceId ? 'selected' : '' }}"
                                wire:click="$set('animation.voiceover.voice', '{{ $voiceId }}')">
                            <span class="vw-voice-name">{{ $voice['icon'] }} {{ $voice['name'] }}</span>
                            <span class="vw-voice-desc">{{ $voice['desc'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Speed Control --}}
            <div class="vw-speed-control">
                <span class="vw-speed-label">⚡ {{ __('Speed') }}:</span>
                <input type="range"
                       wire:model.live="animation.voiceover.speed"
                       min="0.5" max="2.0" step="0.1"
                       class="vw-speed-slider">
                <span class="vw-speed-value">{{ number_format($speed, 1) }}x</span>
            </div>

            {{-- Bulk Actions --}}
            <div class="vw-bulk-actions">
                <button class="vw-bulk-btn voice"
                        wire:click="$dispatch('generate-all-voiceovers')"
                        wire:loading.attr="disabled"
                        wire:target="generateAllVoiceovers">
                    <span wire:loading.remove wire:target="generateAllVoiceovers">
                        🎙️ {{ __('Generate All Voiceovers') }}
                    </span>
                    <span wire:loading wire:target="generateAllVoiceovers">
                        <svg style="width: 16px; height: 16px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                        </svg>
                        {{ __('Generating...') }}
                    </span>
                </button>
            </div>
        </div>

        {{-- Scene List --}}
        <div class="vw-scene-list">
            <div class="vw-scene-list-header">
                <span class="vw-scene-list-title">{{ __('Scenes') }} ({{ $totalScenes }})</span>
            </div>

            @foreach($script['scenes'] as $index => $scene)
                @php
                    $animationScene = $animation['scenes'][$index] ?? null;
                    $voiceoverUrl = $animationScene['voiceoverUrl'] ?? null;
                    $storyboardScene = $storyboard['scenes'][$index] ?? null;
                    $imageUrl = $storyboardScene['imageUrl'] ?? null;
                    $voiceoverStatus = $animationScene['status'] ?? 'pending';
                @endphp
                <div class="vw-scene-item">
                    <div class="vw-scene-row">
                        {{-- Thumbnail --}}
                        <div class="vw-scene-thumb">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $scene['title'] ?? 'Scene ' . ($index + 1) }}">
                            @else
                                <div class="vw-scene-thumb-empty">🎬</div>
                            @endif
                            <div class="vw-scene-number-badge">{{ $index + 1 }}</div>
                        </div>

                        {{-- Content --}}
                        <div class="vw-scene-content">
                            <div class="vw-scene-title-row">
                                <span class="vw-scene-title">{{ $scene['title'] ?? __('Scene') . ' ' . ($index + 1) }}</span>
                                <div class="vw-scene-badges">
                                    <span class="vw-badge duration">{{ $scene['duration'] ?? 8 }}s</span>
                                    @if($voiceoverUrl)
                                        <span class="vw-badge voiceover-ready">✓ {{ __('Voice') }}</span>
                                    @else
                                        <span class="vw-badge voiceover-pending">{{ __('Pending') }}</span>
                                    @endif
                                </div>
                            </div>

                            <p class="vw-scene-narration">"{{ Str::limit($scene['narration'] ?? '', 120) }}"</p>

                            {{-- Audio Section --}}
                            <div class="vw-audio-section">
                                @if($voiceoverStatus === 'generating' || ($isLoading && !$voiceoverUrl))
                                    <div class="vw-generating-indicator">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                        </svg>
                                        <span class="vw-generating-text">{{ __('Generating voiceover...') }}</span>
                                    </div>
                                @elseif($voiceoverUrl)
                                    <div class="vw-audio-player">
                                        <audio controls style="width: 100%; height: 32px;">
                                            <source src="{{ $voiceoverUrl }}" type="audio/mpeg">
                                        </audio>
                                    </div>
                                    <div class="vw-audio-actions">
                                        <button type="button"
                                                class="vw-audio-btn regenerate"
                                                wire:click="$dispatch('regenerate-voiceover', { sceneIndex: {{ $index }} })"
                                                wire:loading.attr="disabled">
                                            🔄
                                        </button>
                                    </div>
                                @else
                                    <button type="button"
                                            class="vw-audio-btn generate"
                                            wire:click="$dispatch('generate-voiceover', { sceneIndex: {{ $index }}, sceneId: '{{ $scene['id'] }}' })"
                                            wire:loading.attr="disabled">
                                        🎙️ {{ __('Generate Voiceover') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Preview Tip --}}
        <div class="vw-preview-tip">
            <span class="vw-preview-tip-icon">💡</span>
            <span class="vw-preview-tip-text">
                {{ __('Tip: Generate all voiceovers at once for faster processing. You can preview each scene after generation.') }}
            </span>
        </div>
    @endif
</div>
