{{-- Enterprise Tool Base Partial --}}
{{-- Include aith shared styles --}}
@include('appaitools::livewire.partials._tool-base')

<style>
    /* Dark theme overrides for enterprise tools */
    .aith-card {
        background: rgba(255,255,255,0.04) !important;
        border-color: rgba(255,255,255,0.08) !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
    }
    .aith-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.4) !important;
    }
    .aith-label {
        color: rgba(255,255,255,0.7) !important;
    }
    .aith-label-hint {
        color: rgba(255,255,255,0.35) !important;
    }
    .aith-input,
    .aith-select,
    .aith-textarea {
        background: rgba(255,255,255,0.06) !important;
        border-color: rgba(255,255,255,0.12) !important;
        color: #fff !important;
    }
    .aith-input:focus,
    .aith-select:focus,
    .aith-textarea:focus {
        border-color: rgba(139,92,246,0.5) !important;
        box-shadow: 0 4px 16px rgba(139,92,246,0.2) !important;
    }
    .aith-input::placeholder,
    .aith-textarea::placeholder {
        color: rgba(255,255,255,0.25) !important;
    }
    .aith-btn-secondary {
        color: rgba(255,255,255,0.6) !important;
        border-color: rgba(255,255,255,0.15) !important;
        background: transparent !important;
    }
    .aith-btn-secondary:hover {
        background: rgba(255,255,255,0.08) !important;
        color: #fff !important;
        border-color: rgba(255,255,255,0.25) !important;
    }

    /* Enterprise Tool Specific Overrides */
    .aith-tool { max-width: 1080px; }

    .aith-e-tool-header {
        display: flex; align-items: center; gap: 0.75rem;
        margin-bottom: 1.5rem; padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .aith-e-tool-icon {
        width: 2.5rem; height: 2.5rem; border-radius: 0.625rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
    }
    .aith-e-tool-info h2 { font-size: 1.25rem; font-weight: 700; color: #fff; margin: 0; }
    .aith-e-tool-info p { font-size: 0.8rem; color: rgba(255,255,255,0.4); margin: 0.125rem 0 0; }

    .aith-e-badge-enterprise {
        margin-left: auto;
        padding: 0.25rem 0.75rem; border-radius: 9999px;
        background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(236,72,153,0.2));
        border: 1px solid rgba(139,92,246,0.3);
        color: #c4b5fd; font-size: 0.7rem; font-weight: 600;
    }

    /* Loading Steps */
    .aith-e-loading { padding: 2rem 0; text-align: center; }
    .aith-e-loading-title { font-size: 1.1rem; font-weight: 600; color: #fff; margin-bottom: 1.5rem; }
    .aith-e-loading-steps { max-width: 400px; margin: 0 auto; }
    .aith-e-loading-step {
        display: flex; align-items: center; gap: 0.75rem;
        padding: 0.5rem 0;
        font-size: 0.875rem;
        transition: all 0.3s;
    }
    .aith-e-loading-step .step-icon {
        width: 24px; height: 24px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.7rem;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.3);
        flex-shrink: 0;
    }
    .aith-e-loading-step.active .step-icon {
        background: rgba(139,92,246,0.2);
        border-color: rgba(139,92,246,0.5);
        color: #c4b5fd;
        animation: aith-e-pulse 1.5s infinite;
    }
    .aith-e-loading-step.done .step-icon {
        background: rgba(34,197,94,0.2);
        border-color: rgba(34,197,94,0.5);
        color: #86efac;
    }
    .aith-e-loading-step .step-label { color: rgba(255,255,255,0.3); }
    .aith-e-loading-step.active .step-label { color: rgba(255,255,255,0.8); }
    .aith-e-loading-step.done .step-label { color: rgba(255,255,255,0.5); }
    @keyframes aith-e-pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }

    .aith-e-progress-bar {
        width: 100%; height: 4px; border-radius: 2px;
        background: rgba(255,255,255,0.05);
        margin-top: 1.5rem; overflow: hidden;
    }
    .aith-e-progress-fill {
        height: 100%; border-radius: 2px;
        background: linear-gradient(90deg, #7c3aed, #ec4899);
        transition: width 0.5s ease;
    }

    /* Result Sections */
    .aith-e-result-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 1.5rem; padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .aith-e-result-title { font-size: 1.1rem; font-weight: 700; color: #fff; }

    .aith-e-score-card {
        display: flex; align-items: center; gap: 1rem;
        padding: 1.25rem; border-radius: 0.75rem;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        margin-bottom: 1.5rem;
    }
    .aith-e-score-circle {
        width: 64px; height: 64px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; font-weight: 800;
        flex-shrink: 0;
    }
    .aith-e-score-high { background: rgba(34,197,94,0.15); color: #86efac; border: 2px solid rgba(34,197,94,0.3); }
    .aith-e-score-medium { background: rgba(245,158,11,0.15); color: #fcd34d; border: 2px solid rgba(245,158,11,0.3); }
    .aith-e-score-low { background: rgba(239,68,68,0.15); color: #fca5a5; border: 2px solid rgba(239,68,68,0.3); }
    .aith-e-score-info { flex: 1; }
    .aith-e-score-label { font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-bottom: 0.25rem; }
    .aith-e-score-text { font-size: 0.875rem; color: rgba(255,255,255,0.6); }

    .aith-e-section-card {
        padding: 1.25rem; border-radius: 0.75rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        margin-bottom: 1rem;
    }
    .aith-e-section-card-title {
        font-size: 0.9rem; font-weight: 600; color: rgba(255,255,255,0.7);
        margin-bottom: 0.75rem;
        display: flex; align-items: center; gap: 0.5rem;
    }

    .aith-e-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .aith-e-table th {
        text-align: left;
        padding: 0.5rem 0.75rem;
        font-size: 0.7rem; font-weight: 600;
        color: rgba(255,255,255,0.3);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .aith-e-table td {
        padding: 0.625rem 0.75rem;
        font-size: 0.8rem;
        color: rgba(255,255,255,0.6);
        border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .aith-e-table tr:last-child td { border-bottom: none; }

    .aith-e-list { list-style: none; padding: 0; margin: 0; }
    .aith-e-list li {
        padding: 0.5rem 0;
        font-size: 0.8rem; color: rgba(255,255,255,0.6);
        display: flex; align-items: flex-start; gap: 0.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.03);
    }
    .aith-e-list li:last-child { border-bottom: none; }
    .aith-e-list li .bullet { color: #7c3aed; flex-shrink: 0; }

    .aith-e-tag {
        display: inline-flex; padding: 0.2rem 0.5rem; border-radius: 9999px;
        font-size: 0.7rem; font-weight: 500;
    }
    .aith-e-tag-high { background: rgba(34,197,94,0.15); color: #86efac; }
    .aith-e-tag-medium { background: rgba(245,158,11,0.15); color: #fcd34d; }
    .aith-e-tag-low { background: rgba(239,68,68,0.15); color: #fca5a5; }
    .aith-e-tag-easy { background: rgba(34,197,94,0.15); color: #86efac; }
    .aith-e-tag-hard { background: rgba(239,68,68,0.15); color: #fca5a5; }
    .aith-e-tag-active { background: rgba(34,197,94,0.15); color: #86efac; }
    .aith-e-tag-inactive { background: rgba(239,68,68,0.15); color: #fca5a5; }
    .aith-e-tag-underutilized { background: rgba(245,158,11,0.15); color: #fcd34d; }

    .aith-e-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    @media (max-width: 768px) { .aith-e-grid-2 { grid-template-columns: 1fr; } }

    /* Error state */
    .aith-e-error {
        padding: 1.5rem; border-radius: 0.75rem;
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.2);
        color: #fca5a5; font-size: 0.875rem;
        text-align: center;
    }
</style>
