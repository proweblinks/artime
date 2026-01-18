{{-- Step 2: Concept Development --}}
<style>
    /* Scoped CSS for Concept Step - uses parent selector for specificity instead of !important */
    .vw-concept-step .vw-concept-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .vw-concept-step .vw-concept-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-concept-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .vw-concept-step .vw-concept-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
    }

    .vw-concept-step .vw-concept-subtitle {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.6);
        margin-top: 0.25rem;
    }

    .vw-concept-step .vw-context-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-context-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.875rem;
    }

    .vw-concept-step .vw-context-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        background: rgba(139, 92, 246, 0.3);
        color: #c4b5fd;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .vw-concept-step .vw-context-badge.accent {
        background: rgba(236, 72, 153, 0.3);
        color: #f9a8d4;
    }

    .vw-concept-step .vw-context-arrow {
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-concept-step .vw-context-duration {
        color: #34d399;
        font-weight: 600;
        font-size: 0.875rem;
    }

    /* Visual Mode Selector - Master Style Authority - PROMINENT POSITIONING */
    .vw-concept-step .vw-visual-mode-section {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(88, 28, 135, 0.2) 100%);
        border: 2px solid rgba(139, 92, 246, 0.4);
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-visual-mode-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-concept-step .vw-visual-mode-title {
        font-size: 1rem;
        font-weight: 700;
        color: #c4b5fd;
    }

    .vw-concept-step .vw-visual-mode-badge {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        color: white;
        padding: 0.25rem 0.6rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .vw-concept-step .vw-visual-mode-options {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .vw-concept-step .vw-visual-mode-option {
        flex: 1;
        min-width: 140px;
        background: rgba(0, 0, 0, 0.3);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        padding: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .vw-concept-step .vw-visual-mode-option:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-concept-step .vw-visual-mode-option.active {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.25);
        box-shadow: 0 0 15px rgba(139, 92, 246, 0.3);
    }

    .vw-concept-step .vw-visual-mode-option.active .vw-mode-label {
        color: #c4b5fd;
    }

    .vw-concept-step .vw-mode-icon {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .vw-concept-step .vw-mode-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
    }

    .vw-concept-step .vw-mode-desc {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
        margin-top: 0.25rem;
    }

    .vw-concept-step .vw-field-label {
        font-size: 0.95rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.75rem;
    }

    .vw-concept-step .vw-textarea {
        width: 100%;
        min-height: 140px;
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        padding: 1rem;
        color: #ffffff;
        font-size: 0.95rem;
        line-height: 1.6;
        resize: vertical;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .vw-concept-step .vw-textarea:focus {
        outline: none;
        border-color: rgba(139, 92, 246, 0.5);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .vw-concept-step .vw-textarea::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-concept-step .vw-textarea.enhanced {
        border-color: rgba(16, 185, 129, 0.5);
        background: rgba(16, 185, 129, 0.05);
    }

    .vw-concept-step .vw-input {
        width: 100%;
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        padding: 0.875rem 1rem;
        color: #ffffff;
        font-size: 0.95rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .vw-concept-step .vw-input:focus {
        outline: none;
        border-color: rgba(139, 92, 246, 0.5);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .vw-concept-step .vw-input::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-concept-step .vw-enhance-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 0.75rem;
    }

    .vw-concept-step .vw-enhance-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        color: white;
        padding: 0.75rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .vw-concept-step .vw-enhance-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    .vw-concept-step .vw-enhance-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .vw-concept-step .vw-enhance-hint {
        display: inline-block;
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.8rem;
    }

    .vw-concept-step .vw-enhanced-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
        color: white;
        padding: 0.5rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        animation: vw-fade-in 0.3s ease-out;
    }

    .vw-concept-step .vw-undo-btn {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: rgba(255, 255, 255, 0.6);
        padding: 0.5rem 0.75rem;
        border-radius: 0.35rem;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-concept-step .vw-undo-btn:hover {
        border-color: rgba(255, 255, 255, 0.4);
        color: rgba(255, 255, 255, 0.8);
    }

    .vw-concept-step .vw-field-group {
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-field-note {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.5rem;
    }

    .vw-concept-step .vw-field-note .warning {
        color: #fbbf24;
    }

    .vw-concept-step .vw-field-helper {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.4);
        margin-top: 0.5rem;
    }

    .vw-concept-step .vw-generate-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 50%, #06b6d4 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 700;
        font-size: 1rem;
        border: none;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-top: 1rem;
    }

    .vw-concept-step .vw-generate-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(236, 72, 153, 0.4);
    }

    .vw-concept-step .vw-generate-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .vw-concept-step .vw-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 1.5rem 0;
    }

    /* Spinner Animation */
    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    @keyframes vw-fade-in {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .vw-concept-step .vw-loading-inner {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .vw-concept-step .vw-loading-inner.lg {
        gap: 0.5rem;
    }

    /* Enhancement Meta Tags */
    .vw-concept-step .vw-enhancement-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-concept-step .vw-enhancement-tag {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 0.25rem 0.6rem;
        border-radius: 0.35rem;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-concept-step .vw-enhancement-tag.mood {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.3);
        color: #c4b5fd;
    }

    .vw-concept-step .vw-enhancement-tag.tone {
        background: rgba(236, 72, 153, 0.15);
        border-color: rgba(236, 72, 153, 0.3);
        color: #f9a8d4;
    }

    /* Your Concept Results Card */
    .vw-concept-step .vw-your-concept-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-your-concept-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .vw-concept-step .vw-your-concept-icon {
        width: 40px;
        height: 40px;
        min-width: 40px;
        background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .vw-concept-step .vw-main-concept-card {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(88, 28, 135, 0.2) 100%);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-main-concept-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 0.75rem;
    }

    .vw-concept-step .vw-main-concept-text {
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.7;
        font-size: 0.95rem;
    }

    .vw-concept-step .vw-concept-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .vw-concept-step .vw-concept-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .vw-concept-step .vw-concept-badge.engaging {
        background: rgba(6, 182, 212, 0.2);
        color: #22d3ee;
    }

    .vw-concept-step .vw-concept-badge.professional {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
    }

    /* Alternative Directions */
    .vw-concept-step .vw-alt-directions-label {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.75rem;
    }

    .vw-concept-step .vw-alt-directions-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .vw-concept-step .vw-alt-directions-grid {
            grid-template-columns: 1fr;
        }
    }

    .vw-concept-step .vw-alt-card {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-concept-step .vw-alt-card:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-concept-step .vw-alt-card.selected {
        border-color: rgba(139, 92, 246, 0.6);
        background: rgba(139, 92, 246, 0.15);
    }

    .vw-concept-step .vw-alt-card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.25rem;
    }

    .vw-concept-step .vw-alt-card-subtitle {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-concept-step .vw-generate-different-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        background: transparent;
        border: 1px dashed rgba(255, 255, 255, 0.2);
        color: rgba(255, 255, 255, 0.6);
        padding: 1rem;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-concept-step .vw-generate-different-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        color: #c4b5fd;
        background: rgba(139, 92, 246, 0.05);
    }

    /* Character Intelligence Section - Always visible */
    .vw-concept-step .vw-character-intel-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-character-intel-card.preview-mode {
        opacity: 0.7;
        border-style: dashed;
    }

    .vw-concept-step .vw-character-intel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
    }

    .vw-concept-step .vw-character-intel-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-concept-step .vw-character-intel-icon {
        width: 40px;
        height: 40px;
        min-width: 40px;
        background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .vw-concept-step .vw-character-intel-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
    }

    .vw-concept-step .vw-character-intel-subtitle {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.5);
        margin-top: 0.125rem;
    }

    .vw-concept-step .vw-character-intel-toggle {
        position: relative;
        width: 44px;
        height: 24px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .vw-concept-step .vw-character-intel-toggle.active {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    }

    .vw-concept-step .vw-character-intel-toggle::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        transition: transform 0.2s;
    }

    .vw-concept-step .vw-character-intel-toggle.active::after {
        transform: translateX(20px);
    }

    .vw-concept-step .vw-preview-hint {
        text-align: center;
        padding: 1rem;
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.85rem;
        font-style: italic;
    }

    .vw-concept-step .vw-narration-style-section {
        margin-bottom: 1.5rem;
    }

    .vw-concept-step .vw-narration-style-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.75rem;
    }

    .vw-concept-step .vw-narration-style-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-concept-step .vw-narration-style-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-concept-step .vw-narration-style-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1rem 0.75rem;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .vw-concept-step .vw-narration-style-btn:hover:not(:disabled) {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-concept-step .vw-narration-style-btn.selected {
        border-color: rgba(139, 92, 246, 0.6);
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%);
    }

    .vw-concept-step .vw-narration-style-btn.disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .vw-concept-step .vw-narration-style-icon {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .vw-concept-step .vw-narration-style-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: #ffffff;
    }

    .vw-concept-step .vw-narration-style-btn.selected .vw-narration-style-name {
        color: #c4b5fd;
    }

    .vw-concept-step .vw-character-count-section {
        margin-top: 1.5rem;
    }

    .vw-concept-step .vw-character-count-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .vw-concept-step .vw-character-count-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .vw-concept-step .vw-character-count-suggested {
        font-size: 0.8rem;
        color: #34d399;
        font-weight: 500;
    }

    .vw-concept-step .vw-character-slider-container {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .vw-concept-step .vw-character-slider {
        -webkit-appearance: none;
        appearance: none;
        flex: 1;
        height: 6px;
        background: linear-gradient(90deg, rgba(139, 92, 246, 0.3) 0%, rgba(139, 92, 246, 0.6) 100%);
        border-radius: 3px;
        outline: none;
        cursor: pointer;
    }

    .vw-concept-step .vw-character-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
    }

    .vw-concept-step .vw-character-slider::-moz-range-thumb {
        width: 20px;
        height: 20px;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        border-radius: 50%;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
    }

    .vw-concept-step .vw-character-count-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #c4b5fd;
        min-width: 30px;
        text-align: right;
    }

    .vw-concept-step .vw-character-count-hint {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.4);
        margin-top: 0.5rem;
    }

    .vw-concept-step .vw-character-intel-disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    .vw-concept-step .vw-loading-opacity {
        opacity: 0.6;
        pointer-events: none;
    }

    .vw-concept-step .vw-error-alert {
        background: rgba(239, 68, 68, 0.2);
        border: 1px solid rgba(239, 68, 68, 0.4);
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        color: #fca5a5;
    }
</style>

<div class="vw-concept-step">
    <div class="vw-concept-card">
        {{-- Error Message --}}
        @if($error)
            <div class="vw-error-alert">
                <span style="margin-right: 0.5rem;">⚠️</span>
                {{ $error }}
            </div>
        @endif

        {{-- Header --}}
        <div class="vw-concept-header">
            <div class="vw-concept-icon">💡</div>
            <div>
                <h2 class="vw-concept-title">{{ __('Develop Your Concept') }}</h2>
                <p class="vw-concept-subtitle">{{ __("Tell us what you want to create - we'll generate unique ideas") }}</p>
            </div>
        </div>

        {{-- Context Bar --}}
        @if($productionType)
            @php
                $productionTypes = config('appvideowizard.production_types', []);
                $typeName = $productionTypes[$productionType]['name'] ?? ucfirst($productionType);
                $subtypeName = '';
                if ($productionSubtype && isset($productionTypes[$productionType]['subTypes'][$productionSubtype])) {
                    $subtypeName = $productionTypes[$productionType]['subTypes'][$productionSubtype]['name'];
                }
                $durationMin = floor($targetDuration / 60);
                $durationSec = $targetDuration % 60;
                $durationText = $durationMin > 0 ? ($durationMin . 'm' . ($durationSec > 0 ? ' ' . $durationSec . 's' : '')) : ($durationSec . 's');
            @endphp
            <div class="vw-context-bar">
                <div class="vw-context-left">
                    <span>{{ __('Creating:') }}</span>
                    <span class="vw-context-badge">
                        🎬 {{ $typeName }}
                    </span>
                    @if($subtypeName)
                        <span class="vw-context-arrow">→</span>
                        <span class="vw-context-badge accent">
                            🎯 {{ $subtypeName }}
                        </span>
                    @endif
                </div>
                <div class="vw-context-duration">{{ $durationText }}</div>
            </div>
        @endif

        {{-- Visual Mode Selector - MASTER STYLE AUTHORITY - Prominent at top --}}
        <div class="vw-visual-mode-section">
            <div class="vw-visual-mode-header">
                <span class="vw-visual-mode-title">🎨 {{ __('Visual Style') }}</span>
                <span class="vw-visual-mode-badge">{{ __('MASTER') }}</span>
            </div>
            <div class="vw-visual-mode-options">
                <div class="vw-visual-mode-option {{ ($content['visualMode'] ?? 'cinematic-realistic') === 'cinematic-realistic' ? 'active' : '' }}"
                     wire:click="setVisualMode('cinematic-realistic')">
                    <div class="vw-mode-icon">🎬</div>
                    <div class="vw-mode-label">{{ __('Cinematic Realistic') }}</div>
                    <div class="vw-mode-desc">{{ __('Live-action, photorealistic, Hollywood quality') }}</div>
                </div>
                <div class="vw-visual-mode-option {{ ($content['visualMode'] ?? '') === 'stylized-animation' ? 'active' : '' }}"
                     wire:click="setVisualMode('stylized-animation')">
                    <div class="vw-mode-icon">✨</div>
                    <div class="vw-mode-label">{{ __('Stylized Animation') }}</div>
                    <div class="vw-mode-desc">{{ __('2D/3D animation, cartoon, anime') }}</div>
                </div>
                <div class="vw-visual-mode-option {{ ($content['visualMode'] ?? '') === 'mixed-hybrid' ? 'active' : '' }}"
                     wire:click="setVisualMode('mixed-hybrid')">
                    <div class="vw-mode-icon">🎭</div>
                    <div class="vw-mode-label">{{ __('Mixed / Hybrid') }}</div>
                    <div class="vw-mode-desc">{{ __('Combination of styles') }}</div>
                </div>
            </div>
        </div>

        {{-- Main Input --}}
        <div class="vw-field-group">
            <label class="vw-field-label">{{ __("What's your video about?") }}</label>
            <textarea wire:model.blur="concept.rawInput"
                      class="vw-textarea {{ !empty($concept['refinedConcept']) ? 'enhanced' : '' }}"
                      placeholder="{{ __("Describe your idea, theme, or story... Be creative! Examples:
• A mysterious figure discovers an ancient power
• The untold story of a small town's greatest secret
• A journey through impossible landscapes
• An entrepreneur's rise from nothing") }}"></textarea>

            <div class="vw-enhance-row">
                @if(!empty($concept['refinedConcept']))
                    {{-- Show enhanced badge with undo option --}}
                    <span class="vw-enhanced-badge">
                        ✓ {{ __('AI Enhanced') }}
                    </span>
                    <button type="button" class="vw-undo-btn" wire:click="dismissEnhancement">
                        ↩ {{ __('Undo Enhancement') }}
                    </button>
                @else
                    {{-- Show enhance button --}}
                    <button class="vw-enhance-btn"
                            wire:click="enhanceConcept"
                            wire:loading.attr="disabled"
                            wire:target="enhanceConcept, generateIdeas"
                            @if(empty($concept['rawInput'])) disabled @endif>
                        <span wire:loading.remove wire:target="enhanceConcept">✨ {{ __('Enhance with AI') }}</span>
                        <span wire:loading wire:target="enhanceConcept">
                            <span class="vw-loading-inner">
                                <svg style="width: 14px; height: 14px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                </svg>
                                {{ __('Enhancing...') }}
                            </span>
                        </span>
                    </button>
                    <span class="vw-enhance-hint">{{ __('Auto-improves your concept with AI') }}</span>
                @endif
            </div>

            {{-- Enhancement Meta Tags (mood, tone, etc.) - shows when enhanced --}}
            @if(!empty($concept['suggestedMood']) || !empty($concept['suggestedTone']) || !empty($concept['keyElements']))
                <div class="vw-enhancement-meta">
                    @if(!empty($concept['suggestedMood']))
                        <span class="vw-enhancement-tag mood">🎭 {{ ucfirst($concept['suggestedMood']) }}</span>
                    @endif
                    @if(!empty($concept['suggestedTone']))
                        <span class="vw-enhancement-tag tone">🎯 {{ ucfirst($concept['suggestedTone']) }}</span>
                    @endif
                    @if(!empty($concept['targetAudience']))
                        <span class="vw-enhancement-tag">👥 {{ $concept['targetAudience'] }}</span>
                    @endif
                    @if(!empty($concept['keyElements']) && is_array($concept['keyElements']))
                        @foreach(array_slice($concept['keyElements'], 0, 3) as $element)
                            <span class="vw-enhancement-tag">{{ $element }}</span>
                        @endforeach
                    @endif
                </div>
            @endif
        </div>

        <div class="vw-divider"></div>

        {{-- Style Inspiration --}}
        <div class="vw-field-group">
            <label class="vw-field-label">{{ __('Style Inspiration (Optional)') }}</label>
            <div class="vw-field-note">
                <span class="warning">⚠️</span>
                <span>{{ __('This is for VISUAL STYLE only - your content will be 100% original') }}</span>
            </div>
            <input type="text"
                   wire:model.blur="concept.styleReference"
                   class="vw-input"
                   placeholder="{{ __("e.g., 'Breaking Bad cinematography', 'Wes Anderson color palette', 'documentary noir'") }}">
            <p class="vw-field-helper">{{ __("We'll capture the visual FEEL without copying any content or characters") }}</p>
        </div>

        {{-- Things to Avoid --}}
        <div class="vw-field-group">
            <label class="vw-field-label">{{ __('Things to Avoid (Optional)') }}</label>
            <input type="text"
                   wire:model.blur="concept.avoidElements"
                   class="vw-input"
                   placeholder="{{ __("e.g., 'specific brand names', 'real people', 'trademarked characters'") }}">
        </div>

        {{-- Generate Button --}}
        <button class="vw-generate-btn"
                wire:click="generateIdeas"
                wire:loading.attr="disabled"
                wire:target="generateIdeas, enhanceConcept"
                @if(empty($concept['rawInput'])) disabled @endif>
            <span wire:loading.remove wire:target="generateIdeas">✨ {{ __('Generate Unique Ideas') }}</span>
            <span wire:loading wire:target="generateIdeas">
                <span class="vw-loading-inner lg">
                    <svg style="width: 18px; height: 18px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                    </svg>
                    {{ __('Generating...') }}
                </span>
            </span>
        </button>
    </div>

    {{-- Your Concept Results Section --}}
    @if(!empty($conceptVariations) && count($conceptVariations) > 0)
        <div class="vw-your-concept-card">
            {{-- Header --}}
            <div class="vw-your-concept-header">
                <div class="vw-your-concept-icon">🎬</div>
                <div>
                    <h3 class="vw-concept-title">{{ __('Your Concept') }}</h3>
                    <p class="vw-concept-subtitle">{{ __('Select a concept direction below') }}</p>
                </div>
            </div>

            {{-- Main Selected Concept Card --}}
            @php
                $selectedVariation = $conceptVariations[$selectedConceptIndex] ?? $conceptVariations[0] ?? null;
            @endphp
            @if($selectedVariation)
                <div class="vw-main-concept-card">
                    <h4 class="vw-main-concept-title">{{ $selectedVariation['title'] ?? (__($productionType ?? 'movie') . ' ' . __('Concept') . ' ' . ($selectedConceptIndex + 1)) }}</h4>
                    <p class="vw-main-concept-text">{{ $selectedVariation['concept'] ?? $concept['refinedConcept'] ?? $concept['rawInput'] }}</p>
                    <div class="vw-concept-badges">
                        @if(!empty($concept['suggestedTone']))
                            <span class="vw-concept-badge engaging">✨ {{ ucfirst($concept['suggestedTone']) }}</span>
                        @else
                            <span class="vw-concept-badge engaging">✨ {{ __('Engaging') }}</span>
                        @endif
                        @if(!empty($concept['suggestedMood']))
                            <span class="vw-concept-badge professional">🎯 {{ ucfirst($concept['suggestedMood']) }}</span>
                        @else
                            <span class="vw-concept-badge professional">🎯 {{ __('Professional') }}</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Alternative Directions --}}
            <div class="vw-alt-directions-label">
                {{ __('Alternative Directions') }} <span style="color: rgba(255,255,255,0.3);">({{ __('click to switch') }})</span>
            </div>
            <div class="vw-alt-directions-grid" wire:loading.class="vw-loading-opacity" wire:target="selectConceptVariation">
                @foreach($conceptVariations as $index => $variation)
                    <div class="vw-alt-card {{ $selectedConceptIndex === $index ? 'selected' : '' }}"
                         wire:click="selectConceptVariation({{ $index }})"
                         wire:loading.attr="disabled"
                         wire:target="selectConceptVariation"
                         style="{{ $selectedConceptIndex === $index ? '' : 'cursor: pointer;' }}">
                        <div class="vw-alt-card-title">{{ ($index + 1) }}. {{ $variation['title'] ?? (__($productionType ?? 'movie') . ' ' . __('Concept') . ' ' . ($index + 1)) }}</div>
                        <div class="vw-alt-card-subtitle">{{ $variation['angle'] ?? ucfirst($variation['strengths'][0] ?? __('Engaging')) }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Generate Different Concepts Button --}}
            <button class="vw-generate-different-btn"
                    wire:click="generateDifferentConcepts"
                    wire:loading.attr="disabled"
                    wire:target="generateDifferentConcepts">
                <span wire:loading.remove wire:target="generateDifferentConcepts">🎬 {{ __('Generate Different Concepts') }}</span>
                <span wire:loading wire:target="generateDifferentConcepts">
                    <span class="vw-loading-inner">
                        <svg style="width: 14px; height: 14px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                        </svg>
                        {{ __('Generating...') }}
                    </span>
                </span>
            </button>
        </div>
    @endif

    {{-- Character Intelligence Section - Always visible --}}
    <div class="vw-character-intel-card {{ empty($conceptVariations) || count($conceptVariations) === 0 ? 'preview-mode' : '' }}">
        {{-- Header with Toggle --}}
        <div class="vw-character-intel-header">
            <div class="vw-character-intel-left">
                <div class="vw-character-intel-icon">👥</div>
                <div>
                    <h3 class="vw-character-intel-title">{{ __('Character Intelligence') }}</h3>
                    <p class="vw-character-intel-subtitle">{{ __('AI-suggested based on your production type') }}</p>
                </div>
            </div>
            @if(!empty($conceptVariations) && count($conceptVariations) > 0)
                <div class="vw-character-intel-toggle {{ $characterIntelligence['enabled'] ? 'active' : '' }}"
                     wire:click="updateCharacterIntelligence('enabled', {{ $characterIntelligence['enabled'] ? 'false' : 'true' }})"
                     title="{{ $characterIntelligence['enabled'] ? __('Disable') : __('Enable') }} {{ __('Character Intelligence') }}">
                </div>
            @endif
        </div>

        {{-- Preview hint when no variations yet --}}
        @if(empty($conceptVariations) || count($conceptVariations) === 0)
            <div class="vw-preview-hint">
                💡 {{ __('Generate your concept ideas above to unlock character settings') }}
            </div>
        @else
            {{-- Content (conditionally shown based on enabled state) --}}
            <div class="{{ !$characterIntelligence['enabled'] ? 'vw-character-intel-disabled' : '' }}">
                {{-- Narration Style Section --}}
                <div class="vw-narration-style-section">
                    <div class="vw-narration-style-label">{{ __('NARRATION STYLE') }}</div>
                    <div class="vw-narration-style-grid">
                        @php
                            $narrationStyles = config('appvideowizard.narration_styles', [
                                'voiceover' => ['name' => 'Voiceover', 'icon' => '🎙️', 'description' => 'Off-screen narrator'],
                                'dialogue' => ['name' => 'Dialogue', 'icon' => '💬', 'description' => 'Character conversations', 'disabled' => true],
                                'narrator' => ['name' => 'Narrator', 'icon' => '📖', 'description' => 'On-screen narrator'],
                                'none' => ['name' => 'No Voice', 'icon' => '🔇', 'description' => 'Music/ambient only'],
                            ]);
                        @endphp
                        @foreach($narrationStyles as $styleKey => $style)
                            <button type="button"
                                    class="vw-narration-style-btn {{ $characterIntelligence['narrationStyle'] === $styleKey ? 'selected' : '' }} {{ !empty($style['disabled']) ? 'disabled' : '' }}"
                                    wire:click="updateCharacterIntelligence('narrationStyle', '{{ $styleKey }}')"
                                    @if(!empty($style['disabled'])) disabled title="{{ __('Coming soon') }}" @endif
                                    @if(!$characterIntelligence['enabled']) disabled @endif>
                                <span class="vw-narration-style-icon">{{ $style['icon'] }}</span>
                                <span class="vw-narration-style-name">{{ __($style['name']) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Character Count Section --}}
                <div class="vw-character-count-section">
                    <div class="vw-character-count-header">
                        <span class="vw-character-count-label">{{ __('CHARACTER COUNT') }}</span>
                        <span class="vw-character-count-suggested">{{ __('SUGGESTED:') }} {{ $characterIntelligence['suggestedCount'] }}</span>
                    </div>
                    <div class="vw-character-slider-container">
                        <input type="range"
                               class="vw-character-slider"
                               min="1"
                               max="10"
                               step="1"
                               value="{{ $characterIntelligence['characterCount'] }}"
                               wire:change="updateCharacterIntelligence('characterCount', $event.target.value)"
                               @if(!$characterIntelligence['enabled']) disabled @endif>
                        <span class="vw-character-count-value">{{ $characterIntelligence['characterCount'] }}</span>
                    </div>
                    <p class="vw-character-count-hint">
                        @if($characterIntelligence['characterCount'] < 2)
                            {{ __('Single character focus - monologue style') }}
                        @elseif($characterIntelligence['characterCount'] <= 4)
                            {{ __('Minimum 2 characters recommended for dynamic scenes') }}
                        @else
                            {{ __('Multiple characters - ensemble cast style') }}
                        @endif
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
