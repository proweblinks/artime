{{-- Step 3: Script Generation --}}
<style>
    .vw-script-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-script-header {
        display: flex !important;
        align-items: flex-start !important;
        gap: 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-script-icon {
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.5rem !important;
    }

    .vw-script-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-script-subtitle {
        font-size: 0.875rem !important;
        color: rgba(255, 255, 255, 0.6) !important;
        margin-top: 0.25rem !important;
    }

    /* Direct Concept Card */
    .vw-direct-concept-card {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(88, 28, 135, 0.2) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.3) !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-bottom: 1.5rem !important;
        position: relative !important;
    }

    .vw-direct-concept-label {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        color: #f472b6 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-direct-concept-badges {
        position: absolute !important;
        top: 1rem !important;
        right: 1rem !important;
        display: flex !important;
        gap: 0.5rem !important;
        flex-wrap: wrap !important;
    }

    .vw-type-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        padding: 0.25rem 0.625rem !important;
        border-radius: 0.375rem !important;
        font-size: 0.75rem !important;
        font-weight: 500 !important;
    }

    .vw-type-badge.passthrough {
        background: rgba(16, 185, 129, 0.2) !important;
        color: #34d399 !important;
    }

    .vw-type-badge.production {
        background: rgba(139, 92, 246, 0.2) !important;
        color: #c4b5fd !important;
    }

    .vw-type-badge.subtype {
        background: rgba(236, 72, 153, 0.2) !important;
        color: #f9a8d4 !important;
    }

    .vw-direct-concept-title {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-direct-concept-text {
        color: rgba(255, 255, 255, 0.8) !important;
        line-height: 1.7 !important;
        font-size: 0.95rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-concept-meta {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 1rem !important;
    }

    .vw-concept-meta-left {
        display: flex !important;
        align-items: center !important;
        gap: 1.5rem !important;
        flex-wrap: wrap !important;
    }

    .vw-concept-meta-item {
        display: flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        font-size: 0.85rem !important;
    }

    .vw-concept-meta-item span:first-child {
        color: rgba(255, 255, 255, 0.5) !important;
    }

    .vw-concept-meta-item span:last-child {
        color: #34d399 !important;
        font-weight: 500 !important;
    }

    .vw-concept-meta-item.duration span:last-child {
        color: #f472b6 !important;
    }

    .vw-char-count {
        font-size: 0.8rem !important;
        color: #34d399 !important;
    }

    /* Selector Sections */
    .vw-selector-section {
        margin-bottom: 1.5rem !important;
    }

    .vw-selector-label {
        font-size: 0.95rem !important;
        font-weight: 600 !important;
        color: #ffffff !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-selector-sublabel {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        font-weight: 400 !important;
        margin-left: 0.5rem !important;
    }

    .vw-selector-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.75rem !important;
    }

    @media (max-width: 768px) {
        .vw-selector-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    .vw-selector-btn {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1rem !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        color: rgba(255, 255, 255, 0.7) !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
        text-align: center !important;
    }

    .vw-selector-btn:hover {
        border-color: rgba(139, 92, 246, 0.4) !important;
        background: rgba(139, 92, 246, 0.1) !important;
    }

    .vw-selector-btn.selected {
        border-color: rgba(139, 92, 246, 0.6) !important;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
        color: #ffffff !important;
    }

    .vw-selector-btn-title {
        font-weight: 600 !important;
        font-size: 0.9rem !important;
    }

    .vw-selector-btn-subtitle {
        font-size: 0.75rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-top: 0.25rem !important;
    }

    .vw-selector-btn.selected .vw-selector-btn-subtitle {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    /* Additional Instructions */
    .vw-instructions-textarea {
        width: 100% !important;
        min-height: 100px !important;
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

    .vw-instructions-textarea:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
    }

    .vw-instructions-textarea::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    /* Generate Button */
    .vw-generate-script-btn {
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
        margin-top: 1.5rem !important;
    }

    .vw-generate-script-btn:hover:not(:disabled) {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 25px rgba(236, 72, 153, 0.4) !important;
    }

    .vw-generate-script-btn:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }

    .vw-cost-estimate {
        text-align: center !important;
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.4) !important;
        margin-top: 0.75rem !important;
    }

    /* Spinner Animation */
    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    /* Loading content inner wrapper */
    .vw-loading-inner {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Script Results Section */
    .vw-script-results {
        margin-top: 1.5rem !important;
    }

    .vw-scene-card {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-scene-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 0.75rem !important;
    }

    .vw-scene-number {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 28px !important;
        height: 28px !important;
        background: rgba(139, 92, 246, 0.3) !important;
        border-radius: 50% !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: #c4b5fd !important;
        margin-right: 0.75rem !important;
    }

    .vw-scene-title {
        font-weight: 600 !important;
        color: #ffffff !important;
    }

    .vw-scene-duration {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        background: rgba(0, 0, 0, 0.3) !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 0.25rem !important;
    }

    .vw-scene-narration {
        color: rgba(255, 255, 255, 0.8) !important;
        font-size: 0.9rem !important;
        line-height: 1.6 !important;
    }

    /* Script Stats Bar */
    .vw-script-stats-bar {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        padding: 0.875rem 1.25rem !important;
        margin-bottom: 1.25rem !important;
        flex-wrap: wrap !important;
        gap: 1rem !important;
    }

    .vw-script-stats-left {
        display: flex !important;
        align-items: center !important;
        gap: 2rem !important;
        flex-wrap: wrap !important;
    }

    .vw-script-stat {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.125rem !important;
    }

    .vw-script-stat-label {
        font-size: 0.7rem !important;
        font-weight: 600 !important;
        color: rgba(255, 255, 255, 0.5) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }

    .vw-script-stat-value {
        font-size: 1rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
    }

    .vw-script-stat-value.highlight {
        color: #34d399 !important;
    }

    .vw-pacing-indicator {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        padding: 0.5rem 0.875rem !important;
        background: rgba(139, 92, 246, 0.2) !important;
        border-radius: 0.375rem !important;
        font-size: 0.85rem !important;
        color: #c4b5fd !important;
        font-weight: 500 !important;
    }

    /* Voice & Dialogue Status Panel */
    .vw-voice-status-panel {
        background: rgba(0, 0, 0, 0.25) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-voice-status-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 1rem !important;
    }

    .vw-voice-status-title {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        font-size: 0.95rem !important;
        font-weight: 600 !important;
        color: #ffffff !important;
    }

    .vw-voice-pending-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        background: rgba(251, 191, 36, 0.2) !important;
        color: #fbbf24 !important;
        padding: 0.375rem 0.75rem !important;
        border-radius: 0.375rem !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
    }

    .vw-voice-status-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.75rem !important;
    }

    @media (max-width: 768px) {
        .vw-voice-status-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    .vw-voice-stat-card {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 0.5rem !important;
        padding: 1rem !important;
        text-align: center !important;
    }

    .vw-voice-stat-value {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin-bottom: 0.25rem !important;
    }

    .vw-voice-stat-label {
        font-size: 0.75rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
    }

    /* Full Script View Button */
    .vw-full-script-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: rgba(255, 255, 255, 0.7) !important;
        padding: 0.5rem 0.875rem !important;
        border-radius: 0.375rem !important;
        font-size: 0.85rem !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }

    .vw-full-script-btn:hover {
        border-color: rgba(139, 92, 246, 0.4) !important;
        color: #c4b5fd !important;
        background: rgba(139, 92, 246, 0.1) !important;
    }

    /* Full Script Modal */
    .vw-full-script-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 100 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 2rem !important;
    }

    .vw-full-script-overlay {
        position: absolute !important;
        inset: 0 !important;
        background: rgba(0, 0, 0, 0.75) !important;
        backdrop-filter: blur(4px) !important;
    }

    .vw-full-script-content {
        position: relative !important;
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.98) 0%, rgba(20, 20, 35, 1) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.3) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        max-width: 800px !important;
        width: 100% !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
    }

    .vw-full-script-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 1.25rem !important;
        padding-bottom: 1rem !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .vw-full-script-close {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 32px !important;
        height: 32px !important;
        background: rgba(255, 255, 255, 0.1) !important;
        border: none !important;
        border-radius: 50% !important;
        color: rgba(255, 255, 255, 0.7) !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }

    .vw-full-script-close:hover {
        background: rgba(239, 68, 68, 0.3) !important;
        color: #fca5a5 !important;
    }

    .vw-full-script-text {
        color: rgba(255, 255, 255, 0.85) !important;
        line-height: 1.8 !important;
        font-size: 0.95rem !important;
        white-space: pre-wrap !important;
    }

    .vw-full-script-scene-divider {
        margin: 1.5rem 0 !important;
        text-align: center !important;
        color: rgba(255, 255, 255, 0.3) !important;
        font-size: 0.8rem !important;
    }

    /* Advanced Scene Cards */
    .vw-advanced-scene-card {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
        margin-bottom: 1rem !important;
        overflow: hidden !important;
        transition: border-color 0.2s !important;
    }

    .vw-advanced-scene-card:hover {
        border-color: rgba(139, 92, 246, 0.3) !important;
    }

    .vw-advanced-scene-card.expanded {
        border-color: rgba(139, 92, 246, 0.4) !important;
    }

    .vw-scene-card-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 1rem 1.25rem !important;
        cursor: pointer !important;
        user-select: none !important;
    }

    .vw-scene-card-header-left {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        flex: 1 !important;
    }

    .vw-scene-expand-icon {
        color: rgba(255, 255, 255, 0.5) !important;
        transition: transform 0.2s !important;
        font-size: 0.9rem !important;
    }

    .vw-advanced-scene-card.expanded .vw-scene-expand-icon {
        transform: rotate(180deg) !important;
    }

    .vw-scene-music-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        padding: 0.25rem 0.5rem !important;
        background: rgba(251, 191, 36, 0.2) !important;
        color: #fbbf24 !important;
        border-radius: 0.25rem !important;
        font-size: 0.7rem !important;
        font-weight: 500 !important;
    }

    .vw-scene-meta-badges {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        flex-wrap: wrap !important;
    }

    .vw-scene-meta-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        padding: 0.25rem 0.5rem !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border-radius: 0.25rem !important;
        font-size: 0.75rem !important;
        color: rgba(255, 255, 255, 0.6) !important;
    }

    .vw-scene-card-body {
        padding: 0 1.25rem 1.25rem !important;
        border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    .vw-scene-section {
        margin-top: 1.25rem !important;
    }

    .vw-scene-section-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 0.5rem !important;
    }

    .vw-scene-section-label {
        display: flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .vw-scene-write-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        padding: 0.375rem 0.625rem !important;
        background: rgba(139, 92, 246, 0.2) !important;
        border: 1px solid rgba(139, 92, 246, 0.3) !important;
        border-radius: 0.375rem !important;
        color: #c4b5fd !important;
        font-size: 0.75rem !important;
        font-weight: 500 !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }

    .vw-scene-write-btn:hover:not(:disabled) {
        background: rgba(139, 92, 246, 0.3) !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
    }

    .vw-scene-write-btn:disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
    }

    .vw-scene-textarea {
        width: 100% !important;
        min-height: 80px !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        padding: 0.75rem !important;
        color: #ffffff !important;
        font-size: 0.9rem !important;
        line-height: 1.5 !important;
        resize: vertical !important;
        transition: border-color 0.2s !important;
    }

    .vw-scene-textarea:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
    }

    .vw-scene-textarea::placeholder {
        color: rgba(255, 255, 255, 0.3) !important;
    }

    .vw-music-only-notice {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        padding: 0.75rem !important;
        background: rgba(251, 191, 36, 0.1) !important;
        border: 1px solid rgba(251, 191, 36, 0.2) !important;
        border-radius: 0.5rem !important;
        color: #fbbf24 !important;
        font-size: 0.85rem !important;
    }

    .vw-scene-controls-row {
        display: flex !important;
        align-items: flex-end !important;
        gap: 1rem !important;
        margin-top: 1.25rem !important;
        flex-wrap: wrap !important;
    }

    .vw-scene-control-group {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.375rem !important;
    }

    .vw-scene-control-label {
        font-size: 0.75rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
    }

    .vw-scene-duration-input {
        width: 80px !important;
        padding: 0.5rem 0.75rem !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.375rem !important;
        color: #ffffff !important;
        font-size: 0.9rem !important;
        text-align: center !important;
    }

    .vw-scene-duration-input:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
    }

    .vw-scene-transition-select {
        padding: 0.5rem 0.75rem !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.375rem !important;
        color: #ffffff !important;
        font-size: 0.9rem !important;
        cursor: pointer !important;
        min-width: 120px !important;
    }

    .vw-scene-transition-select:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
    }

    .vw-scene-transition-select option {
        background: #1a1a2e !important;
        color: #ffffff !important;
    }

    .vw-scene-actions {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        margin-left: auto !important;
    }

    .vw-scene-action-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 32px !important;
        height: 32px !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.375rem !important;
        color: rgba(255, 255, 255, 0.6) !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
        font-size: 0.9rem !important;
    }

    .vw-scene-action-btn:hover:not(:disabled) {
        border-color: rgba(139, 92, 246, 0.4) !important;
        color: #c4b5fd !important;
        background: rgba(139, 92, 246, 0.1) !important;
    }

    .vw-scene-action-btn.danger:hover:not(:disabled) {
        border-color: rgba(239, 68, 68, 0.4) !important;
        color: #fca5a5 !important;
        background: rgba(239, 68, 68, 0.1) !important;
    }

    .vw-scene-action-btn:disabled {
        opacity: 0.3 !important;
        cursor: not-allowed !important;
    }

    .vw-scene-regenerate-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        padding: 0.5rem 0.875rem !important;
        background: rgba(139, 92, 246, 0.2) !important;
        border: 1px solid rgba(139, 92, 246, 0.3) !important;
        border-radius: 0.375rem !important;
        color: #c4b5fd !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }

    .vw-scene-regenerate-btn:hover:not(:disabled) {
        background: rgba(139, 92, 246, 0.3) !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
    }

    .vw-scene-regenerate-btn:disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
    }

    /* Add Scene Button */
    .vw-add-scene-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        width: 100% !important;
        padding: 1rem !important;
        background: transparent !important;
        border: 2px dashed rgba(139, 92, 246, 0.3) !important;
        border-radius: 0.75rem !important;
        color: rgba(139, 92, 246, 0.7) !important;
        font-size: 0.9rem !important;
        font-weight: 500 !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
        margin-top: 1rem !important;
    }

    .vw-add-scene-btn:hover {
        border-color: rgba(139, 92, 246, 0.5) !important;
        background: rgba(139, 92, 246, 0.05) !important;
        color: #c4b5fd !important;
    }

    /* Music Only Toggle */
    .vw-music-only-toggle {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        cursor: pointer !important;
        user-select: none !important;
    }

    .vw-music-only-checkbox {
        width: 18px !important;
        height: 18px !important;
        accent-color: #8b5cf6 !important;
        cursor: pointer !important;
    }

    .vw-music-only-label {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.6) !important;
    }
</style>

<div class="vw-script-step">
    <div class="vw-script-card">
        {{-- Error Message --}}
        @if($error)
            <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; color: #fca5a5;">
                <span style="margin-right: 0.5rem;">⚠️</span>
                {{ $error }}
            </div>
        @endif

        {{-- Header --}}
        <div class="vw-script-header">
            <div class="vw-script-icon">✨</div>
            <div>
                <h2 class="vw-script-title">{{ __('Generate Your Script') }}</h2>
                <p class="vw-script-subtitle">{{ __('AI will create a professional cinematic script based on your settings') }}</p>
            </div>
        </div>

        {{-- Direct Concept Summary --}}
        @php
            $productionTypes = config('appvideowizard.production_types', []);
            $typeName = $productionTypes[$productionType]['name'] ?? ucfirst($productionType ?? 'movie');
            $subtypeName = '';
            if ($productionSubtype && isset($productionTypes[$productionType]['subTypes'][$productionSubtype])) {
                $subtypeName = $productionTypes[$productionType]['subTypes'][$productionSubtype]['name'];
            }
            $durationMin = floor($targetDuration / 60);
            $durationSec = $targetDuration % 60;
            $durationText = $durationMin > 0 ? ($durationMin . 'm' . ($durationSec > 0 ? ' ' . $durationSec . 's' : '')) : ($durationSec . 's');
            $conceptText = $concept['refinedConcept'] ?: $concept['rawInput'];
            $charCount = strlen($conceptText);
        @endphp

        <div class="vw-direct-concept-card">
            <div class="vw-direct-concept-badges">
                <span class="vw-type-badge passthrough">✓ {{ __('Pass-through') }}</span>
                @if($productionType)
                    <span class="vw-type-badge production">🎬 {{ $typeName }}</span>
                @endif
                @if($subtypeName)
                    <span class="vw-type-badge subtype">{{ $subtypeName }}</span>
                @endif
            </div>

            <div class="vw-direct-concept-label">
                📝 {{ __('YOUR DIRECT CONCEPT') }}
            </div>

            <h3 class="vw-direct-concept-title">{{ Str::limit($concept['logline'] ?? $typeName, 50) ?: 'A' }}</h3>

            <p class="vw-direct-concept-text">{{ $conceptText }}</p>

            <div class="vw-concept-meta">
                <div class="vw-concept-meta-left">
                    @php
                        $safeSuggestedMood = is_string($concept['suggestedMood'] ?? null) ? $concept['suggestedMood'] : (is_array($concept['suggestedMood'] ?? null) ? implode(', ', $concept['suggestedMood']) : '');
                        $safeSuggestedTone = is_string($concept['suggestedTone'] ?? null) ? $concept['suggestedTone'] : (is_array($concept['suggestedTone'] ?? null) ? implode(', ', $concept['suggestedTone']) : '');
                    @endphp
                    @if(!empty($safeSuggestedMood))
                        <div class="vw-concept-meta-item">
                            <span>{{ __('Mood:') }}</span>
                            <span>{{ $safeSuggestedMood }}</span>
                        </div>
                    @endif
                    @if(!empty($safeSuggestedTone))
                        <div class="vw-concept-meta-item">
                            <span>{{ __('Tone:') }}</span>
                            <span>{{ $safeSuggestedTone }}</span>
                        </div>
                    @endif
                    <div class="vw-concept-meta-item duration">
                        <span>{{ __('Duration:') }}</span>
                        <span>{{ $durationText }}</span>
                    </div>
                </div>
                <div class="vw-char-count">{{ $charCount }} {{ __('chars') }}</div>
            </div>
        </div>

        {{-- Script Tone Selector --}}
        <div class="vw-selector-section">
            <div class="vw-selector-label">{{ __('Script Tone') }}</div>
            <div class="vw-selector-grid">
                <button type="button"
                        class="vw-selector-btn {{ $scriptTone === 'engaging' ? 'selected' : '' }}"
                        wire:click="$set('scriptTone', 'engaging')">
                    <span class="vw-selector-btn-title">{{ __('Engaging') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $scriptTone === 'professional' ? 'selected' : '' }}"
                        wire:click="$set('scriptTone', 'professional')">
                    <span class="vw-selector-btn-title">{{ __('Professional') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $scriptTone === 'casual' ? 'selected' : '' }}"
                        wire:click="$set('scriptTone', 'casual')">
                    <span class="vw-selector-btn-title">{{ __('Casual') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $scriptTone === 'inspirational' ? 'selected' : '' }}"
                        wire:click="$set('scriptTone', 'inspirational')">
                    <span class="vw-selector-btn-title">{{ __('Inspirational') }}</span>
                </button>
            </div>
        </div>

        {{-- Content Depth Selector --}}
        <div class="vw-selector-section">
            <div class="vw-selector-label">
                {{ __('Content Depth') }}
                <span class="vw-selector-sublabel">— {{ __('How much detail in the narration') }}</span>
            </div>
            <div class="vw-selector-grid">
                <button type="button"
                        class="vw-selector-btn {{ $contentDepth === 'quick' ? 'selected' : '' }}"
                        wire:click="$set('contentDepth', 'quick')">
                    <span class="vw-selector-btn-title">⚡ {{ __('Quick') }}</span>
                    <span class="vw-selector-btn-subtitle">{{ __('Key points only') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $contentDepth === 'standard' ? 'selected' : '' }}"
                        wire:click="$set('contentDepth', 'standard')">
                    <span class="vw-selector-btn-title">📝 {{ __('Standard') }}</span>
                    <span class="vw-selector-btn-subtitle">{{ __('Balanced content') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $contentDepth === 'detailed' ? 'selected' : '' }}"
                        wire:click="$set('contentDepth', 'detailed')">
                    <span class="vw-selector-btn-title">📚 {{ __('Detailed') }}</span>
                    <span class="vw-selector-btn-subtitle">{{ __('Examples & stats') }}</span>
                </button>
                <button type="button"
                        class="vw-selector-btn {{ $contentDepth === 'deep' ? 'selected' : '' }}"
                        wire:click="$set('contentDepth', 'deep')">
                    <span class="vw-selector-btn-title">🔬 {{ __('Deep Dive') }}</span>
                    <span class="vw-selector-btn-subtitle">{{ __('Full analysis') }}</span>
                </button>
            </div>
        </div>

        {{-- Additional Instructions --}}
        <div class="vw-selector-section">
            <div class="vw-selector-label">
                {{ __('Additional Instructions') }}
                <span class="vw-selector-sublabel">({{ __('optional') }})</span>
            </div>
            <textarea wire:model.blur="additionalInstructions"
                      class="vw-instructions-textarea"
                      placeholder="{{ __('Any specific requirements? e.g., Include a personal story, mention specific products, focus on beginners...') }}"></textarea>
        </div>

        {{-- Generate Button --}}
        <button class="vw-generate-script-btn"
                wire:click="generateScript"
                wire:loading.attr="disabled"
                wire:target="generateScript">
            <span wire:loading.remove wire:target="generateScript">🚀 {{ __('Generate Script with AI') }}</span>
            <span wire:loading wire:target="generateScript">
                <span class="vw-loading-inner">
                    <svg style="width: 18px; height: 18px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                    </svg>
                    {{ __('Generating...') }}
                </span>
            </span>
        </button>

        <p class="vw-cost-estimate">{{ __('Estimated cost: ~5 tokens • Powered by') }} {{ get_option('ai_platform', 'GPT-4o') }}</p>
    </div>

    {{-- Script Results (shown after generation) --}}
    @if(!empty($script['scenes']) && count($script['scenes']) > 0)
        @php
            // Script data is sanitized by VideoWizard::sanitizeScriptData()
            // All fields are guaranteed to be properly typed (strings, numbers, arrays)
            $sceneCount = count($script['scenes']);
            $totalDuration = (int)($script['totalDuration'] ?? array_sum(array_column($script['scenes'], 'duration')));
            $totalNarrationWords = array_sum(array_map(fn($s) => str_word_count($s['narration'] ?? ''), $script['scenes']));

            $avgDuration = $sceneCount > 0 ? round($totalDuration / $sceneCount) : 0;
            $estimatedNarrationTime = round(($totalNarrationWords / 150) * 60);
            $totalMin = floor($totalDuration / 60);
            $totalSec = $totalDuration % 60;
            $totalTimeText = $totalMin > 0 ? ($totalMin . 'm ' . $totalSec . 's') : ($totalSec . 's');

            // Determine pacing
            if ($avgDuration <= 10) {
                $pacingText = __('Fast-paced');
                $pacingIcon = '⚡';
            } elseif ($avgDuration <= 20) {
                $pacingText = __('Balanced');
                $pacingIcon = '⚖️';
            } else {
                $pacingText = __('Cinematic');
                $pacingIcon = '🎬';
            }

            // Safety net: ensure script fields are strings even if sanitization didn't run
            $safeScriptTitle = is_string($script['title'] ?? null) ? $script['title'] : __('Your Script');
            $safeHook = is_string($script['hook'] ?? null) ? $script['hook'] : '';
            $safeCta = is_string($script['cta'] ?? null) ? $script['cta'] : '';
        @endphp

        <div class="vw-script-card vw-script-results">
            <div class="vw-script-header" style="margin-bottom: 1rem;">
                <div>
                    <h3 class="vw-script-title">{{ $safeScriptTitle }}</h3>
                    <p class="vw-script-subtitle">
                        {{ $sceneCount }} {{ __('scenes') }} •
                        {{ $totalDuration }}s {{ __('total') }}
                    </p>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button class="vw-full-script-btn"
                            x-data
                            @click="$dispatch('open-full-script')">
                        📄 {{ __('Full Script') }}
                    </button>
                    <button style="background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.3); color: #c4b5fd; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; font-size: 0.85rem;"
                            wire:click="$dispatch('generate-script')"
                            wire:loading.attr="disabled">
                        🔄 {{ __('Regenerate') }}
                    </button>
                </div>
            </div>

            {{-- Script Stats Bar --}}
            <div class="vw-script-stats-bar">
                <div class="vw-script-stats-left">
                    <div class="vw-script-stat">
                        <span class="vw-script-stat-label">{{ __('VISUAL TIME') }}</span>
                        <span class="vw-script-stat-value">{{ $totalTimeText }}</span>
                    </div>
                    <div class="vw-script-stat">
                        <span class="vw-script-stat-label">{{ __('NARRATION') }}</span>
                        <span class="vw-script-stat-value">~{{ $estimatedNarrationTime }}s</span>
                    </div>
                    <div class="vw-script-stat">
                        <span class="vw-script-stat-label">{{ __('PER SCENE') }}</span>
                        <span class="vw-script-stat-value highlight">~{{ $avgDuration }}s</span>
                    </div>
                </div>
                <div class="vw-pacing-indicator">
                    {{ $pacingIcon }} {{ $pacingText }}
                </div>
            </div>

            {{-- Voice & Dialogue Status Panel --}}
            <div class="vw-voice-status-panel">
                <div class="vw-voice-status-header">
                    <div class="vw-voice-status-title">
                        🎙️ {{ __('Voice & Dialogue Status') }}
                    </div>
                    @if(($voiceStatus['pendingVoices'] ?? 0) > 0)
                        <div class="vw-voice-pending-badge">
                            {{ $voiceStatus['pendingVoices'] }} {{ __('voice pending') }}
                        </div>
                    @endif
                </div>
                <div class="vw-voice-status-grid">
                    <div class="vw-voice-stat-card">
                        <div class="vw-voice-stat-value">{{ $voiceStatus['dialogueLines'] ?? 0 }}</div>
                        <div class="vw-voice-stat-label">{{ __('Dialogue Lines') }}</div>
                    </div>
                    <div class="vw-voice-stat-card">
                        <div class="vw-voice-stat-value">{{ $voiceStatus['speakers'] ?? 0 }}</div>
                        <div class="vw-voice-stat-label">{{ __('Speakers') }}</div>
                    </div>
                    <div class="vw-voice-stat-card">
                        <div class="vw-voice-stat-value">{{ $voiceStatus['voicesMapped'] ?? 0 }}</div>
                        <div class="vw-voice-stat-label">{{ __('Voices Mapped') }}</div>
                    </div>
                    <div class="vw-voice-stat-card">
                        <div class="vw-voice-stat-value">{{ $voiceStatus['scenesWithVoiceover'] ?? 0 }}</div>
                        <div class="vw-voice-stat-label">{{ __('Scenes w/ Voice') }}</div>
                    </div>
                </div>
            </div>

            {{-- Hook section - $safeHook is defined above and guaranteed to be string --}}
            @if($safeHook)
                <div style="background: rgba(236, 72, 153, 0.1); border: 1px solid rgba(236, 72, 153, 0.3); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                    <span style="color: #f472b6; font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">{{ __('Hook') }}</span>
                    <p style="color: rgba(255,255,255,0.8); margin-top: 0.5rem;">{{ $safeHook }}</p>
                </div>
            @endif

            @php
                $transitions = config('appvideowizard.transitions', [
                    'cut' => 'Cut',
                    'dissolve' => 'Dissolve',
                    'fade' => 'Fade',
                    'wipe' => 'Wipe',
                ]);
            @endphp

            @foreach($script['scenes'] as $index => $scene)
                @php
                    // Safety net: ensure all fields are strings even if sanitization didn't run
                    // (e.g., old projects loaded before fix was deployed, or AI returned unexpected types)

                    // Helper function to extract string from potentially nested arrays
                    $extractString = function($value, $default = '') use (&$extractString) {
                        if (is_string($value)) return $value;
                        if (is_numeric($value)) return (string)$value;
                        if (is_array($value)) {
                            foreach ($value as $item) {
                                $result = $extractString($item, '');
                                if ($result !== '') return $result;
                            }
                        }
                        return $default;
                    };

                    $safeTitle = $extractString($scene['title'] ?? null, __('Scene') . ' ' . ($index + 1));
                    $safeNarration = $extractString($scene['narration'] ?? null, '');
                    $safeVisualPrompt = $extractString($scene['visualPrompt'] ?? null, '');
                    $safeVisualDescription = $extractString($scene['visualDescription'] ?? null, '');
                    $safeMood = $extractString($scene['mood'] ?? null, '');
                    $safeTransition = $extractString($scene['transition'] ?? null, 'cut');
                    $safeDuration = is_numeric($scene['duration'] ?? null) ? (int)$scene['duration'] : 15;

                    // Use visualPrompt first, fall back to visualDescription
                    $displayVisualPrompt = $safeVisualPrompt ?: $safeVisualDescription;

                    // Check music only status
                    $isMusicOnly = isset($scene['voiceover']['enabled']) && !$scene['voiceover']['enabled'];
                    $sceneId = $extractString($scene['id'] ?? null, 'scene_' . $index);

                    // Get transition label
                    $transitionLabel = $transitions[$safeTransition] ?? 'Cut';
                @endphp
                <div class="vw-advanced-scene-card"
                     x-data="{ expanded: false }"
                     :class="{ 'expanded': expanded }">
                    {{-- Scene Header (Clickable to expand) --}}
                    <div class="vw-scene-card-header" @click="expanded = !expanded">
                        <div class="vw-scene-card-header-left">
                            <span class="vw-scene-expand-icon">▼</span>
                            <span class="vw-scene-number">{{ $index + 1 }}</span>
                            @if($isMusicOnly)
                                <span class="vw-scene-music-badge">🎵 {{ __('Music only') }}</span>
                            @endif
                            <span class="vw-scene-title">{{ $safeTitle }}</span>
                        </div>
                        <div class="vw-scene-meta-badges">
                            <span class="vw-scene-meta-badge">{{ $safeDuration }}s</span>
                            <span class="vw-scene-meta-badge">{{ $transitionLabel }}</span>
                            @if($safeMood)
                                <span class="vw-scene-meta-badge">{{ ucfirst($safeMood) }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Scene Body (Expandable) --}}
                    <div class="vw-scene-card-body" x-show="expanded" x-collapse>
                        {{-- Visual Prompt Section --}}
                        <div class="vw-scene-section">
                            <div class="vw-scene-section-header">
                                <span class="vw-scene-section-label">🖼️ {{ __('Visual Prompt') }}</span>
                                <button class="vw-scene-write-btn"
                                        wire:click="generateVisualPrompt({{ $index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="generateVisualPrompt">
                                    <span wire:loading.remove wire:target="generateVisualPrompt({{ $index }})">✨ {{ __('Write for SR') }}</span>
                                    <span wire:loading wire:target="generateVisualPrompt({{ $index }})">...</span>
                                </button>
                            </div>
                            <textarea class="vw-scene-textarea"
                                      placeholder="{{ __('Describe the visual scene for AI video generation. Include camera movements, lighting, subject...') }}"
                                      wire:blur="updateSceneVisualPrompt({{ $index }}, $event.target.value)">{{ $displayVisualPrompt }}</textarea>
                        </div>

                        {{-- Narration/Voiceover Section --}}
                        <div class="vw-scene-section">
                            <div class="vw-scene-section-header">
                                <span class="vw-scene-section-label">🎙️ {{ __('Voiceover') }}</span>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <label class="vw-music-only-toggle">
                                        <input type="checkbox"
                                               class="vw-music-only-checkbox"
                                               {{ $isMusicOnly ? 'checked' : '' }}
                                               wire:click="toggleSceneMusicOnly({{ $index }})">
                                        <span class="vw-music-only-label">{{ __('Music only') }}</span>
                                    </label>
                                    @if(!$isMusicOnly)
                                        <button class="vw-scene-write-btn"
                                                wire:click="generateVoiceoverText({{ $index }})"
                                                wire:loading.attr="disabled"
                                                wire:target="generateVoiceoverText">
                                            <span wire:loading.remove wire:target="generateVoiceoverText({{ $index }})">✨ {{ __('Write VO') }}</span>
                                            <span wire:loading wire:target="generateVoiceoverText({{ $index }})">...</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @if($isMusicOnly)
                                <div class="vw-music-only-notice">
                                    🎵 {{ __('Music/ambient only - no voiceover for this scene') }}
                                </div>
                            @else
                                <textarea class="vw-scene-textarea"
                                          placeholder="{{ __('Voiceover text for this scene...') }}"
                                          wire:blur="updateSceneNarration({{ $index }}, $event.target.value)">{{ $safeNarration }}</textarea>
                            @endif
                        </div>

                        {{-- Controls Row --}}
                        <div class="vw-scene-controls-row">
                            <div class="vw-scene-control-group">
                                <span class="vw-scene-control-label">⏱️ {{ __('Duration (seconds)') }}</span>
                                <input type="number"
                                       class="vw-scene-duration-input"
                                       value="{{ $safeDuration }}"
                                       min="1"
                                       max="300"
                                       wire:blur="updateSceneDuration({{ $index }}, $event.target.value)">
                            </div>
                            <div class="vw-scene-control-group">
                                <span class="vw-scene-control-label">📋 {{ __('Transition') }}</span>
                                <select class="vw-scene-transition-select"
                                        wire:change="updateSceneTransition({{ $index }}, $event.target.value)">
                                    @foreach($transitions as $key => $label)
                                        <option value="{{ $key }}" {{ $safeTransition === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vw-scene-actions">
                                <button class="vw-scene-regenerate-btn"
                                        wire:click="regenerateScene({{ $index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="regenerateScene">
                                    <span wire:loading.remove wire:target="regenerateScene({{ $index }})">🔄 {{ __('Regenerate') }}</span>
                                    <span wire:loading wire:target="regenerateScene({{ $index }})">...</span>
                                </button>
                                <button class="vw-scene-action-btn"
                                        wire:click="reorderScene({{ $index }}, 'up')"
                                        @if($index === 0) disabled @endif
                                        title="{{ __('Move Up') }}">↑</button>
                                <button class="vw-scene-action-btn"
                                        wire:click="reorderScene({{ $index }}, 'down')"
                                        @if($index === count($script['scenes']) - 1) disabled @endif
                                        title="{{ __('Move Down') }}">↓</button>
                                <button class="vw-scene-action-btn danger"
                                        wire:click="deleteScene({{ $index }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this scene?') }}"
                                        title="{{ __('Delete') }}">🗑️</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Add Scene Button --}}
            <button class="vw-add-scene-btn" wire:click="addScene">
                ➕ {{ __('Add Scene') }}
            </button>

            {{-- CTA section - $safeCta is defined above and guaranteed to be string --}}
            @if($safeCta)
                <div style="background: rgba(6, 182, 212, 0.1); border: 1px solid rgba(6, 182, 212, 0.3); border-radius: 0.5rem; padding: 1rem; margin-top: 1rem;">
                    <span style="color: #22d3ee; font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">{{ __('Call to Action') }}</span>
                    <p style="color: rgba(255,255,255,0.8); margin-top: 0.5rem;">{{ $safeCta }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Full Script Modal --}}
    @if(!empty($script['scenes']) && count($script['scenes']) > 0)
        <div x-data="{ showFullScript: false }"
             @open-full-script.window="showFullScript = true"
             @keydown.escape.window="showFullScript = false">
            <template x-teleport="body">
                <div x-show="showFullScript"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="vw-full-script-modal"
                     style="display: none;">
                    <div class="vw-full-script-overlay" @click="showFullScript = false"></div>
                    <div class="vw-full-script-content"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         @click.stop>
                        {{-- Modal uses $safeScriptTitle, $safeHook, $safeCta from parent scope --}}
                        <div class="vw-full-script-header">
                            <div>
                                <h3 class="vw-script-title">📄 {{ __('Full Script') }}</h3>
                                <p class="vw-script-subtitle">{{ $safeScriptTitle }}</p>
                            </div>
                            <button class="vw-full-script-close" @click="showFullScript = false">✕</button>
                        </div>

                        <div class="vw-full-script-text">
                            @if($safeHook)
                                <strong style="color: #f472b6;">{{ __('HOOK:') }}</strong>
                                <br>{{ $safeHook }}
                                <div class="vw-full-script-scene-divider">— — —</div>
                            @endif

                            @foreach($script['scenes'] as $index => $scene)
                                @php
                                    // Safety net for modal scene display
                                    $modalSceneTitle = is_string($scene['title'] ?? null) ? $scene['title'] : '';
                                    $modalSceneNarration = is_string($scene['narration'] ?? null) ? $scene['narration'] : '';
                                @endphp
                                <strong style="color: #c4b5fd;">{{ __('SCENE') }} {{ $index + 1 }}: {{ $modalSceneTitle }}</strong>
                                <br>{{ $modalSceneNarration }}

                                @if(!$loop->last)
                                    <div class="vw-full-script-scene-divider">— — —</div>
                                @endif
                            @endforeach

                            @if($safeCta)
                                <div class="vw-full-script-scene-divider">— — —</div>
                                <strong style="color: #22d3ee;">{{ __('CALL TO ACTION:') }}</strong>
                                <br>{{ $safeCta }}
                            @endif
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
