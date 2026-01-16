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

    /* Pulse Animation for active batches */
    @keyframes vw-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
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

    /* Full Script Modal - hidden by default */
    .vw-full-script-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 100 !important;
        display: none !important; /* Hidden by default */
        align-items: center !important;
        justify-content: center !important;
        padding: 2rem !important;
    }

    /* Only show when .is-open class is added by Alpine */
    .vw-full-script-modal.is-open {
        display: flex !important;
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

    /* Narrative Structure Intelligence Styles */
    .vw-narrative-section {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(236, 72, 153, 0.05) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 0.75rem !important;
        padding: 1.25rem !important;
        margin-bottom: 1.5rem !important;
    }

    .vw-narrative-clear-btn {
        background: rgba(239, 68, 68, 0.2) !important;
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
        color: #fca5a5 !important;
        padding: 0.375rem 0.75rem !important;
        border-radius: 0.375rem !important;
        font-size: 0.75rem !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }

    .vw-narrative-clear-btn:hover {
        background: rgba(239, 68, 68, 0.3) !important;
        border-color: rgba(239, 68, 68, 0.5) !important;
    }

    .vw-narrative-presets-row {
        margin-top: 0.75rem !important;
    }

    .vw-narrative-preset-label {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.6) !important;
        margin-bottom: 0.5rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }

    .vw-narrative-presets-grid {
        display: grid !important;
        grid-template-columns: repeat(6, 1fr) !important;
        gap: 0.5rem !important;
    }

    @media (max-width: 1024px) {
        .vw-narrative-presets-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
    }

    @media (max-width: 640px) {
        .vw-narrative-presets-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    .vw-narrative-preset-btn {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0.625rem 0.5rem !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.5rem !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
        text-align: center !important;
        min-height: 60px !important;
    }

    .vw-narrative-preset-btn:hover {
        border-color: rgba(139, 92, 246, 0.4) !important;
        background: rgba(139, 92, 246, 0.1) !important;
    }

    .vw-narrative-preset-btn.selected {
        border-color: rgba(139, 92, 246, 0.6) !important;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.25) 0%, rgba(236, 72, 153, 0.15) 100%) !important;
        box-shadow: 0 0 10px rgba(139, 92, 246, 0.3) !important;
    }

    .vw-preset-icon {
        font-size: 1.25rem !important;
        margin-bottom: 0.25rem !important;
    }

    .vw-preset-name {
        font-size: 0.7rem !important;
        color: rgba(255, 255, 255, 0.7) !important;
        font-weight: 500 !important;
        line-height: 1.2 !important;
    }

    .vw-narrative-preset-btn.selected .vw-preset-name {
        color: #ffffff !important;
    }

    /* Cascading preset organization styles */
    .vw-format-toggle {
        display: inline-flex !important;
        margin-left: 0.75rem !important;
        background: rgba(0, 0, 0, 0.3) !important;
        border-radius: 0.375rem !important;
        padding: 0.125rem !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .vw-format-btn {
        padding: 0.25rem 0.75rem !important;
        font-size: 0.7rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.03em !important;
        border: none !important;
        background: transparent !important;
        color: rgba(255, 255, 255, 0.5) !important;
        cursor: pointer !important;
        border-radius: 0.25rem !important;
        transition: all 0.2s !important;
    }

    .vw-format-btn:hover {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .vw-format-btn.active {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.4) 0%, rgba(168, 85, 247, 0.3) 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 0 8px rgba(139, 92, 246, 0.3) !important;
    }

    /* Legacy badge styles - kept for compatibility */
    .vw-format-badge {
        display: inline-block !important;
        padding: 0.125rem 0.5rem !important;
        border-radius: 0.25rem !important;
        font-size: 0.65rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        margin-left: 0.5rem !important;
    }

    .vw-format-badge.short {
        background: rgba(59, 130, 246, 0.2) !important;
        color: rgba(96, 165, 250, 0.9) !important;
        border: 1px solid rgba(59, 130, 246, 0.3) !important;
    }

    .vw-format-badge.feature {
        background: rgba(168, 85, 247, 0.2) !important;
        color: rgba(192, 132, 252, 0.9) !important;
        border: 1px solid rgba(168, 85, 247, 0.3) !important;
    }

    .vw-preset-context-hint {
        font-size: 0.75rem !important;
        color: rgba(16, 185, 129, 0.8) !important;
        font-weight: 400 !important;
        text-transform: none !important;
        letter-spacing: normal !important;
    }

    .vw-narrative-preset-btn.recommended {
        border-color: rgba(16, 185, 129, 0.3) !important;
        background: rgba(16, 185, 129, 0.08) !important;
        position: relative !important;
    }

    .vw-narrative-preset-btn.recommended:hover {
        border-color: rgba(16, 185, 129, 0.5) !important;
        background: rgba(16, 185, 129, 0.15) !important;
    }

    .vw-narrative-preset-btn.recommended.selected {
        border-color: rgba(16, 185, 129, 0.7) !important;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(139, 92, 246, 0.15) 100%) !important;
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.3) !important;
    }

    .vw-preset-recommended-badge {
        position: absolute !important;
        top: 2px !important;
        right: 4px !important;
        font-size: 0.6rem !important;
        color: rgba(16, 185, 129, 0.9) !important;
        font-weight: 700 !important;
    }

    .vw-narrative-preset-btn.compatible {
        opacity: 0.85 !important;
    }

    .vw-presets-other-details {
        margin-top: 0.5rem !important;
    }

    .vw-presets-other-toggle {
        font-size: 0.75rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        cursor: pointer !important;
        padding: 0.25rem 0 !important;
        user-select: none !important;
    }

    .vw-presets-other-toggle:hover {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .vw-presets-other .vw-narrative-preset-btn {
        opacity: 0.7 !important;
    }

    .vw-presets-other .vw-narrative-preset-btn:hover {
        opacity: 1 !important;
    }

    .vw-narrative-tip {
        display: flex !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
        margin-top: 0.75rem !important;
        padding: 0.75rem !important;
        background: rgba(139, 92, 246, 0.1) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 0.5rem !important;
        font-size: 0.8rem !important;
        color: #c4b5fd !important;
        line-height: 1.5 !important;
    }

    .vw-narrative-tip-icon {
        flex-shrink: 0 !important;
    }

    .vw-narrative-advanced-toggle {
        margin-top: 1rem !important;
    }

    .vw-advanced-toggle-btn {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        background: transparent !important;
        border: none !important;
        color: rgba(255, 255, 255, 0.6) !important;
        font-size: 0.85rem !important;
        cursor: pointer !important;
        padding: 0.5rem 0 !important;
        transition: color 0.2s !important;
    }

    .vw-advanced-toggle-btn:hover {
        color: #c4b5fd !important;
    }

    .vw-advanced-active-badge {
        background: rgba(34, 197, 94, 0.2) !important;
        color: #22c55e !important;
        padding: 0.125rem 0.5rem !important;
        border-radius: 0.25rem !important;
        font-size: 0.7rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }

    .vw-narrative-advanced-panel {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1rem !important;
        margin-top: 1rem !important;
        padding-top: 1rem !important;
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    @media (max-width: 1024px) {
        .vw-narrative-advanced-panel {
            grid-template-columns: 1fr !important;
        }
    }

    .vw-narrative-option-group {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.375rem !important;
    }

    .vw-narrative-option-label {
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: #ffffff !important;
    }

    .vw-option-sublabel {
        font-size: 0.75rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        font-weight: 400 !important;
        margin-left: 0.25rem !important;
    }

    .vw-narrative-select {
        width: 100% !important;
        padding: 0.625rem 0.75rem !important;
        background: rgba(0, 0, 0, 0.4) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        border-radius: 0.5rem !important;
        color: #ffffff !important;
        font-size: 0.85rem !important;
        cursor: pointer !important;
        transition: border-color 0.2s !important;
    }

    .vw-narrative-select:focus {
        outline: none !important;
        border-color: rgba(139, 92, 246, 0.5) !important;
    }

    .vw-narrative-select option {
        background: #1a1a2e !important;
        color: #ffffff !important;
    }

    .vw-option-description {
        font-size: 0.75rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        line-height: 1.4 !important;
        padding: 0.375rem !important;
        background: rgba(0, 0, 0, 0.2) !important;
        border-radius: 0.25rem !important;
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
            // Safety: ensure typeName is string
            if (is_array($typeName)) $typeName = reset($typeName) ?: 'Video';
            $typeName = is_string($typeName) ? $typeName : strval($typeName ?? 'Video');

            $subtypeName = '';
            if ($productionSubtype && isset($productionTypes[$productionType]['subTypes'][$productionSubtype])) {
                $subtypeName = $productionTypes[$productionType]['subTypes'][$productionSubtype]['name'];
            }
            // Safety: ensure subtypeName is string
            if (is_array($subtypeName)) $subtypeName = reset($subtypeName) ?: '';
            $subtypeName = is_string($subtypeName) ? $subtypeName : '';

            $durationMin = floor($targetDuration / 60);
            $durationSec = $targetDuration % 60;
            $durationText = $durationMin > 0 ? ($durationMin . 'm' . ($durationSec > 0 ? ' ' . $durationSec . 's' : '')) : ($durationSec . 's');

            // Safety: ensure conceptText is string
            $conceptText = $concept['refinedConcept'] ?? $concept['rawInput'] ?? '';
            if (is_array($conceptText)) $conceptText = reset($conceptText) ?: '';
            if (is_array($conceptText)) $conceptText = reset($conceptText) ?: '';
            $conceptText = is_string($conceptText) ? $conceptText : '';
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
                        // Safely extract mood - handle nested arrays
                        $rawSugMood = $concept['suggestedMood'] ?? null;
                        if (is_array($rawSugMood)) $rawSugMood = reset($rawSugMood) ?: null;
                        if (is_array($rawSugMood)) $rawSugMood = reset($rawSugMood) ?: null;
                        $safeSuggestedMood = is_string($rawSugMood) ? $rawSugMood : '';

                        // Safely extract tone - handle nested arrays
                        $rawSugTone = $concept['suggestedTone'] ?? null;
                        if (is_array($rawSugTone)) $rawSugTone = reset($rawSugTone) ?: null;
                        if (is_array($rawSugTone)) $rawSugTone = reset($rawSugTone) ?: null;
                        $safeSuggestedTone = is_string($rawSugTone) ? $rawSugTone : '';
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

        {{-- Story Bible Section (Phase 1: Bible-First Architecture) --}}
        <div class="vw-selector-section" style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <div class="vw-selector-label" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                    📖 {{ __('Story Bible') }}
                    @if($storyBible['status'] === 'ready')
                        <span style="background: rgba(16,185,129,0.2); color: #10b981; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600;">{{ __('READY') }}</span>
                    @elseif($storyBible['status'] === 'generating')
                        <span style="background: rgba(251,191,36,0.2); color: #fbbf24; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600;">{{ __('GENERATING...') }}</span>
                    @endif
                    <span class="vw-selector-sublabel">— {{ __('The DNA that constrains all generation') }}</span>
                </div>
            </div>

            @if($storyBible['status'] === 'ready')
                {{-- Story Bible Ready - Show Summary --}}
                <div style="background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(6,182,212,0.05)); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.75rem; padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                        <div>
                            @if(!empty($storyBible['title']))
                                <h4 style="margin: 0 0 0.25rem 0; color: white; font-size: 0.95rem; font-weight: 600;">{{ $storyBible['title'] }}</h4>
                            @endif
                            @if(!empty($storyBible['logline']))
                                <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 0.8rem; line-height: 1.5;">{{ Str::limit($storyBible['logline'], 150) }}</p>
                            @endif
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button"
                                    wire:click="openStoryBibleModal"
                                    style="padding: 0.35rem 0.75rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.375rem; color: #6ee7b7; font-size: 0.75rem; cursor: pointer; white-space: nowrap;">
                                ✏️ {{ __('Edit Bible') }}
                            </button>
                            @if(!empty($script['scenes']))
                            <button type="button"
                                    wire:click="openWritersRoom"
                                    style="padding: 0.35rem 0.75rem; background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(6,182,212,0.2)); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.375rem; color: #c4b5fd; font-size: 0.75rem; cursor: pointer; white-space: nowrap;">
                                ✍️ {{ __("Writer's Room") }}
                            </button>
                            @endif
                        </div>
                    </div>

                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <span style="font-size: 0.8rem;">👥</span>
                            <span style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">{{ count($storyBible['characters'] ?? []) }} {{ __('Characters') }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <span style="font-size: 0.8rem;">🏛️</span>
                            <span style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">{{ count($storyBible['locations'] ?? []) }} {{ __('Locations') }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <span style="font-size: 0.8rem;">🎭</span>
                            <span style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">{{ count($storyBible['acts'] ?? []) }} {{ __('Acts') }}</span>
                        </div>
                        @if(!empty($storyBible['tone']))
                            <div style="display: flex; align-items: center; gap: 0.35rem;">
                                <span style="font-size: 0.8rem;">🎨</span>
                                <span style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">{{ ucfirst($storyBible['tone']) }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Character Names --}}
                    @if(!empty($storyBible['characters']))
                        <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.1);">
                            <span style="color: rgba(255,255,255,0.5); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em;">{{ __('Characters') }}:</span>
                            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.35rem;">
                                @foreach($storyBible['characters'] as $char)
                                    <span style="padding: 0.2rem 0.5rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.375rem; color: #c4b5fd; font-size: 0.7rem;">
                                        {{ $char['name'] ?? __('Unnamed') }}
                                        <span style="color: rgba(255,255,255,0.4);">({{ ucfirst($char['role'] ?? 'supporting') }})</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- Story Bible Not Ready - Show Generate Button --}}
                <div style="background: linear-gradient(135deg, rgba(251,191,36,0.08), rgba(245,158,11,0.05)); border: 1px solid rgba(251,191,36,0.25); border-radius: 0.75rem; padding: 1rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 0.8rem; line-height: 1.5;">
                                {{ __('Generate a Story Bible first to establish characters, locations, and visual style. This ensures consistency across all generated content.') }}
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button"
                                    wire:click="generateStoryBible"
                                    wire:loading.attr="disabled"
                                    wire:target="generateStoryBible"
                                    @if($isGeneratingStoryBible) disabled @endif
                                    style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #f59e0b, #ec4899); border: none; border-radius: 0.5rem; color: white; font-weight: 600; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; white-space: nowrap;">
                                <span wire:loading.remove wire:target="generateStoryBible">✨ {{ __('Generate Story Bible') }}</span>
                                <span wire:loading wire:target="generateStoryBible" style="display: flex; align-items: center; gap: 0.35rem;">
                                    <div style="width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                                    {{ __('Generating...') }}
                                </span>
                            </button>
                            <button type="button"
                                    wire:click="openStoryBibleModal"
                                    style="padding: 0.5rem 0.75rem; background: transparent; border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: rgba(255,255,255,0.6); font-size: 0.8rem; cursor: pointer;">
                                {{ __('Manual') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Optional Skip Notice --}}
                <p style="margin: 0.5rem 0 0 0; color: rgba(255,255,255,0.4); font-size: 0.7rem; text-align: center;">
                    {{ __('You can skip this step, but script generation will use basic extraction instead.') }}
                </p>
            @endif
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

        {{-- Narrative Structure Intelligence Section --}}
        <div class="vw-selector-section vw-narrative-section">
            <div class="vw-selector-label" style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    🎬 {{ __('Narrative Structure') }}
                    <span class="vw-selector-sublabel">— {{ __('Hollywood-level storytelling') }}</span>
                </div>
                @if($narrativePreset || $storyArc || $tensionCurve || $emotionalJourney)
                    <button type="button"
                            class="vw-narrative-clear-btn"
                            wire:click="clearNarrativeSettings"
                            title="{{ __('Clear all narrative settings') }}">
                        ✕ {{ __('Clear') }}
                    </button>
                @endif
            </div>

            {{-- Narrative Presets - Platform-optimized storytelling --}}
            {{-- Now organized by production type selection from Step 1 and content format (short/feature) --}}
            @php
                $organizedPresets = $this->getOrganizedNarrativePresets();
                $hasProductionType = !empty($productionType);
                $contentFormat = $organizedPresets['contentFormat'] ?? 'short';
                $isFeature = $contentFormat === 'feature';
            @endphp
            <div class="vw-narrative-presets-row">
                <div class="vw-narrative-preset-label">
                    {{ __('Storytelling Formula') }}
                    {{-- Format Toggle - Click to switch between Short Form and Feature Film --}}
                    <div class="vw-format-toggle" title="{{ __('Click to switch format') }}">
                        <button type="button"
                                class="vw-format-btn {{ !$isFeature ? 'active' : '' }}"
                                wire:click="setContentFormat('short')">
                            {{ __('Short') }}
                        </button>
                        <button type="button"
                                class="vw-format-btn {{ $isFeature ? 'active' : '' }}"
                                wire:click="setContentFormat('feature')">
                            {{ __('Feature') }}
                        </button>
                    </div>
                    @if($hasProductionType && !empty($organizedPresets['recommended']))
                        <span class="vw-preset-context-hint">
                            — {{ __('Recommended for') }} {{ config('appvideowizard.production_types.' . $productionType . '.name', $productionType) }}
                            @if($productionSubtype)
                                ({{ config('appvideowizard.production_types.' . $productionType . '.subTypes.' . $productionSubtype . '.name', $productionSubtype) }})
                            @endif
                        </span>
                    @endif
                </div>

                {{-- Recommended Presets (shown prominently when production type is set) --}}
                @if(!empty($organizedPresets['recommended']))
                    <div class="vw-narrative-presets-grid vw-presets-recommended">
                        @foreach($organizedPresets['recommended'] as $key => $preset)
                            <button type="button"
                                    class="vw-narrative-preset-btn {{ $narrativePreset === $key ? 'selected' : '' }} recommended"
                                    wire:click="applyNarrativePreset('{{ $key }}')"
                                    title="{{ $preset['description'] ?? '' }}">
                                <span class="vw-preset-recommended-badge">✓</span>
                                <span class="vw-preset-icon">{{ $preset['icon'] ?? '📺' }}</span>
                                <span class="vw-preset-name">{{ $preset['name'] ?? $key }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Compatible Presets (shown but not highlighted) --}}
                @if(!empty($organizedPresets['compatible']))
                    <div class="vw-narrative-presets-grid vw-presets-compatible" style="margin-top: 0.5rem;">
                        @foreach($organizedPresets['compatible'] as $key => $preset)
                            <button type="button"
                                    class="vw-narrative-preset-btn {{ $narrativePreset === $key ? 'selected' : '' }} compatible"
                                    wire:click="applyNarrativePreset('{{ $key }}')"
                                    title="{{ $preset['description'] ?? '' }}">
                                <span class="vw-preset-icon">{{ $preset['icon'] ?? '📺' }}</span>
                                <span class="vw-preset-name">{{ $preset['name'] ?? $key }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Other Presets (collapsed when production type is set) --}}
                @if(!empty($organizedPresets['other']))
                    @if($hasProductionType)
                        <details class="vw-presets-other-details" style="margin-top: 0.5rem;">
                            <summary class="vw-presets-other-toggle">
                                {{ __('Show all presets') }} ({{ count($organizedPresets['other']) }} {{ __('more') }})
                            </summary>
                            <div class="vw-narrative-presets-grid vw-presets-other" style="margin-top: 0.5rem;">
                                @foreach($organizedPresets['other'] as $key => $preset)
                                    <button type="button"
                                            class="vw-narrative-preset-btn {{ $narrativePreset === $key ? 'selected' : '' }}"
                                            wire:click="applyNarrativePreset('{{ $key }}')"
                                            title="{{ $preset['description'] ?? '' }}">
                                        <span class="vw-preset-icon">{{ $preset['icon'] ?? '📺' }}</span>
                                        <span class="vw-preset-name">{{ $preset['name'] ?? $key }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </details>
                    @else
                        {{-- No production type selected, show all presets normally --}}
                        <div class="vw-narrative-presets-grid" style="margin-top: 0.5rem;">
                            @foreach($organizedPresets['other'] as $key => $preset)
                                <button type="button"
                                        class="vw-narrative-preset-btn {{ $narrativePreset === $key ? 'selected' : '' }}"
                                        wire:click="applyNarrativePreset('{{ $key }}')"
                                        title="{{ $preset['description'] ?? '' }}">
                                    <span class="vw-preset-icon">{{ $preset['icon'] ?? '📺' }}</span>
                                    <span class="vw-preset-name">{{ $preset['name'] ?? $key }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- Active Preset Tips --}}
            @php
                $allNarrativePresets = config('appvideowizard.narrative_presets', []);
            @endphp
            @if($narrativePreset && isset($allNarrativePresets[$narrativePreset]))
                <div class="vw-narrative-tip">
                    <span class="vw-narrative-tip-icon">💡</span>
                    <span>{{ $allNarrativePresets[$narrativePreset]['tips'] ?? $allNarrativePresets[$narrativePreset]['description'] }}</span>
                </div>
            @endif

            {{-- Advanced Options Toggle --}}
            <div class="vw-narrative-advanced-toggle">
                <button type="button"
                        class="vw-advanced-toggle-btn"
                        wire:click="$toggle('showNarrativeAdvanced')">
                    <span>{{ $showNarrativeAdvanced ? '▼' : '▶' }}</span>
                    {{ __('Advanced Narrative Options') }}
                    @if($storyArc || $tensionCurve || $emotionalJourney)
                        <span class="vw-advanced-active-badge">{{ __('Active') }}</span>
                    @endif
                </button>
            </div>

            {{-- Advanced Options Panel --}}
            @if($showNarrativeAdvanced)
                @php
                    $storyArcs = config('appvideowizard.story_arcs', []);
                    $tensionCurves = config('appvideowizard.tension_curves', []);
                    $emotionalJourneys = config('appvideowizard.emotional_journeys', []);
                @endphp
                <div class="vw-narrative-advanced-panel">
                    {{-- Story Arc Selection --}}
                    <div class="vw-narrative-option-group">
                        <label class="vw-narrative-option-label">
                            📐 {{ __('Story Arc') }}
                            <span class="vw-option-sublabel">{{ __('How the narrative unfolds') }}</span>
                        </label>
                        <select wire:model.live="storyArc" class="vw-narrative-select">
                            <option value="">{{ __('Auto (from preset)') }}</option>
                            @foreach($storyArcs as $key => $arc)
                                <option value="{{ $key }}">{{ $arc['icon'] ?? '' }} {{ $arc['name'] ?? $key }}</option>
                            @endforeach
                        </select>
                        @if($storyArc && isset($storyArcs[$storyArc]))
                            <div class="vw-option-description">{{ $storyArcs[$storyArc]['description'] ?? '' }}</div>
                        @endif
                    </div>

                    {{-- Tension Curve Selection --}}
                    <div class="vw-narrative-option-group">
                        <label class="vw-narrative-option-label">
                            📈 {{ __('Tension Curve') }}
                            <span class="vw-option-sublabel">{{ __('Pacing dynamics') }}</span>
                        </label>
                        <select wire:model.live="tensionCurve" class="vw-narrative-select">
                            <option value="">{{ __('Auto (from preset)') }}</option>
                            @foreach($tensionCurves as $key => $curve)
                                <option value="{{ $key }}">{{ $curve['icon'] ?? '' }} {{ $curve['name'] ?? $key }}</option>
                            @endforeach
                        </select>
                        @if($tensionCurve && isset($tensionCurves[$tensionCurve]))
                            <div class="vw-option-description">{{ $tensionCurves[$tensionCurve]['description'] ?? '' }}</div>
                        @endif
                    </div>

                    {{-- Emotional Journey Selection --}}
                    <div class="vw-narrative-option-group">
                        <label class="vw-narrative-option-label">
                            🎭 {{ __('Emotional Journey') }}
                            <span class="vw-option-sublabel">{{ __('Viewer feeling arc') }}</span>
                        </label>
                        <select wire:model.live="emotionalJourney" class="vw-narrative-select">
                            <option value="">{{ __('Auto (from preset)') }}</option>
                            @foreach($emotionalJourneys as $key => $journey)
                                <option value="{{ $key }}">{{ $journey['icon'] ?? '' }} {{ $journey['name'] ?? $key }}</option>
                            @endforeach
                        </select>
                        @if($emotionalJourney && isset($emotionalJourneys[$emotionalJourney]))
                            <div class="vw-option-description">{{ $emotionalJourneys[$emotionalJourney]['description'] ?? '' }}</div>
                        @endif
                    </div>
                </div>
            @endif
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

        {{-- Progressive Generation Panel --}}
        @if($scriptGeneration['status'] !== 'idle')
            <div style="background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1rem;">
                {{-- Header --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <h3 style="color: white; font-size: 1.1rem; font-weight: 600; margin: 0;">
                            🎬 {{ __('Script Generation') }}
                        </h3>
                        <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin: 0.25rem 0 0 0;">
                            {{ $targetDuration }}s {{ __('video') }} → {{ $scriptGeneration['targetSceneCount'] }} {{ __('scenes') }}
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span style="color: #8b5cf6; font-size: 1.5rem; font-weight: 700;">
                            {{ $scriptGeneration['generatedSceneCount'] }} / {{ $scriptGeneration['targetSceneCount'] }}
                        </span>
                        <p style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin: 0;">{{ __('scenes generated') }}</p>
                    </div>
                </div>

                {{-- Progress Bar --}}
                @php
                    $progress = $scriptGeneration['targetSceneCount'] > 0
                        ? ($scriptGeneration['generatedSceneCount'] / $scriptGeneration['targetSceneCount']) * 100
                        : 0;
                @endphp
                <div style="background: rgba(255,255,255,0.1); border-radius: 0.5rem; height: 12px; overflow: hidden; margin-bottom: 1rem;">
                    <div style="background: linear-gradient(90deg, #8b5cf6, #06b6d4); height: 100%; width: {{ $progress }}%; transition: width 0.5s ease;"></div>
                </div>

                {{-- Batch List --}}
                <div style="display: grid; gap: 0.5rem; margin-bottom: 1rem; max-height: 200px; overflow-y: auto;">
                    @foreach($scriptGeneration['batches'] as $batchIndex => $batch)
                        @php
                            $statusConfig = [
                                'complete' => ['bg' => 'rgba(16, 185, 129, 0.2)', 'border' => 'rgba(16, 185, 129, 0.4)', 'icon' => '✅', 'color' => '#10b981', 'pulse' => false],
                                'generating' => ['bg' => 'rgba(251, 191, 36, 0.2)', 'border' => 'rgba(251, 191, 36, 0.4)', 'icon' => '⏳', 'color' => '#fbbf24', 'pulse' => true],
                                'retrying' => ['bg' => 'rgba(251, 146, 60, 0.2)', 'border' => 'rgba(251, 146, 60, 0.4)', 'icon' => '🔄', 'color' => '#fb923c', 'pulse' => true],
                                'pending' => ['bg' => 'rgba(255, 255, 255, 0.05)', 'border' => 'rgba(255, 255, 255, 0.1)', 'icon' => '⏸️', 'color' => 'rgba(255,255,255,0.5)', 'pulse' => false],
                                'error' => ['bg' => 'rgba(239, 68, 68, 0.2)', 'border' => 'rgba(239, 68, 68, 0.4)', 'icon' => '❌', 'color' => '#ef4444', 'pulse' => false],
                            ];
                            $style = $statusConfig[$batch['status']] ?? $statusConfig['pending'];
                            $retryInfo = isset($batch['retryCount']) && $batch['retryCount'] > 0
                                ? ' (' . $batch['retryCount'] . '/' . ($scriptGeneration['maxRetries'] ?? 3) . ')'
                                : '';
                        @endphp
                        <div style="background: {{ $style['bg'] }}; border: 1px solid {{ $style['border'] }}; border-radius: 0.5rem; padding: 0.6rem 0.75rem; display: flex; align-items: center; gap: 0.75rem; {{ $style['pulse'] ? 'animation: vw-pulse 2s ease-in-out infinite;' : '' }}">
                            @if($style['pulse'])
                                <span style="font-size: 1rem; animation: vw-spin 1.5s linear infinite;">{{ $style['icon'] }}</span>
                            @else
                                <span style="font-size: 1rem;">{{ $style['icon'] }}</span>
                            @endif
                            <div style="flex: 1;">
                                <span style="color: white; font-weight: 500; font-size: 0.9rem;">{{ __('Batch') }} {{ $batch['batchNumber'] }}</span>
                                <span style="color: rgba(255,255,255,0.5); font-size: 0.8rem; margin-left: 0.5rem;">
                                    {{ __('Scenes') }} {{ $batch['startScene'] }}-{{ $batch['endScene'] }}
                                </span>
                            </div>
                            @if($batch['status'] === 'error')
                                <button wire:click="retryBatch({{ $batchIndex }})"
                                        style="background: rgba(239, 68, 68, 0.3); border: 1px solid rgba(239, 68, 68, 0.5); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; cursor: pointer;">
                                    🔄 {{ __('Retry') }}
                                </button>
                            @endif
                            <span style="color: {{ $style['color'] }}; font-size: 0.75rem; text-transform: capitalize;">
                                {{ __($batch['status']) }}{{ $retryInfo }}
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Action Buttons --}}
                <div style="display: flex; gap: 1rem;">
                    @if($scriptGeneration['status'] === 'complete')
                        <div style="flex: 1; padding: 0.75rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 0.5rem; text-align: center;">
                            <span style="color: #10b981; font-weight: 600;">✅ {{ __('All :count scenes generated!', ['count' => $scriptGeneration['targetSceneCount']]) }}</span>
                        </div>
                        <button wire:click="resetProgressiveGeneration"
                                style="padding: 0.75rem 1rem; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.5rem; color: #f87171; cursor: pointer; font-size: 0.85rem;">
                            🗑️ {{ __('Reset') }}
                        </button>
                    @elseif($scriptGeneration['status'] === 'paused' || $scriptGeneration['status'] === 'generating')
                        <button wire:click="generateNextBatch"
                                wire:loading.attr="disabled"
                                wire:target="generateNextBatch"
                                @if($scriptGeneration['status'] === 'generating') disabled @endif
                                style="flex: 1; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; opacity: {{ $scriptGeneration['status'] === 'generating' ? '0.6' : '1' }};">
                            <span wire:loading.remove wire:target="generateNextBatch">🚀 {{ __('Generate Next Batch') }}</span>
                            <span wire:loading wire:target="generateNextBatch">
                                <svg style="width: 16px; height: 16px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                </svg>
                                {{ __('Generating...') }}
                            </span>
                        </button>

                        <button wire:click="generateAllRemaining"
                                wire:loading.attr="disabled"
                                @if($scriptGeneration['status'] === 'generating') disabled @endif
                                style="flex: 1; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #06b6d4, #0891b2); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; opacity: {{ $scriptGeneration['status'] === 'generating' ? '0.6' : '1' }};">
                            ⚡ {{ __('Auto-Generate All') }}
                        </button>
                    @endif
                </div>
            </div>
        @else
            {{-- Pacing Selector (Hollywood-style scene architecture) --}}
            @php
                $currentPacing = $content['pacing'] ?? 'balanced';
                $sceneCount = $this->calculateSceneCount();
                $estimatedShots = $this->calculateEstimatedShotCount();
                $clipDuration = $this->getClipDuration();
                $sceneDuration = $script['timing']['sceneDuration'] ?? 35;
            @endphp
            <div style="margin-bottom: 1.25rem; padding: 1rem; background: rgba(139, 92, 246, 0.08); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 0.75rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: white; margin-bottom: 0.25rem;">🎬 {{ __('Pacing & Scene Structure') }}</div>
                        <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5);">{{ __('Hollywood-style: scenes contain multiple shots') }}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5);">{{ __('Clip duration') }}</div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: #06b6d4;">{{ $clipDuration }}s</div>
                    </div>
                </div>

                {{-- Pacing Options --}}
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.75rem;">
                    <button wire:click="setPacing('fast')"
                            style="padding: 0.6rem 0.5rem; border-radius: 0.5rem; border: 2px solid {{ $currentPacing === 'fast' ? '#8b5cf6' : 'rgba(255,255,255,0.15)' }}; background: {{ $currentPacing === 'fast' ? 'rgba(139, 92, 246, 0.2)' : 'rgba(255,255,255,0.05)' }}; cursor: pointer; text-align: center;">
                        <div style="font-size: 1rem; margin-bottom: 0.2rem;">⚡</div>
                        <div style="font-size: 0.7rem; font-weight: 600; color: {{ $currentPacing === 'fast' ? '#a78bfa' : 'white' }};">{{ __('Fast') }}</div>
                        <div style="font-size: 0.55rem; color: rgba(255,255,255,0.4);">~25s {{ __('scenes') }}</div>
                    </button>
                    <button wire:click="setPacing('balanced')"
                            style="padding: 0.6rem 0.5rem; border-radius: 0.5rem; border: 2px solid {{ $currentPacing === 'balanced' ? '#8b5cf6' : 'rgba(255,255,255,0.15)' }}; background: {{ $currentPacing === 'balanced' ? 'rgba(139, 92, 246, 0.2)' : 'rgba(255,255,255,0.05)' }}; cursor: pointer; text-align: center;">
                        <div style="font-size: 1rem; margin-bottom: 0.2rem;">🎭</div>
                        <div style="font-size: 0.7rem; font-weight: 600; color: {{ $currentPacing === 'balanced' ? '#a78bfa' : 'white' }};">{{ __('Balanced') }}</div>
                        <div style="font-size: 0.55rem; color: rgba(255,255,255,0.4);">~35s {{ __('scenes') }}</div>
                    </button>
                    <button wire:click="setPacing('contemplative')"
                            style="padding: 0.6rem 0.5rem; border-radius: 0.5rem; border: 2px solid {{ $currentPacing === 'contemplative' ? '#8b5cf6' : 'rgba(255,255,255,0.15)' }}; background: {{ $currentPacing === 'contemplative' ? 'rgba(139, 92, 246, 0.2)' : 'rgba(255,255,255,0.05)' }}; cursor: pointer; text-align: center;">
                        <div style="font-size: 1rem; margin-bottom: 0.2rem;">🌊</div>
                        <div style="font-size: 0.7rem; font-weight: 600; color: {{ $currentPacing === 'contemplative' ? '#a78bfa' : 'white' }};">{{ __('Contemplative') }}</div>
                        <div style="font-size: 0.55rem; color: rgba(255,255,255,0.4);">~45s {{ __('scenes') }}</div>
                    </button>
                </div>

                {{-- Scene/Shot Preview --}}
                <div style="display: flex; align-items: center; justify-content: center; gap: 1.5rem; padding: 0.6rem; background: rgba(0,0,0,0.2); border-radius: 0.5rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.25rem; font-weight: 700; color: #8b5cf6;">{{ $sceneCount }}</div>
                        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5);">{{ __('scenes') }}</div>
                    </div>
                    <div style="color: rgba(255,255,255,0.3);">→</div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.25rem; font-weight: 700; color: #06b6d4;">{{ $estimatedShots }}</div>
                        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5);">{{ __('shots') }}</div>
                    </div>
                    <div style="color: rgba(255,255,255,0.3);">→</div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.25rem; font-weight: 700; color: #10b981;">{{ floor($targetDuration / 60) }}:{{ str_pad($targetDuration % 60, 2, '0', STR_PAD_LEFT) }}</div>
                        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5);">{{ __('video') }}</div>
                    </div>
                </div>
            </div>

            {{-- Start Progressive Generation Button --}}
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <button style="flex: 1; padding: 1rem 1.5rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.75rem; color: white; font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                        wire:click="startProgressiveGeneration"
                        wire:loading.attr="disabled"
                        wire:target="startProgressiveGeneration">
                    <span wire:loading.remove wire:target="startProgressiveGeneration">🎬 {{ __('Generate Script') }} ({{ $sceneCount }} {{ __('scenes') }})</span>
                    <span wire:loading wire:target="startProgressiveGeneration">
                        <svg style="width: 18px; height: 18px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                        </svg>
                        {{ __('Starting...') }}
                    </span>
                </button>
            </div>

            <p style="text-align: center; color: rgba(255,255,255,0.5); font-size: 0.8rem; margin: 0;">
                {{ __('Each scene (~:sceneDur s) will be decomposed into :shots shots', ['sceneDur' => $sceneDuration, 'shots' => ceil($sceneDuration / $clipDuration)]) }} • {{ __('Powered by') }} {{ get_option('ai_platform', 'GPT-4o') }}
            </p>
        @endif
    </div>

    {{-- Scene Recovery Notice: Show when generation was marked complete but scenes are missing --}}
    @if($scriptGeneration['status'] === 'idle' && empty($script['scenes']))
        {{-- Normal state: nothing generated yet, show nothing --}}
    @elseif($scriptGeneration['status'] === 'complete' && empty($script['scenes']))
        {{-- ERROR STATE: Generation was marked complete but scenes are missing --}}
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                <span style="font-size: 1.5rem;">⚠️</span>
                <div style="flex: 1;">
                    <h4 style="color: #f87171; font-weight: 600; margin: 0 0 0.5rem 0; font-size: 1rem;">{{ __('Scene Data Recovery Issue') }}</h4>
                    <p style="color: rgba(255,255,255,0.8); margin: 0 0 1rem 0; font-size: 0.9rem;">
                        {{ __('The script generation was marked as complete, but the scene data appears to be missing. This can happen due to a synchronization issue.') }}
                    </p>
                    <p style="color: rgba(255,255,255,0.6); margin: 0 0 1rem 0; font-size: 0.85rem;">
                        {{ __('Please try regenerating your script. Your concept and settings are preserved.') }}
                    </p>
                    <button wire:click="resetProgressiveGeneration"
                            style="padding: 0.6rem 1.25rem; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 0.5rem; color: #fca5a5; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                        🔄 {{ __('Reset & Regenerate Script') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

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
            $rawScriptTitle = $script['title'] ?? null;
            if (is_array($rawScriptTitle)) $rawScriptTitle = reset($rawScriptTitle) ?: null;
            if (is_array($rawScriptTitle)) $rawScriptTitle = reset($rawScriptTitle) ?: null;
            $safeScriptTitle = is_string($rawScriptTitle) ? $rawScriptTitle : __('Your Script');

            $rawHook = $script['hook'] ?? null;
            if (is_array($rawHook)) $rawHook = reset($rawHook) ?: null;
            if (is_array($rawHook)) $rawHook = reset($rawHook) ?: null;
            $safeHook = is_string($rawHook) ? $rawHook : '';

            $rawCta = $script['cta'] ?? null;
            if (is_array($rawCta)) $rawCta = reset($rawCta) ?: null;
            if (is_array($rawCta)) $rawCta = reset($rawCta) ?: null;
            $safeCta = is_string($rawCta) ? $rawCta : '';
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
                            x-data="{}"
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
                    // Safety net: ensure all fields are strings
                    // Using explicit level-by-level unwrapping (no loops - Blade compatibility)

                    // Extract title - unwrap up to 5 levels
                    $rawTitle = $scene['title'] ?? null;
                    if (is_array($rawTitle)) $rawTitle = reset($rawTitle) ?: null;
                    if (is_array($rawTitle)) $rawTitle = reset($rawTitle) ?: null;
                    if (is_array($rawTitle)) $rawTitle = reset($rawTitle) ?: null;
                    if (is_array($rawTitle)) $rawTitle = reset($rawTitle) ?: null;
                    if (is_array($rawTitle)) $rawTitle = reset($rawTitle) ?: null;
                    $safeTitle = is_string($rawTitle) ? $rawTitle : (__('Scene') . ' ' . ($index + 1));

                    // Extract narration
                    $rawNarration = $scene['narration'] ?? null;
                    if (is_array($rawNarration)) $rawNarration = reset($rawNarration) ?: null;
                    if (is_array($rawNarration)) $rawNarration = reset($rawNarration) ?: null;
                    if (is_array($rawNarration)) $rawNarration = reset($rawNarration) ?: null;
                    $safeNarration = is_string($rawNarration) ? $rawNarration : '';

                    // Extract visualPrompt
                    $rawVisualPrompt = $scene['visualPrompt'] ?? null;
                    if (is_array($rawVisualPrompt)) $rawVisualPrompt = reset($rawVisualPrompt) ?: null;
                    if (is_array($rawVisualPrompt)) $rawVisualPrompt = reset($rawVisualPrompt) ?: null;
                    if (is_array($rawVisualPrompt)) $rawVisualPrompt = reset($rawVisualPrompt) ?: null;
                    $safeVisualPrompt = is_string($rawVisualPrompt) ? $rawVisualPrompt : '';

                    // Extract visualDescription
                    $rawVisualDescription = $scene['visualDescription'] ?? null;
                    if (is_array($rawVisualDescription)) $rawVisualDescription = reset($rawVisualDescription) ?: null;
                    if (is_array($rawVisualDescription)) $rawVisualDescription = reset($rawVisualDescription) ?: null;
                    if (is_array($rawVisualDescription)) $rawVisualDescription = reset($rawVisualDescription) ?: null;
                    $safeVisualDescription = is_string($rawVisualDescription) ? $rawVisualDescription : '';

                    // Extract mood - unwrap up to 5 levels
                    $rawMood = $scene['mood'] ?? null;
                    if (is_array($rawMood)) $rawMood = reset($rawMood) ?: null;
                    if (is_array($rawMood)) $rawMood = reset($rawMood) ?: null;
                    if (is_array($rawMood)) $rawMood = reset($rawMood) ?: null;
                    if (is_array($rawMood)) $rawMood = reset($rawMood) ?: null;
                    if (is_array($rawMood)) $rawMood = reset($rawMood) ?: null;
                    $safeMood = is_string($rawMood) ? $rawMood : '';
                    $moodDisplay = (is_string($safeMood) && $safeMood !== '') ? ucfirst($safeMood) : '';

                    // Extract transition
                    $rawTransition = $scene['transition'] ?? null;
                    if (is_array($rawTransition)) $rawTransition = reset($rawTransition) ?: null;
                    if (is_array($rawTransition)) $rawTransition = reset($rawTransition) ?: null;
                    $safeTransition = is_string($rawTransition) ? $rawTransition : 'cut';

                    // Extract duration
                    $safeDuration = is_numeric($scene['duration'] ?? null) ? (int)$scene['duration'] : 15;

                    // Extract sceneId
                    $rawSceneId = $scene['id'] ?? null;
                    if (is_array($rawSceneId)) $rawSceneId = reset($rawSceneId) ?: null;
                    if (is_array($rawSceneId)) $rawSceneId = reset($rawSceneId) ?: null;
                    $sceneId = is_string($rawSceneId) ? $rawSceneId : ('scene_' . $index);

                    // Use visualPrompt first, fall back to visualDescription
                    $displayVisualPrompt = $safeVisualPrompt ?: $safeVisualDescription;

                    // Check music only status
                    $isMusicOnly = isset($scene['voiceover']['enabled']) && !$scene['voiceover']['enabled'];

                    // Get transition label
                    $transitionLabel = $transitions[$safeTransition] ?? 'Cut';

                    // FINAL SAFETY: Force all display variables to strings using strval
                    $safeTitle = is_array($safeTitle) ? '' : strval($safeTitle);
                    $safeDuration = is_array($safeDuration) ? 15 : intval($safeDuration);
                    $transitionLabel = is_array($transitionLabel) ? 'Cut' : strval($transitionLabel);
                    $moodDisplay = is_array($moodDisplay) ? '' : strval($moodDisplay);
                @endphp
                <div class="vw-advanced-scene-card"
                     wire:key="scene-card-{{ $sceneId }}"
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
                            @if($moodDisplay !== '')
                                <span class="vw-scene-meta-badge">{{ $moodDisplay }}</span>
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
                                        @php $safeLabel = is_string($label) ? $label : (is_array($label) ? (reset($label) ?: $key) : strval($label ?? $key)); @endphp
                                        <option value="{{ $key }}" {{ $safeTransition === $key ? 'selected' : '' }}>
                                            {{ $safeLabel }}
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
                     :class="{ 'is-open': showFullScript }"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="vw-full-script-modal">
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
                                    // Safety net for modal scene display - handle nested arrays
                                    $rawModalTitle = $scene['title'] ?? null;
                                    if (is_array($rawModalTitle)) $rawModalTitle = reset($rawModalTitle) ?: null;
                                    if (is_array($rawModalTitle)) $rawModalTitle = reset($rawModalTitle) ?: null;
                                    $modalSceneTitle = is_string($rawModalTitle) ? $rawModalTitle : ('Scene ' . ($index + 1));

                                    $rawModalNarr = $scene['narration'] ?? null;
                                    if (is_array($rawModalNarr)) $rawModalNarr = reset($rawModalNarr) ?: null;
                                    if (is_array($rawModalNarr)) $rawModalNarr = reset($rawModalNarr) ?: null;
                                    $modalSceneNarration = is_string($rawModalNarr) ? $rawModalNarr : '';
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

    {{-- Scene Overwrite Confirmation Modal --}}
    @if($showSceneOverwriteModal)
        <div style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(4px);">
            <div style="background: linear-gradient(135deg, rgba(30, 30, 45, 0.98) 0%, rgba(20, 20, 35, 1) 100%); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 1rem; padding: 2rem; max-width: 480px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                {{-- Header --}}
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b, #ef4444); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        ⚠️
                    </div>
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; color: #ffffff; margin: 0;">
                            {{ __('Existing Scenes Found') }}
                        </h3>
                        <p style="font-size: 0.875rem; color: rgba(255, 255, 255, 0.6); margin: 0.25rem 0 0;">
                            {{ __('You already have') }} {{ count($script['scenes'] ?? []) }} {{ __('scenes generated') }}
                        </p>
                    </div>
                </div>

                {{-- Message --}}
                <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    {{ __('What would you like to do with your existing scenes?') }}
                </p>

                {{-- Options --}}
                <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem;">
                    {{-- Replace Option --}}
                    <button wire:click="confirmSceneOverwrite('replace')"
                            style="width: 100%; padding: 1rem; background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.3)); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 0.75rem; color: white; font-size: 0.95rem; font-weight: 600; cursor: pointer; text-align: left; display: flex; align-items: center; gap: 0.75rem; transition: all 0.2s;"
                            onmouseover="this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.3), rgba(220, 38, 38, 0.4))'"
                            onmouseout="this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.3))'">
                        <span style="font-size: 1.25rem;">🔄</span>
                        <div>
                            <div style="color: #fca5a5;">{{ __('Replace All') }}</div>
                            <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5); font-weight: 400;">{{ __('Delete existing scenes and start fresh') }}</div>
                        </div>
                    </button>

                    {{-- Append Option --}}
                    <button wire:click="confirmSceneOverwrite('append')"
                            style="width: 100%; padding: 1rem; background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(22, 163, 74, 0.3)); border: 1px solid rgba(34, 197, 94, 0.4); border-radius: 0.75rem; color: white; font-size: 0.95rem; font-weight: 600; cursor: pointer; text-align: left; display: flex; align-items: center; gap: 0.75rem; transition: all 0.2s;"
                            onmouseover="this.style.background='linear-gradient(135deg, rgba(34, 197, 94, 0.3), rgba(22, 163, 74, 0.4))'"
                            onmouseout="this.style.background='linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(22, 163, 74, 0.3))'">
                        <span style="font-size: 1.25rem;">➕</span>
                        <div>
                            <div style="color: #86efac;">{{ __('Add More') }}</div>
                            <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5); font-weight: 400;">{{ __('Keep existing scenes and generate additional ones') }}</div>
                        </div>
                    </button>
                </div>

                {{-- Cancel Button --}}
                <button wire:click="$set('showSceneOverwriteModal', false)"
                        style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: rgba(255, 255, 255, 0.7); font-size: 0.9rem; cursor: pointer; transition: all 0.2s;"
                        onmouseover="this.style.background='rgba(255, 255, 255, 0.15)'"
                        onmouseout="this.style.background='rgba(255, 255, 255, 0.1)'">
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>
    @endif
</div>

{{-- JavaScript for Delayed Retry with Exponential Backoff --}}
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('retry-batch-delayed', (data) => {
            const { batchIndex, delayMs } = data[0] || data;
            console.log(`Retrying batch ${batchIndex} in ${delayMs}ms...`);

            setTimeout(() => {
                Livewire.dispatch('execute-delayed-retry', { batchIndex: batchIndex });
            }, delayMs);
        });
    });
</script>
