{{-- Step 2: Concept Development --}}
<style>
    .vw-concept-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-concept-header {
        display: flex !important;
        align-items: flex-start !important;
        gap: 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-concept-icon {
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.5rem !important;
    }

    .vw-concept-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-concept-subtitle {
        font-size: 0.875rem !important;
        color: rgba(255, 255, 255, 0.6) !important;
        margin-top: 0.25rem !important;
    }

    .vw-context-bar {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border-radius: 0.5rem !important;
        padding: 0.75rem 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-context-left {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        color: rgba(255, 255, 255, 0.7) !important;
        font-size: 0.875rem !important;
    }

    .vw-context-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        background: rgba(139, 92, 246, 0.3) !important;
        color: #c4b5fd !important;
        padding: 0.375rem 0.75rem !important;
        border-radius: 0.375rem !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
    }

    .vw-context-badge.accent {
        background: rgba(236, 72, 153, 0.3) !important;
        color: #f9a8d4 !important;
    }

    .vw-context-arrow {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .vw-context-duration {
        color: #34d399 !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
    }

    .vw-field-label {
        font-size: 0.95rem !important;
        font-weight: 600 !important;
        color: #ffffff !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-textarea {
        width: 100% !important;
        min-height: 140px !important;
        background: rgba(0, 0, 0, 0.4) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        padding: 1rem !important;
        color: #ffffff !important;
        font-size: 0.95rem !important;
        line-height: 1.6 !important;
        resize: vertical !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }

    .vw-textarea:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
    }

    .vw-textarea::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .vw-input {
        width: 100% !important;
        background: rgba(0, 0, 0, 0.4) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        padding: 0.875rem 1rem !important;
        color: #ffffff !important;
        font-size: 0.95rem !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }

    .vw-input:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
    }

    .vw-input::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .vw-enhance-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%) !important;
        color: white !important;
        padding: 0.75rem 1.25rem !important;
        border-radius: 0.5rem !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        border: none !important;
        cursor: pointer !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        margin-top: 0.75rem !important;
    }

    .vw-enhance-btn:hover:not(:disabled) {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4) !important;
    }

    .vw-enhance-btn:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }

    .vw-enhance-hint {
        display: inline-block !important;
        margin-left: 0.75rem !important;
        color: rgba(255, 255, 255, 0.4) !important;
        font-size: 0.8rem !important;
    }

    .vw-field-group {
        margin-bottom: 1.5rem !important;
    }

    .vw-field-note {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-bottom: 0.5rem !important;
    }

    .vw-field-note .warning {
        color: #fbbf24 !important;
    }

    .vw-field-helper {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.4) !important;
        margin-top: 0.5rem !important;
    }

    .vw-generate-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        width: 100% !important;
        background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 50%, #06b6d4 100%) !important;
        color: white !important;
        padding: 1rem 1.5rem !important;
        border-radius: 0.75rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        border: none !important;
        cursor: pointer !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        margin-top: 1rem !important;
    }

    .vw-generate-btn:hover:not(:disabled) {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 25px rgba(236, 72, 153, 0.4) !important;
    }

    .vw-generate-btn:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }

    .vw-divider {
        height: 1px !important;
        background: rgba(255, 255, 255, 0.1) !important;
        margin: 1.5rem 0 !important;
    }

    /* Spinner Animation */
    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    /* Loading content inner wrapper */
    .vw-loading-inner {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }
    .vw-loading-inner.lg {
        gap: 0.5rem;
    }

    /* Your Concept Results Card */
    .vw-your-concept-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-your-concept-header {
        display: flex !important;
        align-items: flex-start !important;
        gap: 1rem !important;
        margin-bottom: 1.25rem !important;
    }

    .vw-your-concept-icon {
        width: 40px !important;
        height: 40px !important;
        min-width: 40px !important;
        background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%) !important;
        border-radius: 0.5rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.25rem !important;
    }

    .vw-main-concept-card {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(88, 28, 135, 0.2) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.3) !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-main-concept-title {
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-main-concept-text {
        color: rgba(255, 255, 255, 0.8) !important;
        line-height: 1.7 !important;
        font-size: 0.95rem !important;
    }

    .vw-concept-badges {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
        margin-top: 1rem !important;
    }

    .vw-concept-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        padding: 0.375rem 0.75rem !important;
        border-radius: 1rem !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
    }

    .vw-concept-badge.engaging {
        background: rgba(6, 182, 212, 0.2) !important;
        color: #22d3ee !important;
    }

    .vw-concept-badge.professional {
        background: rgba(16, 185, 129, 0.2) !important;
        color: #34d399 !important;
    }

    /* Alternative Directions */
    .vw-alt-directions-label {
        font-size: 0.85rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-alt-directions-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 0.75rem !important;
        margin-bottom: 1.5rem !important;
    }

    @media (max-width: 768px) {
        .vw-alt-directions-grid {
            grid-template-columns: 1fr !important;
        }
    }

    .vw-alt-card {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        padding: 1rem !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }

    .vw-alt-card:hover {
        border-color: rgba(139, 92, 246, 0.4) !important;
        background: rgba(139, 92, 246, 0.1) !important;
    }

    .vw-alt-card.selected {
        border-color: rgba(139, 92, 246, 0.6) !important;
        background: rgba(139, 92, 246, 0.15) !important;
    }

    .vw-alt-card-title {
        font-size: 0.9rem !important;
        font-weight: 600 !important;
        color: #ffffff !important;
        margin-bottom: 0.25rem !important;
    }

    .vw-alt-card-subtitle {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
    }

    .vw-generate-different-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        width: 100% !important;
        background: transparent !important;
        border: 1px dashed rgba(255, 255, 255, 0.2) !important;
        color: rgba(255, 255, 255, 0.6) !important;
        padding: 1rem !important;
        border-radius: 0.5rem !important;
        font-size: 0.9rem !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }

    .vw-generate-different-btn:hover {
        border-color: rgba(139, 92, 246, 0.4) !important;
        color: #c4b5fd !important;
        background: rgba(139, 92, 246, 0.05) !important;
    }

    /* Character Intelligence Section */
    .vw-character-intel-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-character-intel-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 1.25rem !important;
    }

    .vw-character-intel-left {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
    }

    .vw-character-intel-icon {
        width: 40px !important;
        height: 40px !important;
        min-width: 40px !important;
        background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%) !important;
        border-radius: 0.5rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.25rem !important;
    }

    .vw-character-intel-title {
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-character-intel-subtitle {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-top: 0.125rem !important;
    }

    .vw-character-intel-toggle {
        position: relative !important;
        width: 44px !important;
        height: 24px !important;
        background: rgba(255, 255, 255, 0.1) !important;
        border-radius: 12px !important;
        cursor: pointer !important;
        transition: background 0.2s !important;
    }

    .vw-character-intel-toggle.active {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%) !important;
    }

    .vw-character-intel-toggle::after {
        content: '' !important;
        position: absolute !important;
        top: 2px !important;
        left: 2px !important;
        width: 20px !important;
        height: 20px !important;
        background: white !important;
        border-radius: 50% !important;
        transition: transform 0.2s !important;
    }

    .vw-character-intel-toggle.active::after {
        transform: translateX(20px) !important;
    }

    .vw-narration-style-section {
        margin-bottom: 1.5rem !important;
    }

    .vw-narration-style-label {
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: rgba(255, 255, 255, 0.7) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-narration-style-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.75rem !important;
    }

    @media (max-width: 768px) {
        .vw-narration-style-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    .vw-narration-style-btn {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1rem 0.75rem !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
        text-align: center !important;
    }

    .vw-narration-style-btn:hover {
        border-color: rgba(139, 92, 246, 0.4) !important;
        background: rgba(139, 92, 246, 0.1) !important;
    }

    .vw-narration-style-btn.selected {
        border-color: rgba(139, 92, 246, 0.6) !important;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
    }

    .vw-narration-style-btn.disabled {
        opacity: 0.4 !important;
        cursor: not-allowed !important;
        text-decoration: line-through !important;
    }

    .vw-narration-style-icon {
        font-size: 1.5rem !important;
        margin-bottom: 0.5rem !important;
    }

    .vw-narration-style-name {
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: #ffffff !important;
    }

    .vw-narration-style-btn.selected .vw-narration-style-name {
        color: #c4b5fd !important;
    }

    .vw-character-count-section {
        margin-top: 1.5rem !important;
    }

    .vw-character-count-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-character-count-label {
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: rgba(255, 255, 255, 0.7) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }

    .vw-character-count-suggested {
        font-size: 0.8rem !important;
        color: #34d399 !important;
        font-weight: 500 !important;
    }

    .vw-character-slider-container {
        position: relative !important;
        padding: 0.5rem 0 !important;
    }

    .vw-character-slider {
        -webkit-appearance: none !important;
        appearance: none !important;
        width: 100% !important;
        height: 6px !important;
        background: linear-gradient(90deg, rgba(139, 92, 246, 0.3) 0%, rgba(139, 92, 246, 0.6) 100%) !important;
        border-radius: 3px !important;
        outline: none !important;
        cursor: pointer !important;
    }

    .vw-character-slider::-webkit-slider-thumb {
        -webkit-appearance: none !important;
        appearance: none !important;
        width: 20px !important;
        height: 20px !important;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%) !important;
        border-radius: 50% !important;
        cursor: pointer !important;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4) !important;
    }

    .vw-character-slider::-moz-range-thumb {
        width: 20px !important;
        height: 20px !important;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%) !important;
        border-radius: 50% !important;
        cursor: pointer !important;
        border: none !important;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4) !important;
    }

    .vw-character-count-value {
        position: absolute !important;
        right: 0 !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #c4b5fd !important;
        min-width: 30px !important;
        text-align: right !important;
    }

    .vw-character-count-hint {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.4) !important;
        margin-top: 0.5rem !important;
    }

    .vw-character-intel-disabled {
        opacity: 0.5 !important;
        pointer-events: none !important;
    }

    .vw-loading-opacity {
        opacity: 0.6 !important;
        pointer-events: none !important;
    }

    /* Enhancement Preview Section */
    .vw-enhancement-preview {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%) !important;
        border: 1px solid rgba(16, 185, 129, 0.3) !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-top: 1rem !important;
        animation: vw-fade-in 0.3s ease-out !important;
    }

    @keyframes vw-fade-in {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .vw-enhancement-header {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-enhancement-badge {
        background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%) !important;
        color: white !important;
        padding: 0.25rem 0.75rem !important;
        border-radius: 1rem !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
    }

    .vw-enhancement-logline {
        background: rgba(255, 255, 255, 0.05) !important;
        border-left: 3px solid #10b981 !important;
        padding: 0.75rem 1rem !important;
        border-radius: 0 0.5rem 0.5rem 0 !important;
        margin-bottom: 1rem !important;
        font-style: italic !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .vw-enhancement-refined {
        color: rgba(255, 255, 255, 0.8) !important;
        line-height: 1.6 !important;
        font-size: 0.9rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-enhancement-meta {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-enhancement-tag {
        background: rgba(255, 255, 255, 0.08) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        padding: 0.25rem 0.6rem !important;
        border-radius: 0.35rem !important;
        font-size: 0.75rem !important;
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .vw-enhancement-tag.mood {
        background: rgba(139, 92, 246, 0.15) !important;
        border-color: rgba(139, 92, 246, 0.3) !important;
        color: #c4b5fd !important;
    }

    .vw-enhancement-tag.tone {
        background: rgba(236, 72, 153, 0.15) !important;
        border-color: rgba(236, 72, 153, 0.3) !important;
        color: #f9a8d4 !important;
    }

    .vw-enhancement-actions {
        display: flex !important;
        gap: 0.5rem !important;
        margin-top: 1rem !important;
        padding-top: 1rem !important;
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .vw-apply-btn {
        background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%) !important;
        color: white !important;
        padding: 0.5rem 1rem !important;
        border-radius: 0.5rem !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        border: none !important;
        cursor: pointer !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
    }

    .vw-apply-btn:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4) !important;
    }

    .vw-dismiss-btn {
        background: rgba(255, 255, 255, 0.08) !important;
        color: rgba(255, 255, 255, 0.7) !important;
        padding: 0.5rem 1rem !important;
        border-radius: 0.5rem !important;
        font-size: 0.8rem !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        cursor: pointer !important;
    }

    .vw-dismiss-btn:hover {
        background: rgba(255, 255, 255, 0.12) !important;
    }

    /* Visual Mode Selector - Master Style Authority */
    .vw-visual-mode-section {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(139, 92, 246, 0.3) !important;
        border-radius: 0.75rem !important;
        padding: 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-visual-mode-header {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-visual-mode-title {
        font-size: 0.9rem !important;
        font-weight: 600 !important;
        color: #c4b5fd !important;
    }

    .vw-visual-mode-badge {
        background: rgba(139, 92, 246, 0.3) !important;
        color: #c4b5fd !important;
        padding: 0.2rem 0.5rem !important;
        border-radius: 0.25rem !important;
        font-size: 0.65rem !important;
        font-weight: 500 !important;
        text-transform: uppercase !important;
    }

    .vw-visual-mode-options {
        display: flex !important;
        gap: 0.75rem !important;
        flex-wrap: wrap !important;
    }

    .vw-visual-mode-option {
        flex: 1 !important;
        min-width: 140px !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 2px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        padding: 0.75rem !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }

    .vw-visual-mode-option:hover {
        border-color: rgba(139, 92, 246, 0.4) !important;
        background: rgba(139, 92, 246, 0.1) !important;
    }

    .vw-visual-mode-option.active {
        border-color: #8b5cf6 !important;
        background: rgba(139, 92, 246, 0.2) !important;
    }

    .vw-visual-mode-option.active .vw-mode-label {
        color: #c4b5fd !important;
    }

    .vw-mode-icon {
        font-size: 1.25rem !important;
        margin-bottom: 0.25rem !important;
    }

    .vw-mode-label {
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .vw-mode-desc {
        font-size: 0.7rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-top: 0.25rem !important;
    }
</style>

<div class="vw-concept-step">
    <div class="vw-concept-card">
        {{-- Error Message --}}
        @if($error)
            <div class="vw-error-alert" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; color: #fca5a5;">
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

        {{-- Visual Mode Selector - MASTER STYLE AUTHORITY --}}
        <div class="vw-visual-mode-section">
            <div class="vw-visual-mode-header">
                <span class="vw-visual-mode-title">🎨 {{ __('Visual Style') }}</span>
                <span class="vw-visual-mode-badge">{{ __('GLOBAL') }}</span>
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
                      class="vw-textarea"
                      placeholder="{{ __("Describe your idea, theme, or story... Be creative! Examples:
• A mysterious figure discovers an ancient power
• The untold story of a small town's greatest secret
• A journey through impossible landscapes
• An entrepreneur's rise from nothing") }}"></textarea>

            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem;">
                <button class="vw-enhance-btn"
                        wire:click="enhanceConcept"
                        wire:loading.attr="disabled"
                        wire:target="enhanceConcept, generateIdeas">
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
                <span class="vw-enhance-hint">{{ __('Auto-extracts styles & fills all fields') }}</span>
            </div>

            {{-- Enhancement Preview - Shows after AI enhancement --}}
            @if(!empty($concept['refinedConcept']))
                <div class="vw-enhancement-preview">
                    <div class="vw-enhancement-header">
                        <span class="vw-enhancement-badge">✨ {{ __('AI Enhanced') }}</span>
                        <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">{{ __('Preview of your enhanced concept') }}</span>
                    </div>

                    {{-- Logline --}}
                    @if(!empty($concept['logline']))
                        <div class="vw-enhancement-logline">
                            "{{ $concept['logline'] }}"
                        </div>
                    @endif

                    {{-- Refined Concept --}}
                    <div class="vw-enhancement-refined">
                        {{ $concept['refinedConcept'] }}
                    </div>

                    {{-- Mood/Tone/Key Elements --}}
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

                    {{-- Actions --}}
                    <div class="vw-enhancement-actions">
                        <button type="button" class="vw-apply-btn" wire:click="applyEnhancedConcept">
                            ✓ {{ __('Apply Enhancement') }}
                        </button>
                        <button type="button" class="vw-dismiss-btn" wire:click="dismissEnhancement">
                            {{ __('Keep Original') }}
                        </button>
                    </div>
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

    {{-- Character Intelligence Section --}}
    @if(!empty($conceptVariations) && count($conceptVariations) > 0)
        <div class="vw-character-intel-card">
            {{-- Header with Toggle --}}
            <div class="vw-character-intel-header">
                <div class="vw-character-intel-left">
                    <div class="vw-character-intel-icon">👥</div>
                    <div>
                        <h3 class="vw-character-intel-title">{{ __('Character Intelligence') }}</h3>
                        <p class="vw-character-intel-subtitle">{{ __('AI-suggested based on your production type') }}</p>
                    </div>
                </div>
                <div class="vw-character-intel-toggle {{ $characterIntelligence['enabled'] ? 'active' : '' }}"
                     wire:click="updateCharacterIntelligence('enabled', {{ $characterIntelligence['enabled'] ? 'false' : 'true' }})"
                     title="{{ $characterIntelligence['enabled'] ? __('Disable') : __('Enable') }} {{ __('Character Intelligence') }}">
                </div>
            </div>

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
                    <div class="vw-character-slider-container" style="display: flex; align-items: center; gap: 1rem;">
                        <input type="range"
                               class="vw-character-slider"
                               min="1"
                               max="10"
                               step="1"
                               value="{{ $characterIntelligence['characterCount'] }}"
                               wire:change="updateCharacterIntelligence('characterCount', $event.target.value)"
                               @if(!$characterIntelligence['enabled']) disabled @endif
                               style="flex: 1;">
                        <span class="vw-character-count-value" style="position: relative; transform: none;">{{ $characterIntelligence['characterCount'] }}</span>
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
        </div>
    @endif
</div>
