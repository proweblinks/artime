{{-- Step 1: Platform & Format Selection --}}

{{-- Scoped CSS for Platform Step - uses design system tokens --}}
<style>
    /* Content Card Container */
    .vw-platform-step .vw-content-card {
        background: var(--vw-bg-surface);
        border: 1px solid var(--vw-border);
        border-radius: var(--vw-radius-lg);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .vw-platform-step .vw-card-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .vw-platform-step .vw-card-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        border-radius: var(--vw-radius-lg);
        background: var(--vw-primary-soft);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-card-title {
        font-size: var(--vw-text-lg);
        font-weight: 700;
        color: var(--vw-text);
        margin: 0;
    }

    .vw-platform-step .vw-card-subtitle {
        font-size: var(--vw-text-sm);
        color: var(--vw-text-muted);
        margin: 0;
    }

    /* Format Grid - 4 columns */
    .vw-platform-step .vw-format-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-platform-step .vw-format-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* Format Cards */
    .vw-platform-step .vw-format-card {
        background: var(--vw-bg-elevated);
        border: 2px solid var(--vw-border);
        border-radius: var(--vw-radius-lg);
        padding: 1.25rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: all var(--vw-transition);
        position: relative;
    }

    .vw-platform-step .vw-format-card:hover {
        background: var(--vw-bg-hover);
        border-color: var(--vw-border-accent);
    }

    .vw-platform-step .vw-format-card.selected {
        background: linear-gradient(135deg, var(--vw-bg-elevated) 0%, rgba(var(--vw-primary-rgb), 0.08) 100%);
        border-color: var(--vw-primary);
        box-shadow: var(--vw-primary-glow);
    }

    .vw-platform-step .vw-format-card.recommended::after {
        content: '★';
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        font-size: 0.75rem;
        color: var(--vw-warning);
    }

    .vw-platform-step .vw-format-icon {
        font-size: 1.5rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--vw-text-secondary);
        height: 2rem;
    }

    .vw-platform-step .vw-format-card.selected .vw-format-icon {
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-format-name {
        font-size: var(--vw-text-md);
        font-weight: 600;
        color: var(--vw-text);
        margin-bottom: 0.25rem;
    }

    .vw-platform-step .vw-format-card.selected .vw-format-name {
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-format-ratio {
        font-size: var(--vw-text-sm);
        color: var(--vw-text-secondary);
        margin-bottom: 0.25rem;
    }

    .vw-platform-step .vw-format-desc {
        font-size: var(--vw-text-xs);
        color: var(--vw-text-muted);
    }

    .vw-platform-step .vw-format-recommendation {
        font-size: var(--vw-text-xs);
        color: var(--vw-warning);
        margin-top: 0.25rem;
        font-weight: 500;
    }

    /* Production Type Grid - 3 columns */
    .vw-platform-step .vw-production-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-platform-step .vw-production-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-platform-step .vw-production-card {
        background: var(--vw-bg-elevated);
        border: 2px solid var(--vw-border);
        border-radius: var(--vw-radius-lg);
        padding: 1.25rem;
        text-align: center;
        cursor: pointer;
        transition: all var(--vw-transition);
    }

    .vw-platform-step .vw-production-card:hover {
        background: var(--vw-bg-hover);
        border-color: var(--vw-border-accent);
    }

    .vw-platform-step .vw-production-card.selected {
        background: linear-gradient(135deg, var(--vw-bg-elevated) 0%, rgba(var(--vw-primary-rgb), 0.08) 100%);
        border-color: var(--vw-primary);
        box-shadow: var(--vw-primary-glow);
    }

    .vw-platform-step .vw-production-icon {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--vw-text-secondary);
        height: 2rem;
    }

    .vw-platform-step .vw-production-card.selected .vw-production-icon {
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-production-name {
        font-size: var(--vw-text-md);
        font-weight: 600;
        color: var(--vw-text);
    }

    .vw-platform-step .vw-production-card.selected .vw-production-name {
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-production-desc {
        font-size: var(--vw-text-xs);
        color: var(--vw-text-muted);
        margin-top: 0.25rem;
    }

    /* Subtype Grid - 4 columns */
    .vw-platform-step .vw-subtype-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
    }

    @media (max-width: 1024px) {
        .vw-platform-step .vw-subtype-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .vw-platform-step .vw-subtype-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-platform-step .vw-subtype-card {
        background: var(--vw-bg-elevated);
        border: 1px solid var(--vw-border);
        border-radius: var(--vw-radius-lg);
        padding: 1rem;
        cursor: pointer;
        transition: all var(--vw-transition);
        text-align: center;
        position: relative;
    }

    .vw-platform-step .vw-subtype-card:hover {
        background: var(--vw-bg-hover);
        border-color: var(--vw-border-accent);
        transform: translateY(-1px);
    }

    .vw-platform-step .vw-subtype-card.selected {
        background: linear-gradient(135deg, var(--vw-bg-elevated) 0%, rgba(var(--vw-primary-rgb), 0.08) 100%);
        border-color: var(--vw-primary);
    }

    .vw-platform-step .vw-subtype-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
    }

    .vw-platform-step .vw-subtype-icon {
        font-size: 1.25rem;
        color: var(--vw-text-muted);
        margin-bottom: 0.15rem;
    }

    .vw-platform-step .vw-subtype-card.selected .vw-subtype-icon {
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-subtype-name {
        font-size: var(--vw-text-sm);
        font-weight: 600;
        color: var(--vw-text-secondary);
    }

    .vw-platform-step .vw-subtype-card.selected .vw-subtype-name {
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-subtype-desc {
        font-size: var(--vw-text-xs);
        color: var(--vw-text-muted);
        margin-top: 0.35rem;
        line-height: 1.3;
    }

    /* Info Icon on Subtype Cards */
    .vw-platform-step .vw-subtype-info-btn {
        position: absolute;
        top: 0.4rem;
        right: 0.4rem;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--vw-primary-soft);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all var(--vw-transition);
        z-index: 10;
        padding: 0;
    }
    .vw-platform-step .vw-subtype-info-btn:hover {
        background: rgba(var(--vw-primary-rgb), 0.3);
        transform: scale(1.1);
    }
    .vw-platform-step .vw-subtype-info-btn i {
        font-size: 0.65rem;
        color: var(--vw-primary);
    }

    /* Dark Glass-morphism Popover */
    .vw-platform-step .vw-style-popover {
        position: absolute;
        bottom: calc(100% + 10px);
        left: 50%;
        transform: translateX(-50%) scale(0.95);
        width: 340px;
        max-height: 380px;
        overflow-y: auto;
        background: rgba(15, 29, 61, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--vw-border-accent);
        border-radius: var(--vw-radius-lg);
        padding: 1rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 30px rgba(var(--vw-primary-rgb), 0.08);
        z-index: 200;
        opacity: 0;
        pointer-events: none;
        transition: opacity 150ms ease, transform 150ms ease;
    }
    .vw-platform-step .vw-style-popover.active {
        opacity: 1;
        pointer-events: auto;
        transform: translateX(-50%) scale(1);
    }
    /* Popover arrow */
    .vw-platform-step .vw-style-popover::after {
        content: '';
        position: absolute;
        bottom: -7px;
        left: 50%;
        transform: translateX(-50%) rotate(45deg);
        width: 14px;
        height: 14px;
        background: rgba(15, 29, 61, 0.95);
        border-right: 1px solid var(--vw-border-accent);
        border-bottom: 1px solid var(--vw-border-accent);
    }

    .vw-platform-step .vw-popover-header {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 0.5rem;
        padding-bottom: 0.4rem;
        border-bottom: 1px solid var(--vw-border);
    }
    .vw-platform-step .vw-popover-header i {
        font-size: 0.95rem;
        color: var(--vw-primary);
    }
    .vw-platform-step .vw-popover-title {
        font-size: var(--vw-text-sm);
        font-weight: 700;
        color: var(--vw-text);
    }
    .vw-platform-step .vw-popover-duration {
        font-size: var(--vw-text-xs);
        color: var(--vw-text-muted);
        margin-left: auto;
    }
    .vw-platform-step .vw-popover-desc {
        font-size: var(--vw-text-xs);
        line-height: 1.45;
        color: var(--vw-text-secondary);
        margin-bottom: 0.6rem;
    }
    .vw-platform-step .vw-popover-section-label {
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgba(var(--vw-primary-rgb), 0.7);
        margin-bottom: 0.25rem;
    }
    .vw-platform-step .vw-popover-bullets {
        list-style: none;
        padding: 0;
        margin: 0 0 0.6rem 0;
    }
    .vw-platform-step .vw-popover-bullets li {
        font-size: var(--vw-text-xs);
        color: var(--vw-text-secondary);
        padding: 0.1rem 0;
        padding-left: 0.85rem;
        position: relative;
    }
    .vw-platform-step .vw-popover-bullets li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.5em;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: var(--vw-primary);
    }
    .vw-platform-step .vw-popover-example {
        background: rgba(var(--vw-primary-rgb), 0.08);
        border: 1px solid rgba(var(--vw-primary-rgb), 0.15);
        border-radius: var(--vw-radius);
        padding: 0.5rem 0.65rem;
        font-size: var(--vw-text-xs);
        line-height: 1.4;
        color: var(--vw-text-secondary);
        font-style: italic;
    }

    /* Thin scrollbar for popover */
    .vw-platform-step .vw-style-popover::-webkit-scrollbar {
        width: 4px;
    }
    .vw-platform-step .vw-style-popover::-webkit-scrollbar-track {
        background: transparent;
    }
    .vw-platform-step .vw-style-popover::-webkit-scrollbar-thumb {
        background: rgba(var(--vw-primary-rgb), 0.25);
        border-radius: 4px;
    }
    .vw-platform-step .vw-style-popover::-webkit-scrollbar-thumb:hover {
        background: rgba(var(--vw-primary-rgb), 0.45);
    }

    /* Mobile: popover below card instead of above */
    @media (max-width: 768px) {
        .vw-platform-step .vw-style-popover {
            width: 300px;
            bottom: auto;
            top: calc(100% + 10px);
        }
        .vw-platform-step .vw-style-popover::after {
            bottom: auto;
            top: -7px;
            border-right: none;
            border-bottom: none;
            border-left: 1px solid var(--vw-border-accent);
            border-top: 1px solid var(--vw-border-accent);
        }
    }

    /* Divider */
    .vw-platform-step .vw-divider {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--vw-border);
    }

    .vw-platform-step .vw-subtype-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: var(--vw-text-base);
        font-weight: 500;
        color: var(--vw-text-secondary);
        margin-bottom: 1rem;
    }
    .vw-platform-step .vw-subtype-label i {
        color: var(--vw-primary);
        font-size: 1rem;
    }

    /* Production Settings - Modern 3-Column Layout */
    .vw-platform-step .vw-settings-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }

    @media (max-width: 992px) {
        .vw-platform-step .vw-settings-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }

    .vw-platform-step .vw-setting-section {
        background: var(--vw-bg-elevated);
        border: 1px solid var(--vw-border);
        border-radius: var(--vw-radius-lg);
        padding: 1.25rem;
    }

    .vw-platform-step .vw-setting-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-platform-step .vw-setting-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: var(--vw-radius);
        background: var(--vw-primary-soft);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-setting-title {
        font-size: var(--vw-text-base);
        font-weight: 600;
        color: var(--vw-text);
    }

    /* AI Engine Selection Grid */
    .vw-platform-step .vw-engine-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.5rem;
    }

    @media (max-width: 768px) {
        .vw-platform-step .vw-engine-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-platform-step .vw-engine-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border: 2px solid var(--vw-border);
        border-radius: var(--vw-radius-lg);
        cursor: pointer;
        transition: all var(--vw-transition);
        background: rgba(255, 255, 255, 0.03);
        position: relative;
    }

    .vw-platform-step .vw-engine-card:hover {
        border-color: var(--vw-border-accent);
        background: rgba(var(--vw-primary-rgb), 0.04);
    }

    .vw-platform-step .vw-engine-card.selected {
        border-color: var(--vw-primary);
        background: rgba(var(--vw-primary-rgb), 0.08);
    }

    .vw-platform-step .vw-engine-icon {
        font-size: 1.1rem;
        width: 28px;
        text-align: center;
        color: var(--vw-text-muted);
    }

    .vw-platform-step .vw-engine-card.selected .vw-engine-icon {
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-engine-info {
        flex: 1;
        min-width: 0;
    }

    .vw-platform-step .vw-engine-name {
        font-size: var(--vw-text-sm);
        font-weight: 600;
        color: var(--vw-text);
    }

    .vw-platform-step .vw-engine-card.selected .vw-engine-name {
        color: var(--vw-primary);
    }

    .vw-platform-step .vw-engine-desc {
        font-size: var(--vw-text-xs);
        color: var(--vw-text-muted);
    }

    .vw-platform-step .vw-engine-badge {
        font-size: 0.55rem;
        font-weight: 700;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        position: absolute;
        top: 0.4rem;
        right: 0.4rem;
    }

    .vw-platform-step .vw-engine-badge.gray {
        background: rgba(75, 86, 117, 0.25);
        color: var(--vw-text-secondary);
    }

    .vw-platform-step .vw-engine-badge.cyan {
        background: var(--vw-info-soft);
        color: #67e8f9;
    }

    .vw-platform-step .vw-engine-badge.green {
        background: var(--vw-success-soft);
        color: #6ee7b7;
    }

    .vw-platform-step .vw-engine-badge.orange {
        background: var(--vw-warning-soft);
        color: #fde68a;
    }

    .vw-platform-step .vw-engine-badge.yellow {
        background: var(--vw-warning-soft);
        color: #fde68a;
    }

    .vw-platform-step .vw-engine-badge.blue {
        background: rgba(59, 130, 246, 0.15);
        color: #93c5fd;
    }

    /* Language Selector - Dark Dropdown */
    .vw-platform-step .vw-lang-dropdown {
        position: relative;
    }

    .vw-platform-step .vw-lang-trigger {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid var(--vw-border);
        border-radius: var(--vw-radius-lg);
        background: rgba(255, 255, 255, 0.03);
        cursor: pointer;
        transition: all var(--vw-transition);
    }

    .vw-platform-step .vw-lang-trigger:hover {
        border-color: var(--vw-border-accent);
        background: rgba(var(--vw-primary-rgb), 0.04);
    }

    .vw-platform-step .vw-lang-trigger.open {
        border-color: var(--vw-primary);
        box-shadow: 0 0 0 3px rgba(var(--vw-primary-rgb), 0.15);
    }

    .vw-platform-step .vw-lang-trigger-flag {
        width: 28px;
        height: 20px;
        border-radius: 3px;
        object-fit: cover;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    .vw-platform-step .vw-lang-trigger-text {
        flex: 1;
        font-size: var(--vw-text-base);
        font-weight: 500;
        color: var(--vw-text);
        text-align: left;
    }

    .vw-platform-step .vw-lang-trigger-arrow {
        width: 16px;
        height: 16px;
        color: var(--vw-text-muted);
        transition: transform 0.2s ease;
    }

    .vw-platform-step .vw-lang-trigger.open .vw-lang-trigger-arrow {
        transform: rotate(180deg);
    }

    .vw-platform-step .vw-lang-menu {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: var(--vw-bg-elevated);
        border: 1px solid var(--vw-border-accent);
        border-radius: var(--vw-radius-lg);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        max-height: 280px;
        overflow-y: auto;
        z-index: 100;
        display: none;
    }

    .vw-platform-step .vw-lang-menu.open {
        display: block;
    }

    .vw-platform-step .vw-lang-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.625rem 1rem;
        cursor: pointer;
        transition: background 150ms ease;
    }

    .vw-platform-step .vw-lang-option:first-child {
        border-radius: var(--vw-radius-lg) var(--vw-radius-lg) 0 0;
    }

    .vw-platform-step .vw-lang-option:last-child {
        border-radius: 0 0 var(--vw-radius-lg) var(--vw-radius-lg);
    }

    .vw-platform-step .vw-lang-option:hover {
        background: rgba(var(--vw-primary-rgb), 0.1);
    }

    .vw-platform-step .vw-lang-option.selected {
        background: rgba(var(--vw-primary-rgb), 0.15);
    }

    .vw-platform-step .vw-lang-option-flag {
        width: 24px;
        height: 16px;
        border-radius: 2px;
        object-fit: cover;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .vw-platform-step .vw-lang-option-text {
        flex: 1;
        font-size: var(--vw-text-sm);
        color: var(--vw-text);
    }

    .vw-platform-step .vw-lang-option-native {
        font-size: var(--vw-text-xs);
        color: var(--vw-text-muted);
    }

    .vw-platform-step .vw-lang-option-check {
        width: 16px;
        height: 16px;
        color: var(--vw-primary);
        opacity: 0;
    }

    .vw-platform-step .vw-lang-option.selected .vw-lang-option-check {
        opacity: 1;
    }

    .vw-platform-step .vw-language-preview {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: rgba(var(--vw-primary-rgb), 0.06);
        border-radius: var(--vw-radius-lg);
        margin-top: 0.75rem;
        border: 1px solid rgba(var(--vw-primary-rgb), 0.12);
    }

    .vw-platform-step .vw-language-flag {
        width: 32px;
        height: 22px;
        border-radius: 3px;
        object-fit: cover;
        box-shadow: 0 2px 4px rgba(0,0,0,0.25);
    }

    .vw-platform-step .vw-language-info {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }

    .vw-platform-step .vw-language-name {
        font-size: var(--vw-text-sm);
        font-weight: 600;
        color: var(--vw-text);
    }

    .vw-platform-step .vw-language-desc {
        font-size: var(--vw-text-xs);
        color: var(--vw-text-secondary);
    }

    /* Duration Section */
    .vw-platform-step .vw-duration-display {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem;
        background: rgba(var(--vw-primary-rgb), 0.08);
        border-radius: var(--vw-radius-lg);
        margin-bottom: 1rem;
    }

    .vw-platform-step .vw-duration-value {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--vw-primary) 0%, var(--vw-info) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .vw-platform-step .vw-duration-unit {
        font-size: var(--vw-text-sm);
        color: var(--vw-text-secondary);
    }

    .vw-platform-step .vw-duration-slider-wrap {
        padding: 0 0.25rem;
    }

    .vw-platform-step .vw-range {
        width: 100%;
        height: 8px;
        border-radius: 4px;
        background: rgba(75, 86, 117, 0.3);
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }

    .vw-platform-step .vw-range::-webkit-slider-thumb {
        appearance: none;
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--vw-primary) 0%, var(--vw-info) 100%);
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    }

    .vw-platform-step .vw-range::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--vw-primary) 0%, var(--vw-info) 100%);
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    }

    .vw-platform-step .vw-range-labels {
        display: flex;
        justify-content: space-between;
        font-size: var(--vw-text-xs);
        color: var(--vw-text-muted);
        margin-top: 0.5rem;
    }

    /* Format Guidance Alert */
    .vw-platform-step .vw-format-guidance {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: var(--vw-warning-soft);
        border: 1px solid rgba(245, 158, 11, 0.2);
        border-radius: var(--vw-radius);
        margin-bottom: 1rem;
        font-size: var(--vw-text-sm);
        color: #fde68a;
    }

    .vw-platform-step .vw-format-guidance-icon {
        font-size: 1rem;
        color: var(--vw-warning);
    }
</style>

<div class="vw-platform-step">
    {{-- Video Format Card --}}
    <div class="vw-content-card">
        <div class="vw-card-header">
            <div class="vw-card-icon"><i class="fas fa-crop-simple"></i></div>
            <div>
                <div class="vw-card-title">{{ __('Video Format') }}</div>
                <div class="vw-card-subtitle">{{ __('Choose your aspect ratio') }}</div>
            </div>
        </div>

        {{-- Format guidance based on production type --}}
        @if($productionType)
            @php
                $formatGuidance = match($productionType) {
                    'movie', 'series' => ['format' => 'widescreen', 'text' => __('Recommended: Widescreen (16:9) for cinematic content')],
                    'social' => ['format' => 'vertical', 'text' => __('Recommended: Vertical (9:16) for social media')],
                    'music' => ['format' => 'widescreen', 'text' => __('Recommended: Widescreen (16:9) or Square (1:1) for music videos')],
                    'commercial' => ['format' => 'widescreen', 'text' => __('Recommended: Widescreen (16:9) for commercials')],
                    'educational' => ['format' => 'widescreen', 'text' => __('Recommended: Widescreen (16:9) for educational content')],
                    default => null
                };
            @endphp
            @if($formatGuidance)
                <div class="vw-format-guidance">
                    <span class="vw-format-guidance-icon"><i class="fas fa-lightbulb"></i></span>
                    <span>{{ $formatGuidance['text'] }}</span>
                </div>
            @endif
        @endif

        <div class="vw-format-grid">
            @foreach($formats as $id => $formatConfig)
                @php
                    $isRecommended = isset($formatGuidance) && $formatGuidance && $formatGuidance['format'] === $id;
                @endphp
                <div wire:click="selectFormat('{{ $id }}')"
                     class="vw-format-card {{ $format === $id ? 'selected' : '' }} {{ $isRecommended ? 'recommended' : '' }}"
                     style="cursor: pointer;">
                    <span class="vw-format-icon" style="pointer-events: none;">
                        @switch($id)
                            @case('widescreen') <i class="fas fa-display"></i> @break
                            @case('vertical') <i class="fas fa-mobile-screen"></i> @break
                            @case('square') <i class="fas fa-square"></i> @break
                            @case('tall') <i class="fas fa-rectangle-vertical"></i> @break
                            @default <i class="fas fa-film"></i>
                        @endswitch
                    </span>
                    <div class="vw-format-name">{{ $formatConfig['name'] }}</div>
                    <div class="vw-format-ratio">{{ $formatConfig['aspectRatio'] }}</div>
                    <div class="vw-format-desc">{{ $formatConfig['description'] }}</div>
                    @if($isRecommended)
                        <div class="vw-format-recommendation">{{ __('Recommended') }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Production Type Card --}}
    <div class="vw-content-card">
        <div class="vw-card-header">
            <div class="vw-card-icon"><i class="fas fa-film"></i></div>
            <div>
                <div class="vw-card-title">{{ __('What are you creating?') }}</div>
                <div class="vw-card-subtitle">{{ __('Select your production type') }}</div>
            </div>
        </div>

        <div class="vw-production-grid">
            @foreach($productionTypes as $typeId => $type)
                <div wire:click="selectProductionType('{{ $typeId }}')"
                     class="vw-production-card {{ $productionType === $typeId ? 'selected' : '' }}"
                     style="cursor: pointer;">
                    <span class="vw-production-icon" style="pointer-events: none;">
                        @switch($typeId)
                            @case('social') <i class="fas fa-mobile-screen"></i> @break
                            @case('movie') <i class="fas fa-film"></i> @break
                            @case('series') <i class="fas fa-tv"></i> @break
                            @case('educational') <i class="fas fa-graduation-cap"></i> @break
                            @case('music') <i class="fas fa-music"></i> @break
                            @case('commercial') <i class="fas fa-bullhorn"></i> @break
                            @default <i class="fas fa-crosshairs"></i>
                        @endswitch
                    </span>
                    <div class="vw-production-name">{{ $type['name'] }}</div>
                    <div class="vw-production-desc">{{ $type['description'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Sub-type Selection --}}
        @if($productionType && isset($productionTypes[$productionType]['subTypes']))
            <div class="vw-divider">
                <div class="vw-subtype-label">
                    <i class="{{ $productionTypes[$productionType]['icon'] ?? 'fa-solid fa-clapperboard' }}"></i>
                    <span>{{ __('Select :name Style:', ['name' => $productionTypes[$productionType]['name']]) }}</span>
                </div>

                <div class="vw-subtype-grid">
                    @foreach($productionTypes[$productionType]['subTypes'] as $subId => $subType)
                        <div wire:click="selectProductionType('{{ $productionType }}', '{{ $subId }}')"
                             class="vw-subtype-card {{ $productionSubtype === $subId ? 'selected' : '' }}"
                             style="cursor: pointer;"
                             x-data="{ showInfo: false }"
                             @mouseleave="showInfo = false">
                            {{-- Info icon (only if detailed description exists) --}}
                            @if(!empty($subType['detailedDescription']))
                                <button class="vw-subtype-info-btn"
                                        @click.stop="showInfo = !showInfo"
                                        @mouseenter="showInfo = true"
                                        title="{{ __('Learn more about this style') }}">
                                    <i class="fa-solid fa-circle-info"></i>
                                </button>

                                {{-- Dark glass-morphism popover --}}
                                <div class="vw-style-popover" :class="{ 'active': showInfo }" @click.stop>
                                    <div class="vw-popover-header">
                                        <i class="{{ $subType['icon'] ?? 'fa-solid fa-circle' }}"></i>
                                        <span class="vw-popover-title">{{ $subType['name'] }}</span>
                                        <span class="vw-popover-duration">{{ $subType['suggestedDuration']['min'] ?? '?' }}s – {{ $subType['suggestedDuration']['max'] ?? '?' }}s</span>
                                    </div>
                                    <div class="vw-popover-desc">{{ $subType['detailedDescription'] }}</div>

                                    @if(!empty($subType['perfectFor']))
                                        <div class="vw-popover-section-label">{{ __('Perfect For') }}</div>
                                        <ul class="vw-popover-bullets">
                                            @foreach($subType['perfectFor'] as $bullet)
                                                <li>{{ $bullet }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if(!empty($subType['exampleScenario']))
                                        <div class="vw-popover-section-label">{{ __('Example Scenario') }}</div>
                                        <div class="vw-popover-example">{{ $subType['exampleScenario'] }}</div>
                                    @endif
                                </div>
                            @endif

                            <div class="vw-subtype-header">
                                <span class="vw-subtype-icon"><i class="{{ $subType['icon'] ?? 'fa-solid fa-circle' }}"></i></span>
                                <span class="vw-subtype-name">{{ $subType['name'] }}</span>
                            </div>
                            <div class="vw-subtype-desc">{{ $subType['description'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Production Settings Card - Now appears after just productionType is selected --}}
    @if($productionType)
        @php
            $aiEngines = \Modules\AppVideoWizard\Livewire\VideoWizard::AI_ENGINES;
            $languages = \Modules\AppVideoWizard\Livewire\VideoWizard::SUPPORTED_LANGUAGES;
            $selectedEngine = $content['aiEngine'] ?? 'grok';
            $selectedLang = $content['language'] ?? 'en';

            // Get duration range - use subtype if available, otherwise use type defaults
            if ($productionSubtype && isset($productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration'])) {
                $durationMin = $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['min'] ?? 15;
                $durationMax = $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['max'] ?? 300;
            } else {
                // Default duration range when subtype not yet selected
                $durationMin = 15;
                $durationMax = 5400;  // 90 minutes - allow full movie creation
            }
        @endphp
        <div class="vw-content-card">
            <div class="vw-card-header">
                <div class="vw-card-icon"><i class="fas fa-gear"></i></div>
                <div>
                    <div class="vw-card-title">{{ __('Production Settings') }}</div>
                    <div class="vw-card-subtitle">
                        {{ __('Configure AI model, language, and duration') }}
                    </div>
                </div>
            </div>

            <div class="vw-settings-grid">
                {{-- AI Engine Selection --}}
                <div class="vw-setting-section">
                    <div class="vw-setting-header">
                        <span class="vw-setting-icon"><i class="fas fa-microchip"></i></span>
                        <span class="vw-setting-title">{{ __('AI Engine') }}</span>
                    </div>
                    <div class="vw-engine-grid">
                        @foreach($aiEngines as $engineKey => $engine)
                            <div class="vw-engine-card {{ $selectedEngine === $engineKey ? 'selected' : '' }}"
                                 wire:click="$set('content.aiEngine', '{{ $engineKey }}')">
                                <span class="vw-engine-icon"><i class="{{ $engine['icon'] }}"></i></span>
                                <div class="vw-engine-info">
                                    <span class="vw-engine-name">{{ $engine['label'] }}</span>
                                    <div class="vw-engine-desc">{{ $engine['description'] }}</div>
                                </div>
                                <span class="vw-engine-badge {{ $engine['badgeColor'] }}">{{ $engine['badge'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Language Selection --}}
                <div class="vw-setting-section">
                    <div class="vw-setting-header">
                        <span class="vw-setting-icon"><i class="fas fa-globe"></i></span>
                        <span class="vw-setting-title">{{ __('Content Language') }}</span>
                    </div>
                    <div class="vw-lang-dropdown" x-data="{ open: false }" @click.away="open = false">
                        {{-- Dropdown Trigger --}}
                        <div class="vw-lang-trigger" :class="{ 'open': open }" @click="open = !open">
                            <img src="https://flagcdn.com/w40/{{ $languages[$selectedLang]['country'] ?? 'us' }}.png"
                                 srcset="https://flagcdn.com/w80/{{ $languages[$selectedLang]['country'] ?? 'us' }}.png 2x"
                                 class="vw-lang-trigger-flag"
                                 alt="{{ $languages[$selectedLang]['name'] ?? 'English' }}">
                            <span class="vw-lang-trigger-text">{{ $languages[$selectedLang]['name'] ?? 'English' }} ({{ $languages[$selectedLang]['native'] ?? 'English' }})</span>
                            <svg class="vw-lang-trigger-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </div>

                        {{-- Dropdown Menu --}}
                        <div class="vw-lang-menu" :class="{ 'open': open }">
                            @foreach($languages as $langCode => $lang)
                                <div class="vw-lang-option {{ $selectedLang === $langCode ? 'selected' : '' }}"
                                     wire:click="$set('content.language', '{{ $langCode }}')"
                                     @click="open = false">
                                    <img src="https://flagcdn.com/w40/{{ $lang['country'] }}.png"
                                         srcset="https://flagcdn.com/w80/{{ $lang['country'] }}.png 2x"
                                         class="vw-lang-option-flag"
                                         alt="{{ $lang['name'] }}">
                                    <span class="vw-lang-option-text">{{ $lang['name'] }}</span>
                                    <span class="vw-lang-option-native">{{ $lang['native'] }}</span>
                                    <svg class="vw-lang-option-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <path d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if(isset($languages[$selectedLang]))
                        <div class="vw-language-preview">
                            <img src="https://flagcdn.com/w80/{{ $languages[$selectedLang]['country'] }}.png"
                                 srcset="https://flagcdn.com/w160/{{ $languages[$selectedLang]['country'] }}.png 2x"
                                 class="vw-language-flag"
                                 alt="{{ $languages[$selectedLang]['name'] }}">
                            <div class="vw-language-info">
                                <span class="vw-language-name">{{ $languages[$selectedLang]['name'] }}</span>
                                <span class="vw-language-desc">{{ __('Script & voiceover in :lang', ['lang' => $languages[$selectedLang]['native']]) }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Video Duration --}}
                <div class="vw-setting-section">
                    <div class="vw-setting-header">
                        <span class="vw-setting-icon"><i class="fas fa-clock"></i></span>
                        <span class="vw-setting-title">{{ __('Video Duration') }}</span>
                    </div>
                    <div class="vw-duration-display">
                        <span class="vw-duration-value">
                            @if($targetDuration >= 60)
                                {{ floor($targetDuration / 60) }}:{{ str_pad($targetDuration % 60, 2, '0', STR_PAD_LEFT) }}
                            @else
                                {{ $targetDuration }}s
                            @endif
                        </span>
                        <span class="vw-duration-unit">
                            @if($targetDuration >= 60)
                                {{ __('minutes') }}
                            @else
                                {{ __('seconds') }}
                            @endif
                        </span>
                    </div>
                    <div class="vw-duration-slider-wrap">
                        <input type="range"
                               wire:model.live="targetDuration"
                               min="{{ $durationMin }}"
                               max="{{ $durationMax }}"
                               class="vw-range" />
                        <div class="vw-range-labels">
                            <span>{{ $durationMin }}s</span>
                            <span>{{ $durationMax }}s</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
