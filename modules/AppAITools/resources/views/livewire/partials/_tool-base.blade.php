<style>
/* ===== AI Tools - Shared Tool Styles (aith- prefix) ===== */

/* Root container */
.aith-tool {
    padding: 1.5rem;
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Navigation bar */
.aith-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}
.aith-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.7);
    font-size: 0.8125rem;
    font-weight: 500;
    border: 1px solid rgba(255,255,255,0.1);
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
}
.aith-nav-btn:hover {
    background: rgba(255,255,255,0.15);
    color: #fff;
    transform: translateY(-1px);
}
.aith-nav-btn i { font-size: 0.875rem; }
.aith-nav-spacer { flex: 1; }

/* White card */
.aith-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
    margin-bottom: 1.25rem;
    transition: box-shadow 0.3s, transform 0.3s;
}
.aith-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

/* Card title */
.aith-card-title {
    font-size: 1.375rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.aith-card-title .aith-emoji { font-size: 1.5rem; }

/* Feature description box */
.aith-feature-box {
    border-radius: 12px;
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.aith-feature-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    transition: all 0.2s;
}
.aith-feature-toggle:hover { opacity: 0.8; }
.aith-feature-toggle .aith-chevron {
    margin-left: auto;
    transition: transform 0.3s;
    font-size: 0.75rem;
}
.aith-feature-toggle.aith-open .aith-chevron {
    transform: rotate(180deg);
}
.aith-feature-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease, padding 0.3s ease;
    padding: 0 1rem;
}
.aith-feature-content.aith-open {
    max-height: 500px;
    padding: 0 1rem 1rem;
}
.aith-feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.625rem;
}
.aith-feature-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #475569;
}
.aith-feature-item i {
    color: #22c55e;
    margin-top: 2px;
    flex-shrink: 0;
}

/* Feature box color variants */
.aith-feat-blue { background: linear-gradient(135deg, #eff6ff, #eef2ff, #f5f3ff); }
.aith-feat-red { background: linear-gradient(135deg, #fef2f2, #fff7ed, #fffbeb); }
.aith-feat-cyan { background: linear-gradient(135deg, #ecfeff, #eff6ff, #eef2ff); }
.aith-feat-pink { background: linear-gradient(135deg, #fdf2f8, #fff1f2, #f5f3ff); }
.aith-feat-emerald { background: linear-gradient(135deg, #ecfdf5, #f0fdfa, #ecfeff); }
.aith-feat-purple { background: linear-gradient(135deg, #f5f3ff, #ede9fe, #eef2ff); }
.aith-feat-yellow { background: linear-gradient(135deg, #fefce8, #fff7ed, #fff1f2); }
.aith-feat-teal { background: linear-gradient(135deg, #f0fdfa, #ecfeff, #eff6ff); }

/* Form elements */
.aith-form-group {
    margin-bottom: 1rem;
}
.aith-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.375rem;
}
.aith-label-hint {
    font-weight: 400;
    color: #9ca3af;
    font-size: 0.75rem;
}
.aith-input,
.aith-select,
.aith-textarea {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.875rem;
    color: #1e293b;
    background: #fff;
    transition: all 0.2s;
    outline: none;
    box-sizing: border-box;
    font-family: inherit;
}
.aith-input:focus,
.aith-select:focus,
.aith-textarea:focus {
    border-color: #7c3aed;
    box-shadow: 0 4px 16px rgba(124,58,237,0.1);
    transform: translateY(-1px);
}
.aith-input::placeholder,
.aith-textarea::placeholder {
    color: #9ca3af;
}
.aith-textarea {
    resize: vertical;
    min-height: 80px;
}
.aith-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2rem;
}
.aith-radio-group {
    display: flex;
    gap: 0.5rem;
}
.aith-radio-option {
    flex: 1;
    text-align: center;
    padding: 0.5rem;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    cursor: pointer;
    font-size: 0.75rem;
    color: #64748b;
    transition: all 0.2s;
}
.aith-radio-option.aith-selected {
    border-color: #7c3aed;
    background: rgba(124,58,237,0.06);
    color: #7c3aed;
    font-weight: 600;
}
.aith-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.aith-checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #475569;
    cursor: pointer;
}
.aith-checkbox-item input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    accent-color: #7c3aed;
}
.aith-range-wrap {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.aith-range {
    width: 100%;
    accent-color: #7c3aed;
}
.aith-range-val {
    text-align: center;
    font-size: 0.75rem;
    color: #64748b;
}

/* Buttons */
.aith-btn-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: #fff;
    font-size: 0.9375rem;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(37,99,235,0.3);
}
.aith-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37,99,235,0.4);
}
.aith-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.aith-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: transparent;
    color: #64748b;
    font-size: 0.8125rem;
    font-weight: 500;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}
.aith-btn-secondary:hover {
    background: #f8fafc;
    color: #334155;
    border-color: #cbd5e1;
}

/* Error */
.aith-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-size: 0.8125rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.aith-field-error {
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* Loading state */
.aith-loading {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1.25rem;
    border: 1px solid #e2e8f0;
}
.aith-loading-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}
.aith-loading-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #334155;
}
.aith-loading-title .aith-emoji { font-size: 1.25rem; }

/* Progress bar */
.aith-progress-bar {
    width: 100%;
    height: 10px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 1rem;
}
.aith-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #6366f1, #8b5cf6, #a855f7);
    border-radius: 999px;
    transition: width 0.3s ease;
    position: relative;
}
.aith-progress-fill::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: aithShimmer 1.5s infinite;
}
@keyframes aithShimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.aith-progress-pct {
    font-size: 1.5rem;
    font-weight: 700;
    color: #7c3aed;
}

/* Steps grid */
.aith-steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.aith-step {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.625rem;
    border-radius: 8px;
    font-size: 0.6875rem;
    font-weight: 500;
    background: #f1f5f9;
    color: #94a3b8;
    transition: all 0.3s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.aith-step.aith-step-active {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    color: #4f46e5;
}
.aith-step.aith-step-done {
    background: #ecfdf5;
    color: #059669;
}
.aith-step-icon {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.625rem;
    flex-shrink: 0;
    background: #e2e8f0;
    color: #94a3b8;
}
.aith-step-active .aith-step-icon {
    background: #6366f1;
    color: #fff;
    animation: aithPulse 1.5s infinite;
}
.aith-step-done .aith-step-icon {
    background: #10b981;
    color: #fff;
}
@keyframes aithPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(99,102,241,0.4); }
    50% { box-shadow: 0 0 0 6px rgba(99,102,241,0); }
}

/* Tip box */
.aith-tip {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.625rem 0.875rem;
    background: rgba(99,102,241,0.06);
    border-radius: 8px;
    font-size: 0.75rem;
    color: #6366f1;
    line-height: 1.4;
}
.aith-tip .aith-emoji { flex-shrink: 0; }

/* Tabs */
.aith-tabs {
    display: flex;
    gap: 0.25rem;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 1rem;
}
.aith-tab {
    padding: 0.625rem 1rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #94a3b8;
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}
.aith-tab:hover { color: #64748b; }
.aith-tab.aith-tab-active {
    color: #7c3aed;
    border-bottom-color: #7c3aed;
    font-weight: 600;
}
.aith-tab-content {
    display: none;
}
.aith-tab-content.aith-tab-active {
    display: block;
}

/* Copy button */
.aith-copy-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.625rem;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.aith-copy-btn:hover {
    background: #f1f5f9;
    color: #334155;
}
.aith-copy-btn.aith-copied {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
}

/* Result item */
.aith-result-item {
    padding: 0.875rem 1rem;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #f1f5f9;
    transition: all 0.2s;
    margin-bottom: 0.625rem;
}
.aith-result-item:hover {
    background: #f1f5f9;
    border-color: #e2e8f0;
    transform: translateX(2px);
}
.aith-result-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}
.aith-result-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #7c3aed;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}
.aith-result-text {
    font-size: 0.875rem;
    color: #334155;
    line-height: 1.5;
}
.aith-result-pre {
    white-space: pre-wrap;
    font-size: 0.8125rem;
    color: #334155;
    line-height: 1.6;
    background: #f8fafc;
    padding: 1rem;
    border-radius: 10px;
    border: 1px solid #f1f5f9;
}

/* Badges */
.aith-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.1875rem 0.5rem;
    border-radius: 999px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.aith-badge-high { background: #fef2f2; color: #dc2626; }
.aith-badge-medium { background: #fffbeb; color: #d97706; }
.aith-badge-low { background: #eff6ff; color: #2563eb; }
.aith-badge-success { background: #ecfdf5; color: #059669; }
.aith-badge-purple { background: #f5f3ff; color: #7c3aed; }
.aith-badge-ghost {
    background: #f1f5f9;
    color: #64748b;
    font-weight: 500;
    text-transform: none;
}

/* SVG Score gauge */
.aith-score-wrap {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.aith-score-gauge {
    position: relative;
    width: 100px;
    height: 100px;
}
.aith-score-gauge svg {
    transform: rotate(-90deg);
    width: 100%;
    height: 100%;
}
.aith-score-gauge .aith-gauge-bg {
    fill: none;
    stroke: #e2e8f0;
    stroke-width: 8;
}
.aith-score-gauge .aith-gauge-fill {
    fill: none;
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dashoffset 1s ease;
}
.aith-score-val {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
}
.aith-score-label {
    font-size: 0.75rem;
    color: #64748b;
    text-align: center;
    margin-top: 0.25rem;
}

/* Tag pills */
.aith-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.3125rem 0.75rem;
    background: linear-gradient(135deg, #f5f3ff, #eef2ff);
    color: #6d28d9;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 999px;
    border: 1px solid #e9d5ff;
    transition: all 0.2s;
    cursor: default;
}
.aith-tag:hover {
    background: linear-gradient(135deg, #ede9fe, #e0e7ff);
    transform: translateY(-1px);
}
.aith-tags-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

/* Grid layouts */
.aith-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
.aith-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
.aith-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; }

/* Empty state */
.aith-empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: rgba(255,255,255,0.3);
}
.aith-empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}
.aith-empty-state p {
    font-size: 0.9375rem;
    margin: 0;
}
.aith-empty-state .aith-empty-sub {
    font-size: 0.8125rem;
    margin-top: 0.5rem;
    opacity: 0.7;
}

/* Video info card */
.aith-video-info {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 1.25rem;
}
.aith-video-thumb {
    width: 140px;
    border-radius: 8px;
    flex-shrink: 0;
}
.aith-video-meta h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.375rem;
}
.aith-video-meta p {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0;
}
.aith-video-stats {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
    flex-wrap: wrap;
}

/* Section divider */
.aith-section-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Strength/Weakness boxes */
.aith-sw-box {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 0.75rem;
}
.aith-sw-strength { background: #ecfdf5; border: 1px solid #d1fae5; }
.aith-sw-weakness { background: #fef2f2; border: 1px solid #fecaca; }
.aith-sw-opportunity { background: #eff6ff; border: 1px solid #dbeafe; }
.aith-sw-threat { background: #fffbeb; border: 1px solid #fed7aa; }
.aith-sw-box h5 {
    font-size: 0.8125rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
}
.aith-sw-strength h5 { color: #059669; }
.aith-sw-weakness h5 { color: #dc2626; }
.aith-sw-opportunity h5 { color: #2563eb; }
.aith-sw-threat h5 { color: #d97706; }
.aith-sw-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.aith-sw-list li {
    font-size: 0.8125rem;
    color: #475569;
    padding: 0.25rem 0;
    display: flex;
    align-items: flex-start;
    gap: 0.375rem;
}
.aith-sw-list li::before {
    content: '';
    width: 5px;
    height: 5px;
    border-radius: 50%;
    margin-top: 0.4rem;
    flex-shrink: 0;
}
.aith-sw-strength .aith-sw-list li::before { background: #10b981; }
.aith-sw-weakness .aith-sw-list li::before { background: #ef4444; }
.aith-sw-opportunity .aith-sw-list li::before { background: #3b82f6; }
.aith-sw-threat .aith-sw-list li::before { background: #f59e0b; }

/* Strategy banner */
.aith-strategy-banner {
    background: linear-gradient(135deg, #7c3aed, #6366f1);
    color: #fff;
    padding: 1.25rem;
    border-radius: 12px;
    margin-top: 1rem;
}
.aith-strategy-banner h4 {
    font-weight: 600;
    margin: 0 0 0.5rem;
    font-size: 0.9375rem;
}
.aith-strategy-banner p {
    font-size: 0.8125rem;
    opacity: 0.9;
    line-height: 1.5;
    margin: 0;
}

/* Score card mini */
.aith-score-mini {
    text-align: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #f1f5f9;
}
.aith-score-mini .aith-score-num {
    font-size: 1.75rem;
    font-weight: 700;
}
.aith-score-mini .aith-score-name {
    font-size: 0.6875rem;
    color: #64748b;
    margin-top: 0.25rem;
}

/* Trend indicator */
.aith-trend-up { color: #10b981; }
.aith-trend-down { color: #ef4444; }
.aith-trend-stable { color: #f59e0b; }

/* File upload zone */
.aith-upload-zone {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #fafafa;
}
.aith-upload-zone:hover {
    border-color: #7c3aed;
    background: rgba(124,58,237,0.03);
}
.aith-upload-zone.aith-has-file {
    border-color: #10b981;
    border-style: solid;
}
.aith-upload-preview {
    width: 100%;
    border-radius: 10px;
    margin-top: 0.75rem;
}

/* Winner banner */
.aith-winner-banner {
    background: linear-gradient(135deg, #fef9c3, #fef3c7);
    border: 2px solid #fbbf24;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    text-align: center;
    margin-bottom: 1rem;
}
.aith-winner-banner .aith-trophy { font-size: 1.5rem; }
.aith-winner-banner h4 {
    font-size: 1rem;
    font-weight: 700;
    color: #92400e;
    margin: 0.25rem 0;
}
.aith-winner-banner p {
    font-size: 0.8125rem;
    color: #78716c;
    margin: 0;
}

/* Progress bar for scores */
.aith-bar-wrap {
    margin-bottom: 0.75rem;
}
.aith-bar-label {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
}
.aith-bar-label span:first-child { color: #475569; }
.aith-bar-label span:last-child { font-weight: 600; color: #334155; }
.aith-bar-track {
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
}
.aith-bar-value {
    height: 100%;
    border-radius: 999px;
    transition: width 0.8s ease;
}
.aith-bar-green { background: linear-gradient(90deg, #10b981, #34d399); }
.aith-bar-yellow { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.aith-bar-red { background: linear-gradient(90deg, #ef4444, #f87171); }
.aith-bar-purple { background: linear-gradient(90deg, #7c3aed, #a78bfa); }

/* Sub-tools grid cards */
.aith-subtool-card {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem;
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.aith-subtool-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    border-color: #c4b5fd;
}
.aith-subtool-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: #fff;
    flex-shrink: 0;
    transition: transform 0.3s;
}
.aith-subtool-card:hover .aith-subtool-icon {
    transform: scale(1.1);
}
.aith-subtool-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem;
}
.aith-subtool-desc {
    font-size: 0.8125rem;
    color: #64748b;
    line-height: 1.4;
    margin: 0;
}
.aith-subtool-credits {
    margin-top: 0.5rem;
}

/* Icon backgrounds */
.aith-icon-blue { background: linear-gradient(135deg, #3b82f6, #6366f1); }
.aith-icon-yellow { background: linear-gradient(135deg, #f59e0b, #ef4444); }
.aith-icon-teal { background: linear-gradient(135deg, #14b8a6, #06b6d4); }
.aith-icon-red { background: linear-gradient(135deg, #ef4444, #a855f7); }

/* Responsive */
@media (max-width: 640px) {
    .aith-tool { padding: 1rem; }
    .aith-card { padding: 1.25rem; }
    .aith-steps-grid { grid-template-columns: repeat(2, 1fr); }
    .aith-grid-2, .aith-grid-3, .aith-grid-4 { grid-template-columns: 1fr; }
    .aith-feature-grid { grid-template-columns: 1fr; }
    .aith-score-wrap { flex-direction: column; align-items: center; }
    .aith-video-info { flex-direction: column; }
    .aith-video-thumb { width: 100%; }
    .aith-nav { flex-wrap: wrap; }
}
</style>

<script>
/* ===== AI Tools - Shared JavaScript ===== */
function aithToggleFeature(btn) {
    var content = btn.nextElementSibling;
    btn.classList.toggle('aith-open');
    content.classList.toggle('aith-open');
}

function aithCopyToClipboard(text, btnEl) {
    if (!btnEl) return;
    navigator.clipboard.writeText(text).then(function() {
        var orig = btnEl.innerHTML;
        btnEl.innerHTML = '<i class="fa-light fa-check"></i> Copied!';
        btnEl.classList.add('aith-copied');
        setTimeout(function() {
            btnEl.innerHTML = orig;
            btnEl.classList.remove('aith-copied');
        }, 2000);
    });
}

function aithSetTab(group, tabId) {
    var tabs = document.querySelectorAll('[data-aith-tab-group="' + group + '"]');
    var contents = document.querySelectorAll('[data-aith-tab-content="' + group + '"]');
    tabs.forEach(function(t) { t.classList.toggle('aith-tab-active', t.dataset.aithTab === tabId); });
    contents.forEach(function(c) { c.classList.toggle('aith-tab-active', c.dataset.aithPane === tabId); });
}
</script>
