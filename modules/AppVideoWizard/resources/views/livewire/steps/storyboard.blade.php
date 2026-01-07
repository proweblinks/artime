{{-- Step 4: Storyboard - Matching Reference Design --}}
<style>
    .vw-storyboard-step {
        width: 100%;
    }

    .vw-storyboard-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-storyboard-header {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        margin-bottom: 1.25rem !important;
    }

    .vw-storyboard-icon {
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        background: linear-gradient(135deg, #ec4899 0%, #f97316 100%) !important;
        border-radius: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.5rem !important;
    }

    .vw-storyboard-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-storyboard-subtitle {
        font-size: 0.85rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-top: 0.25rem !important;
    }

    /* Section dividers */
    .vw-section {
        padding: 1rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-section:first-of-type {
        border-top: none;
        padding-top: 0;
    }

    .vw-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .vw-section-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-badge {
        font-size: 0.55rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vw-badge-pro {
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        color: white;
    }

    .vw-badge-new {
        background: linear-gradient(135deg, #10b981, #06b6d4);
        color: white;
    }

    /* AI Model Selector */
    .vw-model-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .vw-model-btn {
        padding: 0.6rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        font-size: 0.8rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
        transition: all 0.2s;
        min-width: 110px;
    }

    .vw-model-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-model-btn.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.25);
        color: white;
    }

    .vw-model-btn-name {
        font-weight: 600;
    }

    .vw-model-btn-cost {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-model-btn.selected .vw-model-btn-cost {
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-model-description {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
        margin-top: 0.5rem;
        font-style: italic;
    }

    /* Visual Style Grid */
    .vw-style-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-style-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-style-select-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .vw-style-select-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-style-select {
        width: 100%;
        padding: 0.6rem 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 0.5rem;
        color: white;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .vw-style-select:focus {
        border-color: rgba(139, 92, 246, 0.5);
        outline: none;
    }

    .vw-style-hint {
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.7rem;
        margin-top: 0.75rem;
    }

    /* Scene Memory Cards */
    .vw-memory-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-memory-grid {
            grid-template-columns: 1fr;
        }
    }

    .vw-memory-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-memory-icon {
        font-size: 1.5rem;
        width: 36px;
        text-align: center;
    }

    .vw-memory-content {
        flex: 1;
        min-width: 0;
    }

    .vw-memory-title {
        font-weight: 600;
        color: white;
        font-size: 0.85rem;
    }

    .vw-memory-desc {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.7rem;
        margin-top: 0.15rem;
    }

    .vw-memory-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-edit-btn {
        padding: 0.35rem 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.35rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-edit-btn:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .vw-memory-checkbox {
        width: 18px;
        height: 18px;
        accent-color: #8b5cf6;
        cursor: pointer;
    }

    /* Technical Specs */
    .vw-specs-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .vw-specs-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
    }

    .vw-specs-value {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: white;
        font-size: 0.8rem;
    }

    .vw-quality-badge {
        padding: 0.25rem 0.5rem;
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
        border-radius: 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
    }

    /* Prompt Chain */
    .vw-chain-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-chain-info {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .vw-chain-title {
        font-weight: 600;
        color: white;
        font-size: 0.9rem;
    }

    .vw-chain-desc {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
    }

    .vw-chain-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-process-btn {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #f59e0b, #f97316);
        border: none;
        border-radius: 0.5rem;
        color: white;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-process-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
    }

    .vw-process-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Progress Stats - Now inside the card */
    .vw-progress-bar {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: 0.75rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }

    .vw-progress-stat {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-progress-stat-icon {
        font-size: 0.9rem;
    }

    .vw-progress-stat-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #10b981;
    }

    .vw-progress-stat-label {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-bulk-actions {
        display: flex;
        gap: 0.5rem;
        margin-left: auto;
    }

    .vw-generate-all-btn {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.6rem 1.25rem;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        border-radius: 0.5rem;
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-generate-all-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    .vw-generate-all-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Storyboard Grid */
    .vw-storyboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }

    /* Scene Card - Dark theme matching main card */
    .vw-scene-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        overflow: hidden;
        transition: all 0.2s;
    }

    .vw-scene-card:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.05);
    }

    /* Scene Image Container */
    .vw-scene-image-container {
        position: relative;
        aspect-ratio: 16/9;
        background: rgba(0, 0, 0, 0.4);
        overflow: hidden;
    }

    .vw-scene-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* Empty State - Full height within image container */
    .vw-scene-empty {
        height: 100%;
        min-height: 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.03);
        border: 2px dashed rgba(255, 255, 255, 0.15);
        border-radius: 0;
        margin: 0;
    }

    .vw-scene-empty-text {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .vw-scene-empty-buttons {
        display: flex;
        gap: 0.5rem;
        width: 100%;
    }

    .vw-scene-empty-btn {
        flex: 1;
        padding: 0.6rem 0.5rem;
        border-radius: 0.5rem;
        border: 1px solid;
        color: white;
        cursor: pointer;
        font-size: 0.7rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.2s;
    }

    .vw-scene-empty-btn.ai {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.3));
        border-color: rgba(139, 92, 246, 0.4);
    }

    .vw-scene-empty-btn.ai:hover {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.4), rgba(6, 182, 212, 0.4));
        transform: translateY(-1px);
    }

    .vw-scene-empty-btn.stock {
        background: rgba(16, 185, 129, 0.2);
        border-color: rgba(16, 185, 129, 0.4);
    }

    .vw-scene-empty-btn.stock:hover {
        background: rgba(16, 185, 129, 0.3);
        transform: translateY(-1px);
    }

    .vw-scene-empty-btn-icon {
        font-size: 1.1rem;
    }

    .vw-scene-empty-btn-cost {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-scene-empty-btn.stock .vw-scene-empty-btn-cost {
        color: rgba(16, 185, 129, 0.8);
    }

    /* Generating State */
    .vw-scene-generating {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(139, 92, 246, 0.08);
        gap: 0.75rem;
    }

    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    .vw-spinner {
        width: 2rem;
        height: 2rem;
        border: 3px solid rgba(139, 92, 246, 0.3);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-generating-text {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.8rem;
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
        background: rgba(251, 191, 36, 0.12);
        border: 1px solid rgba(251, 191, 36, 0.25);
        color: #fbbf24;
    }

    .vw-alert.error {
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.25);
        color: #ef4444;
    }

    .vw-alert-icon {
        font-size: 1.25rem;
    }

    .vw-alert-text {
        font-size: 0.9rem;
    }

    .vw-alert-close {
        margin-left: auto;
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        font-size: 1.25rem;
        opacity: 0.7;
    }

    .vw-alert-close:hover {
        opacity: 1;
    }
</style>

<div class="vw-storyboard-step">
    {{-- Error Alert --}}
    @if($error)
        <div class="vw-alert error">
            <span class="vw-alert-icon">❌</span>
            <span class="vw-alert-text">{{ $error }}</span>
            <button type="button" class="vw-alert-close" wire:click="$set('error', null)">&times;</button>
        </div>
    @endif

    @if(empty($script['scenes']))
        <div class="vw-alert warning">
            <span class="vw-alert-icon">⚠️</span>
            <span class="vw-alert-text">{{ __('Please generate a script first before creating the storyboard.') }}</span>
        </div>
    @else
        {{-- Main Card --}}
        <div class="vw-storyboard-card">
            {{-- Header --}}
            <div class="vw-storyboard-header">
                <div class="vw-storyboard-icon">🎨</div>
                <div style="flex: 1;">
                    <h2 class="vw-storyboard-title">{{ __('Storyboard') }}</h2>
                    <p class="vw-storyboard-subtitle">
                        {{ __('Visual preview of each scene') }} •
                        {{ count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl']))) }}/{{ count($script['scenes']) }}
                        {{ __('images ready') }}
                    </p>
                </div>
            </div>

            {{-- AI Model Selector --}}
            <div class="vw-section">
                <div class="vw-section-label">
                    <span>🤖</span>
                    <span>{{ __('AI Model for Image Generation:') }}</span>
                </div>
                <div class="vw-model-buttons" style="margin-top: 0.75rem;">
                    @php
                        $imageModels = [
                            'hidream' => ['name' => 'HiDream', 'cost' => 2, 'desc' => 'Artistic & cinematic style'],
                            'nanobanana-pro' => ['name' => 'NanoBanana Pro', 'cost' => 3, 'desc' => 'High quality, fast generation'],
                            'nanobanana' => ['name' => 'NanoBanana', 'cost' => 1, 'desc' => 'Quick drafts, lower cost'],
                        ];
                        $selectedModel = $storyboard['imageModel'] ?? 'nanobanana';
                    @endphp
                    @foreach($imageModels as $modelId => $model)
                        <button type="button"
                                class="vw-model-btn {{ $selectedModel === $modelId ? 'selected' : '' }}"
                                wire:click="$set('storyboard.imageModel', '{{ $modelId }}')"
                                title="{{ $model['desc'] }}">
                            <span class="vw-model-btn-name">{{ $model['name'] }}</span>
                            <span class="vw-model-btn-cost">{{ $model['cost'] }} {{ $model['cost'] === 1 ? __('token') : __('tokens') }}</span>
                        </button>
                    @endforeach
                </div>
                <p class="vw-model-description">{{ $imageModels[$selectedModel]['desc'] ?? '' }}</p>
            </div>

            {{-- Visual Style Controls --}}
            <div class="vw-section">
                <div class="vw-section-header">
                    <div class="vw-section-label">
                        <span>🎬</span>
                        <span>{{ __('Visual Style') }}</span>
                    </div>
                    <span class="vw-badge vw-badge-pro">PRO</span>
                </div>
                <div class="vw-style-grid">
                    {{-- Mood --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Mood') }}</span>
                        <select class="vw-style-select" wire:model.live="storyboard.visualStyle.mood">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="epic">{{ __('Epic') }}</option>
                            <option value="intimate">{{ __('Intimate') }}</option>
                            <option value="mysterious">{{ __('Mysterious') }}</option>
                            <option value="energetic">{{ __('Energetic') }}</option>
                            <option value="contemplative">{{ __('Contemplative') }}</option>
                            <option value="tense">{{ __('Tense') }}</option>
                            <option value="hopeful">{{ __('Hopeful') }}</option>
                            <option value="professional">{{ __('Professional') }}</option>
                        </select>
                    </div>
                    {{-- Lighting --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Lighting') }}</span>
                        <select class="vw-style-select" wire:model.live="storyboard.visualStyle.lighting">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="natural">{{ __('Natural') }}</option>
                            <option value="golden-hour">{{ __('Golden Hour') }}</option>
                            <option value="blue-hour">{{ __('Blue Hour') }}</option>
                            <option value="high-key">{{ __('High Key') }}</option>
                            <option value="low-key">{{ __('Low Key/Noir') }}</option>
                            <option value="neon">{{ __('Neon') }}</option>
                        </select>
                    </div>
                    {{-- Colors --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Colors') }}</span>
                        <select class="vw-style-select" wire:model.live="storyboard.visualStyle.colorPalette">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="teal-orange">{{ __('Teal/Orange') }}</option>
                            <option value="warm-tones">{{ __('Warm Tones') }}</option>
                            <option value="cool-tones">{{ __('Cool Tones') }}</option>
                            <option value="desaturated">{{ __('Desaturated') }}</option>
                            <option value="vibrant">{{ __('Vibrant') }}</option>
                            <option value="pastel">{{ __('Pastel') }}</option>
                        </select>
                    </div>
                    {{-- Shot --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Shot') }}</span>
                        <select class="vw-style-select" wire:model.live="storyboard.visualStyle.composition">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="wide">{{ __('Wide') }}</option>
                            <option value="medium">{{ __('Medium') }}</option>
                            <option value="close-up">{{ __('Close-up') }}</option>
                            <option value="extreme-close-up">{{ __('Extreme CU') }}</option>
                            <option value="low-angle">{{ __('Low Angle') }}</option>
                            <option value="birds-eye">{{ __("Bird's Eye") }}</option>
                        </select>
                    </div>
                </div>
                <p class="vw-style-hint">
                    💡 {{ __('"Auto" uses genre-appropriate defaults based on your content settings') }}
                </p>
            </div>

            {{-- Scene Memory Section --}}
            <div class="vw-section">
                <div class="vw-section-header">
                    <div class="vw-section-label">
                        <span>🧠</span>
                        <span>{{ __('Scene Memory') }}</span>
                    </div>
                    <span class="vw-badge vw-badge-new">NEW</span>
                </div>
                <p class="vw-style-hint" style="margin-top: 0; margin-bottom: 0.75rem;">
                    {{ __('Visual consistency with Style, Character & Location Bibles') }}
                </p>
                <div class="vw-memory-grid">
                    {{-- Style Bible --}}
                    <div class="vw-memory-card">
                        <div class="vw-memory-icon">🎨</div>
                        <div class="vw-memory-content">
                            <div class="vw-memory-title">{{ __('Style Bible') }}</div>
                            <div class="vw-memory-desc">{{ __('Visual DNA') }}</div>
                        </div>
                        <div class="vw-memory-actions">
                            <button type="button" class="vw-edit-btn" wire:click="$dispatch('open-style-bible-modal')">
                                {{ __('Edit') }}
                            </button>
                            <input type="checkbox"
                                   class="vw-memory-checkbox"
                                   wire:model.live="sceneMemory.styleBible.enabled"
                                   title="{{ __('Enable Style Bible') }}">
                        </div>
                    </div>

                    {{-- Character Bible --}}
                    <div class="vw-memory-card">
                        <div class="vw-memory-icon">👤</div>
                        <div class="vw-memory-content">
                            <div class="vw-memory-title">{{ __('Character Bible') }}</div>
                            <div class="vw-memory-desc">{{ __('Consistent faces') }}</div>
                        </div>
                        <div class="vw-memory-actions">
                            <button type="button" class="vw-edit-btn" wire:click="openCharacterBibleModal">
                                {{ __('Edit') }}
                            </button>
                            <input type="checkbox"
                                   class="vw-memory-checkbox"
                                   wire:model.live="sceneMemory.characterBible.enabled"
                                   title="{{ __('Enable Character Bible') }}">
                        </div>
                    </div>

                    {{-- Location Bible --}}
                    <div class="vw-memory-card">
                        <div class="vw-memory-icon">📍</div>
                        <div class="vw-memory-content">
                            <div class="vw-memory-title">{{ __('Location Bible') }}</div>
                            <div class="vw-memory-desc">{{ __('Consistent environments') }}</div>
                        </div>
                        <div class="vw-memory-actions">
                            <button type="button" class="vw-edit-btn" wire:click="openLocationBibleModal">
                                {{ __('Edit') }}
                            </button>
                            <input type="checkbox"
                                   class="vw-memory-checkbox"
                                   wire:model.live="sceneMemory.locationBible.enabled"
                                   title="{{ __('Enable Location Bible') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Technical Specs Section (Expandable) --}}
            <div class="vw-section" x-data="{ specsOpen: false }">
                <div class="vw-section-header" style="cursor: pointer;" @click="specsOpen = !specsOpen">
                    <div class="vw-section-label">
                        <span style="transition: transform 0.2s;" :style="specsOpen ? '' : 'transform: rotate(-90deg)'">▼</span>
                        <span>⚙️</span>
                        <span>{{ __('Technical Specs') }}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span class="vw-quality-badge">{{ $storyboard['technicalSpecs']['quality'] ?? '4K' }} {{ __('quality') }}</span>
                        <input type="checkbox"
                               class="vw-memory-checkbox"
                               wire:model.live="storyboard.technicalSpecs.enabled"
                               title="{{ __('Enable Technical Specs') }}"
                               @click.stop>
                    </div>
                </div>

                {{-- Expandable Content --}}
                <div x-show="specsOpen" x-collapse style="margin-top: 0.75rem;">
                    {{-- Output Quality --}}
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-bottom: 0.35rem;">{{ __('Output Quality') }}</label>
                        <select class="vw-style-select" wire:model.live="storyboard.technicalSpecs.quality" style="max-width: 200px;">
                            <option value="4k">{{ __('4K (3840×2160)') }}</option>
                            <option value="2k">{{ __('2K (2560×1440)') }}</option>
                            <option value="1080p">{{ __('1080p (1920×1080)') }}</option>
                            <option value="720p">{{ __('720p (1280×720)') }}</option>
                        </select>
                    </div>

                    {{-- Positive Prompts --}}
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-bottom: 0.35rem;">
                            ✅ {{ __('Positive Prompts') }}
                            <span style="color: rgba(255,255,255,0.3); font-weight: normal;">({{ __('enhance quality') }})</span>
                        </label>
                        <textarea wire:model.live.debounce.500ms="storyboard.technicalSpecs.positive"
                                  placeholder="{{ __('high quality, detailed, professional, 8K resolution, sharp focus, cinematic...') }}"
                                  style="width: 100%; padding: 0.6rem 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 0.5rem; color: white; font-size: 0.8rem; min-height: 60px; resize: vertical;"></textarea>
                    </div>

                    {{-- Negative Prompts --}}
                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: block; font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-bottom: 0.35rem;">
                            ❌ {{ __('Negative Prompts') }}
                            <span style="color: rgba(255,255,255,0.3); font-weight: normal;">({{ __('avoid these') }})</span>
                        </label>
                        <textarea wire:model.live.debounce.500ms="storyboard.technicalSpecs.negative"
                                  placeholder="{{ __('blurry, low quality, ugly, distorted, watermark, text, logo, nsfw...') }}"
                                  style="width: 100%; padding: 0.6rem 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.5rem; color: white; font-size: 0.8rem; min-height: 60px; resize: vertical;"></textarea>
                    </div>

                    {{-- Quick Presets --}}
                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.5rem;">
                        <span style="color: rgba(255,255,255,0.4); font-size: 0.65rem; margin-right: 0.25rem;">{{ __('Presets:') }}</span>
                        <button type="button" wire:click="applyTechnicalSpecsPreset('cinematic')" style="padding: 0.2rem 0.5rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.25rem; color: #c4b5fd; font-size: 0.6rem; cursor: pointer;">🎬 {{ __('Cinematic') }}</button>
                        <button type="button" wire:click="applyTechnicalSpecsPreset('photorealistic')" style="padding: 0.2rem 0.5rem; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 0.25rem; color: #67e8f9; font-size: 0.6rem; cursor: pointer;">📷 {{ __('Photorealistic') }}</button>
                        <button type="button" wire:click="applyTechnicalSpecsPreset('artistic')" style="padding: 0.2rem 0.5rem; background: rgba(236,72,153,0.15); border: 1px solid rgba(236,72,153,0.3); border-radius: 0.25rem; color: #f472b6; font-size: 0.6rem; cursor: pointer;">🎨 {{ __('Artistic') }}</button>
                        <button type="button" wire:click="applyTechnicalSpecsPreset('documentary')" style="padding: 0.2rem 0.5rem; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.25rem; color: #6ee7b7; font-size: 0.6rem; cursor: pointer;">📹 {{ __('Documentary') }}</button>
                    </div>
                </div>
            </div>

            {{-- Prompt Chain Section --}}
            <div class="vw-section">
                <div class="vw-chain-row">
                    <div class="vw-chain-info">
                        <div class="vw-section-label" style="margin-bottom: 0.25rem;">
                            <span>⚡</span>
                            <span>{{ __('Prompt Chain') }}</span>
                        </div>
                        <div class="vw-chain-desc">{{ __('Hollywood-grade scene blueprints') }}</div>
                    </div>
                    <div class="vw-chain-actions">
                        <button type="button"
                                class="vw-process-btn"
                                wire:click="processPromptChain"
                                wire:loading.attr="disabled"
                                wire:target="processPromptChain">
                            <span wire:loading.remove wire:target="processPromptChain">
                                ⚡ {{ __('Process Chain') }}
                            </span>
                            <span wire:loading wire:target="processPromptChain">
                                <svg style="width: 14px; height: 14px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                </svg>
                                {{ __('Processing...') }}
                            </span>
                        </button>
                        <input type="checkbox"
                               class="vw-memory-checkbox"
                               wire:model.live="storyboard.promptChain.enabled"
                               title="{{ __('Enable Prompt Chain') }}"
                               checked>
                    </div>
                </div>
            </div>

            {{-- Progress Stats & Bulk Actions - INSIDE the card --}}
            <div class="vw-progress-bar">
                <div class="vw-progress-stat">
                    <span class="vw-progress-stat-icon">🖼️</span>
                    <span class="vw-progress-stat-value">{{ count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl']))) }}</span>
                </div>
                <div class="vw-progress-stat">
                    <span class="vw-progress-stat-icon">🎬</span>
                    <span class="vw-progress-stat-value">{{ count($script['scenes']) }}</span>
                    <span class="vw-progress-stat-label">{{ __('scenes') }}</span>
                </div>
                <div class="vw-bulk-actions">
                    <button class="vw-generate-all-btn"
                            wire:click="generateAllImages"
                            wire:loading.attr="disabled"
                            wire:target="generateAllImages">
                        <span wire:loading.remove wire:target="generateAllImages">
                            🎨 {{ __('Generate All Images') }}
                        </span>
                        <span wire:loading wire:target="generateAllImages">
                            <svg style="width: 16px; height: 16px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                            </svg>
                            {{ __('Generating...') }}
                        </span>
                    </button>
                </div>
            </div>

            {{-- Storyboard Grid --}}
            <div class="vw-storyboard-grid">
            @foreach($script['scenes'] as $index => $scene)
                @php
                    $storyboardScene = $storyboard['scenes'][$index] ?? null;
                    $imageUrl = $storyboardScene['imageUrl'] ?? null;
                    $status = $storyboardScene['status'] ?? 'pending';
                    $source = $storyboardScene['source'] ?? 'ai';
                    $prompt = $storyboardScene['prompt'] ?? $scene['visualDescription'] ?? $scene['visual'] ?? $scene['narration'] ?? '';
                    $hasMultiShot = isset($multiShotMode['decomposedScenes'][$index]);
                    $decomposed = $hasMultiShot ? $multiShotMode['decomposedScenes'][$index] : null;
                @endphp
                <div class="vw-scene-card">
                    {{-- Image Container with Overlays --}}
                    <div style="position: relative;">
                        {{-- Scene Number Badge - Always visible, top-left --}}
                        <div style="position: absolute; top: 0.5rem; left: 0.5rem; background: rgba(0,0,0,0.75); color: white; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 600; z-index: 10;">
                            {{ __('Scene') }} {{ $index + 1 }}
                        </div>

                        {{-- Multi-Shot Badge - Top right if decomposed --}}
                        @if($hasMultiShot && !empty($decomposed['shots']))
                            <div style="position: absolute; top: 0.5rem; right: 0.5rem; z-index: 10;">
                                <span style="background: linear-gradient(135deg, #8b5cf6, #06b6d4); color: white; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.6rem; font-weight: 600;">
                                    📽️ {{ count($decomposed['shots']) }} {{ __('shots') }}
                                </span>
                            </div>
                        @endif

                        {{-- Main Image Content Area --}}
                        <div class="vw-scene-image-container">
                            @if($status === 'generating')
                                {{-- Generating State --}}
                                <div class="vw-scene-generating">
                                    <div class="vw-spinner"></div>
                                    <span class="vw-generating-text">{{ __('Generating...') }}</span>
                                    <button type="button"
                                            wire:click="cancelImageGeneration({{ $index }})"
                                            wire:confirm="{{ __('Cancel this generation? You can retry afterwards.') }}"
                                            style="margin-top: 0.5rem; padding: 0.25rem 0.75rem; border-radius: 0.25rem; border: 1px solid rgba(239,68,68,0.5); background: rgba(239,68,68,0.2); color: #f87171; cursor: pointer; font-size: 0.7rem; transition: all 0.2s;"
                                            onmouseover="this.style.background='rgba(239,68,68,0.4)'"
                                            onmouseout="this.style.background='rgba(239,68,68,0.2)'"
                                            title="{{ __('Cancel and retry') }}">
                                        ✕ {{ __('Cancel') }}
                                    </button>
                                </div>
                            @elseif($imageUrl)
                                {{-- Image Ready --}}
                                <img src="{{ $imageUrl }}" alt="Scene {{ $index + 1 }}" class="vw-scene-image">

                                {{-- Source Badge - Below scene number --}}
                                <div style="position: absolute; top: 2rem; left: 0.5rem; background: {{ $source === 'stock' ? 'rgba(16,185,129,0.9)' : 'rgba(139,92,246,0.9)' }}; color: white; padding: 0.15rem 0.4rem; border-radius: 0.2rem; font-size: 0.55rem; z-index: 10;">
                                    @if($source === 'stock')
                                        📷 {{ __('Stock') }}
                                    @else
                                        🎨 {{ __('AI') }}
                                    @endif
                                </div>

                                {{-- Action Buttons Overlay - Bottom of image --}}
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.85)); padding: 0.5rem; display: flex; gap: 0.3rem; z-index: 10;">
                                    <button type="button"
                                            wire:click="openAIEditModal({{ $index }})"
                                            style="flex: 1; padding: 0.35rem; border-radius: 0.4rem; border: 1px solid rgba(236,72,153,0.5); background: linear-gradient(135deg, rgba(236,72,153,0.3), rgba(139,92,246,0.3)); color: white; cursor: pointer; font-size: 0.65rem;"
                                            title="{{ __('Edit with AI') }}">
                                        ✨ {{ __('Edit') }}
                                    </button>
                                    <button type="button"
                                            wire:click="openEditPromptModal({{ $index }})"
                                            style="padding: 0.35rem 0.5rem; border-radius: 0.4rem; border: 1px solid rgba(255,255,255,0.3); background: rgba(0,0,0,0.5); color: white; cursor: pointer; font-size: 0.65rem;"
                                            title="{{ __('Modify prompt') }}">
                                        ✏️
                                    </button>
                                    <button type="button"
                                            wire:click="openStockBrowser({{ $index }})"
                                            style="padding: 0.35rem 0.5rem; border-radius: 0.4rem; border: 1px solid rgba(16,185,129,0.5); background: rgba(16,185,129,0.2); color: white; cursor: pointer; font-size: 0.65rem;"
                                            title="{{ __('Browse stock media') }}">
                                        📷
                                    </button>
                                    <button type="button"
                                            wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                            wire:loading.attr="disabled"
                                            style="padding: 0.35rem 0.5rem; border-radius: 0.4rem; border: 1px solid rgba(255,255,255,0.3); background: rgba(0,0,0,0.5); color: white; cursor: pointer; font-size: 0.65rem;"
                                            title="{{ __('Regenerate with AI') }}">
                                        🔄
                                    </button>
                                    <button type="button"
                                            wire:click="openUpscaleModal({{ $index }})"
                                            style="padding: 0.35rem 0.5rem; border-radius: 0.4rem; border: 1px solid rgba(251,191,36,0.5); background: rgba(251,191,36,0.2); color: white; cursor: pointer; font-size: 0.65rem;"
                                            title="{{ __('Upscale to HD/4K') }}">
                                        ⬆️
                                    </button>
                                    <button type="button"
                                            wire:click="openMultiShotModal({{ $index }})"
                                            style="padding: 0.35rem 0.5rem; border-radius: 0.4rem; border: 1px solid rgba(139,92,246,0.6); background: linear-gradient(135deg, rgba(139,92,246,0.4), rgba(6,182,212,0.3)); color: white; cursor: pointer; font-size: 0.65rem; font-weight: 600;"
                                            title="{{ __('Multi-shot decomposition') }}">
                                        ✂️
                                    </button>
                                </div>
                            @elseif($status === 'error')
                                {{-- Error State --}}
                                <div style="height: 160px; background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem;">
                                        <span style="font-size: 1rem;">⚠️</span>
                                        <span style="color: #ef4444; font-size: 0.75rem;">{{ Str::limit($storyboardScene['error'] ?? __('Generation failed'), 40) }}</span>
                                    </div>
                                    <div style="color: rgba(255,255,255,0.6); font-size: 0.7rem; margin-bottom: 0.5rem;">{{ __('Choose to retry:') }}</div>
                                    <div style="display: flex; gap: 0.5rem; width: 100%;">
                                        <button type="button"
                                                wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                                wire:loading.attr="disabled"
                                                style="flex: 1; padding: 0.5rem 0.4rem; background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(6,182,212,0.3)); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.7rem; display: flex; flex-direction: column; align-items: center; gap: 0.2rem;">
                                            <span style="font-size: 1rem;">🎨</span>
                                            <span>{{ __('Retry AI') }}</span>
                                        </button>
                                        <button type="button"
                                                wire:click="openStockBrowser({{ $index }})"
                                                style="flex: 1; padding: 0.5rem 0.4rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.7rem; display: flex; flex-direction: column; align-items: center; gap: 0.2rem;">
                                            <span style="font-size: 1rem;">📷</span>
                                            <span>{{ __('Use Stock') }}</span>
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Empty/Pending State --}}
                                <div class="vw-scene-empty">
                                    <div class="vw-scene-empty-text">{{ __('Choose image source:') }}</div>
                                    <div class="vw-scene-empty-buttons">
                                        <button type="button"
                                                class="vw-scene-empty-btn ai"
                                                wire:click="generateImage({{ $index }}, '{{ $scene['id'] }}')"
                                                wire:loading.attr="disabled">
                                            <span class="vw-scene-empty-btn-icon">🎨</span>
                                            <span>{{ __('AI Generate') }}</span>
                                            <span class="vw-scene-empty-btn-cost">{{ $imageModels[$selectedModel]['cost'] ?? 2 }} {{ __('tokens') }}</span>
                                        </button>
                                        <button type="button"
                                                class="vw-scene-empty-btn stock"
                                                wire:click="openStockBrowser({{ $index }})">
                                            <span class="vw-scene-empty-btn-icon">📷</span>
                                            <span>{{ __('Stock Media') }}</span>
                                            <span class="vw-scene-empty-btn-cost">{{ __('FREE') }}</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Multi-Shot Timeline (if decomposed) --}}
                    @if($hasMultiShot && !empty($decomposed['shots']))
                        <div style="padding: 0.5rem 0.75rem; border-top: 1px solid rgba(255,255,255,0.05); background: rgba(139,92,246,0.05);">
                            <div style="display: flex; gap: 0.25rem; overflow-x: auto;">
                                @foreach($decomposed['shots'] as $shotIdx => $shot)
                                    <div style="width: 40px; height: 24px; border-radius: 0.2rem; overflow: hidden; border: 2px solid {{ ($decomposed['selectedShot'] ?? 0) === $shotIdx ? '#8b5cf6' : 'rgba(255,255,255,0.1)' }}; cursor: pointer; flex-shrink: 0; position: relative;"
                                         wire:click="selectShot({{ $index }}, {{ $shotIdx }})">
                                        @if(($shot['status'] ?? '') === 'ready' && !empty($shot['imageUrl']))
                                            <img src="{{ $shot['imageUrl'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <div style="width: 100%; height: 100%; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;">
                                                <span style="font-size: 0.5rem; color: rgba(255,255,255,0.4);">{{ $shotIdx + 1 }}</span>
                                            </div>
                                        @endif
                                        @if(($decomposed['selectedShot'] ?? 0) === $shotIdx)
                                            <div style="position: absolute; bottom: 1px; right: 1px; width: 8px; height: 8px; background: #8b5cf6; border-radius: 50%;"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Prompt Section --}}
                    <div style="padding: 0.75rem;">
                        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.4); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            {{ __('PROMPT') }}
                        </div>
                        <div style="font-size: 0.8rem; color: rgba(255,255,255,0.75); line-height: 1.4; max-height: 3.2em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            {{ Str::limit($prompt, 120) }}
                        </div>
                        {{-- Duration & Transition --}}
                        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed rgba(255,255,255,0.08); display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.6rem; padding: 0.15rem 0.4rem; background: rgba(6,182,212,0.15); color: #67e8f9; border-radius: 0.2rem;">
                                ⏱️ {{ $scene['duration'] ?? 8 }}s
                            </span>
                            <span style="font-size: 0.6rem; color: rgba(255,255,255,0.4);">
                                ↔️ {{ $scene['transition'] ?? 'cut' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        </div> {{-- Close vw-storyboard-card --}}
    @endif

    {{-- Stock Media Browser Modal --}}
    @include('appvideowizard::livewire.modals.stock-browser')

    {{-- Style Bible Modal --}}
    @include('appvideowizard::livewire.modals.style-bible')

    {{-- Character Bible Modal --}}
    @include('appvideowizard::livewire.modals.character-bible')

    {{-- Location Bible Modal --}}
    @include('appvideowizard::livewire.modals.location-bible')

    {{-- Edit Prompt Modal --}}
    @include('appvideowizard::livewire.modals.edit-prompt')

    {{-- Multi-Shot Decomposition Modal --}}
    @include('appvideowizard::livewire.modals.multi-shot')

    {{-- Upscale Modal --}}
    @include('appvideowizard::livewire.modals.upscale')

    {{-- AI Edit Modal --}}
    @include('appvideowizard::livewire.modals.ai-edit')
</div>

<script>
    document.addEventListener('livewire:init', () => {
        let pollInterval = null;
        let pendingJobs = 0;

        // Listen for image generation started
        Livewire.on('image-generation-started', (data) => {
            if (data.async) {
                pendingJobs++;
                startPolling();
            }
        });

        // Listen for resume polling (after page refresh with pending jobs)
        Livewire.on('resume-job-polling', (data) => {
            pendingJobs = data.count || 0;
            if (pendingJobs > 0) {
                console.log('Resuming polling for', pendingJobs, 'pending jobs');
                startPolling();
            }
        });

        // Listen for poll status updates
        Livewire.on('poll-status', (data) => {
            pendingJobs = data.pendingJobs || 0;
            if (pendingJobs === 0) {
                stopPolling();
            }
        });

        // Listen for image ready events
        Livewire.on('image-ready', (data) => {
            console.log('Image ready for scene:', data.sceneIndex);
        });

        // Listen for image errors
        Livewire.on('image-error', (data) => {
            console.error('Image generation error:', data.error);
        });

        function startPolling() {
            if (pollInterval) return;
            pollInterval = setInterval(() => {
                if (pendingJobs > 0) {
                    Livewire.dispatch('poll-image-jobs');
                } else {
                    stopPolling();
                }
            }, 3000);
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        // Check for pending jobs on page load
        setTimeout(() => {
            Livewire.dispatch('check-pending-jobs');
        }, 1000);
    });
</script>
