@extends('layouts.app')

@section('css')
<style>
/* ============================================
   DASHBOARD GLASS DESIGN SYSTEM
   Frosted glass aesthetic matching Video Wizard
   Scoped under .dashboard-glass wrapper
   ============================================ */

.dashboard-glass {
    /* Backgrounds */
    --db-bg-deep: #f0f4f8;
    --db-bg-surface: rgba(255, 255, 255, 0.55);
    --db-bg-surface-solid: #ffffff;
    --db-bg-elevated: rgba(255, 255, 255, 0.35);
    --db-bg-hover: rgba(255, 255, 255, 0.65);

    /* Borders */
    --db-border: rgba(255, 255, 255, 0.35);
    --db-border-accent: rgba(3, 252, 244, 0.2);

    /* Primary accent (cyan) */
    --db-primary: #03fcf4;
    --db-primary-hover: #00d4cc;
    --db-primary-soft: rgba(3, 252, 244, 0.08);
    --db-primary-text: #0891b2;
    --db-text-on-primary: #0a2e2e;

    /* Semantic colors */
    --db-success: #22c55e;
    --db-success-soft: rgba(34, 197, 94, 0.1);
    --db-warning: #f59e0b;
    --db-warning-soft: rgba(245, 158, 11, 0.1);
    --db-danger: #ef4444;
    --db-danger-soft: rgba(239, 68, 68, 0.08);
    --db-info: #0ea5e9;
    --db-info-soft: rgba(14, 165, 233, 0.08);

    /* Text */
    --db-text: #1a1a2e;
    --db-text-secondary: #5a6178;
    --db-text-muted: #94a0b8;

    /* Typography */
    --db-font: 'Inter', system-ui, -apple-system, sans-serif;

    /* Radius */
    --db-radius: 0.875rem;
    --db-radius-lg: 1.25rem;
    --db-radius-sm: 0.625rem;

    /* Glass shadows */
    --db-glass: 0 8px 32px rgba(0, 0, 0, 0.06), 0 2px 8px rgba(0, 0, 0, 0.04);
    --db-glass-hover: 0 12px 40px rgba(0, 0, 0, 0.08), 0 4px 12px rgba(0, 0, 0, 0.05);
    --db-glass-sm: 0 4px 12px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.03);

    /* Transition */
    --db-transition: 200ms ease;

    /* Apply base styles */
    font-family: var(--db-font);
    color: var(--db-text);
    background: var(--db-bg-deep);
    min-height: 100%;
    position: relative;
}

/* ── Gradient Mesh Background ── */
.db-mesh-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    pointer-events: none;
}

.db-mesh-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    will-change: transform;
}

.db-mesh-blob--cyan {
    width: 400px;
    height: 400px;
    background: #03fcf4;
    top: 10%;
    left: 20%;
    opacity: 0.12;
    animation: db-float-1 25s ease-in-out infinite;
}

.db-mesh-blob--teal {
    width: 350px;
    height: 350px;
    background: #14b8a6;
    bottom: 20%;
    right: 15%;
    opacity: 0.12;
    animation: db-float-2 22s ease-in-out infinite;
    animation-delay: -8s;
}

.db-mesh-blob--blue {
    width: 300px;
    height: 300px;
    background: #38bdf8;
    top: 60%;
    left: 5%;
    opacity: 0.10;
    animation: db-float-3 20s ease-in-out infinite;
    animation-delay: -5s;
}

@keyframes db-float-1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -20px) scale(1.05); }
    66% { transform: translate(-15px, 15px) scale(0.97); }
}

@keyframes db-float-2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(-25px, 20px) scale(0.95); }
    66% { transform: translate(20px, -10px) scale(1.03); }
}

@keyframes db-float-3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(15px, 25px) scale(1.02); }
    66% { transform: translate(-20px, -15px) scale(0.98); }
}

/* ── Glass Card Base ── */
.dashboard-glass .card {
    background: var(--db-bg-surface) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--db-border) !important;
    border-radius: var(--db-radius-lg) !important;
    box-shadow: var(--db-glass);
    transition: box-shadow var(--db-transition), border-color var(--db-transition);
    position: relative;
    z-index: 1;
}

.dashboard-glass .card:hover {
    box-shadow: var(--db-glass-hover);
    border-color: rgba(255, 255, 255, 0.45) !important;
}

.dashboard-glass .card-header {
    background: transparent !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: var(--db-text);
    font-weight: 600;
}

.dashboard-glass .card-body {
    color: var(--db-text);
}

/* ── Text Utility Classes ── */
.dashboard-glass .db-text-accent {
    color: var(--db-primary-text) !important;
}

.dashboard-glass .db-text-secondary {
    color: var(--db-text-secondary) !important;
}

/* Override Bootstrap text utilities inside glass scope */
.dashboard-glass .text-gray-500 {
    color: var(--db-text-muted) !important;
}

.dashboard-glass .text-gray-700 {
    color: var(--db-text-secondary) !important;
}

.dashboard-glass .text-gray-800 {
    color: var(--db-text) !important;
}

.dashboard-glass .text-muted {
    color: var(--db-text-muted) !important;
}

.dashboard-glass .text-dark {
    color: var(--db-text) !important;
}

/* ── Icon Containers ── */
.dashboard-glass .db-icon-container {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    min-width: 50px;
    border-radius: var(--db-radius);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: var(--db-glass-sm);
    font-size: 1.25rem;
}

.dashboard-glass .db-icon--cyan {
    background: rgba(3, 252, 244, 0.1);
    border: 1px solid rgba(3, 252, 244, 0.15);
    color: var(--db-primary-text);
}

.dashboard-glass .db-icon--success {
    background: var(--db-success-soft);
    border: 1px solid rgba(34, 197, 94, 0.2);
    color: #16a34a;
}

.dashboard-glass .db-icon--danger {
    background: var(--db-danger-soft);
    border: 1px solid rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

.dashboard-glass .db-icon--info {
    background: var(--db-info-soft);
    border: 1px solid rgba(14, 165, 233, 0.15);
    color: #0284c7;
}

.dashboard-glass .db-icon--teal {
    background: rgba(20, 184, 166, 0.1);
    border: 1px solid rgba(20, 184, 166, 0.15);
    color: #0d9488;
}

.dashboard-glass .db-icon--warning {
    background: var(--db-warning-soft);
    border: 1px solid rgba(245, 158, 11, 0.15);
    color: #d97706;
}

/* ── Glass Badges ── */
.dashboard-glass .badge.rounded-pill {
    background: rgba(255, 255, 255, 0.5) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    box-shadow: var(--db-glass-sm);
    color: var(--db-primary-text) !important;
    font-weight: 600;
}

.dashboard-glass .badge-success,
.dashboard-glass .badge.badge-success {
    background: var(--db-success-soft) !important;
    color: #16a34a !important;
    border: 1px solid rgba(34, 197, 94, 0.2) !important;
}

.dashboard-glass .badge-danger,
.dashboard-glass .badge.badge-danger {
    background: var(--db-danger-soft) !important;
    color: #dc2626 !important;
    border: 1px solid rgba(239, 68, 68, 0.15) !important;
}

/* ── Glass Progress Bar ── */
.dashboard-glass .progress {
    background: rgba(255, 255, 255, 0.3) !important;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    overflow: hidden;
}

/* ── Glass Table ── */
.dashboard-glass .table {
    color: var(--db-text);
    --bs-table-bg: transparent;
}

.dashboard-glass .table thead,
.dashboard-glass .table-light {
    background: rgba(255, 255, 255, 0.35) !important;
    --bs-table-bg: rgba(255, 255, 255, 0.35) !important;
}

.dashboard-glass .table thead th {
    border-bottom: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: var(--db-text-secondary);
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.dashboard-glass .table tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.dashboard-glass .table tbody tr:hover {
    background: rgba(255, 255, 255, 0.3);
    --bs-table-bg-state: rgba(255, 255, 255, 0.3);
}

.dashboard-glass .table-bordered,
.dashboard-glass .table-bordered td,
.dashboard-glass .table-bordered th {
    border-color: rgba(255, 255, 255, 0.15) !important;
}

/* ── Glass Buttons ── */
.dashboard-glass .btn-light {
    background: rgba(255, 255, 255, 0.5) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: var(--db-text-secondary);
}

.dashboard-glass .btn-light:hover {
    background: rgba(3, 252, 244, 0.08) !important;
    color: var(--db-primary-text);
    border-color: var(--db-border-accent) !important;
}

/* ── Card Borders / Dividers ── */
.dashboard-glass .card-body.border-bottom,
.dashboard-glass .card .border-bottom {
    border-bottom-color: rgba(255, 255, 255, 0.2) !important;
}

.dashboard-glass .card .border-end {
    border-right-color: rgba(255, 255, 255, 0.2) !important;
}

.dashboard-glass .border-gray-300 {
    border-color: rgba(255, 255, 255, 0.25) !important;
}

/* ── Highcharts Transparent Background ── */
.dashboard-glass .highcharts-background {
    fill: transparent !important;
}

/* ── Channel Card Nested Glass ── */
.dashboard-glass .card .card {
    background: rgba(255, 255, 255, 0.4) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    border-radius: var(--db-radius) !important;
    box-shadow: var(--db-glass-sm);
}

/* ── Alert Glass ── */
.dashboard-glass .alert-danger {
    background: var(--db-danger-soft) !important;
    border: 1px solid rgba(239, 68, 68, 0.15) !important;
    color: #dc2626;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/* ── Content z-index ── */
.dashboard-glass .container {
    position: relative;
    z-index: 1;
}
</style>
@endsection

@section('content')
    <div class="dashboard-glass">
        {{-- Gradient Mesh Background --}}
        <div class="db-mesh-bg" aria-hidden="true">
            <div class="db-mesh-blob db-mesh-blob--cyan"></div>
            <div class="db-mesh-blob db-mesh-blob--teal"></div>
            <div class="db-mesh-blob db-mesh-blob--blue"></div>
        </div>

        <div class="container py-5">
            @include("components.main-message")

            <div class="ajax-pages" data-url="{{ route('app.dashboard.statistics') }}" data-resp=".ajax-pages">
                <div class="pb-30 mt-200 ajax-scroll-loading">
                    <div class="app-loading mx-auto mt-10 pl-0 pr-0">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
