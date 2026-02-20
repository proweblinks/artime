@extends('layouts.app')

@section('css')
<style>
/* ============================================
   DASHBOARD — Aliases to shared design system
   ============================================ */

.dashboard-glass {
    /* Alias db-* to shared at-* tokens */
    --db-bg-deep: var(--at-bg-deep);
    --db-bg-surface: var(--at-bg-surface);
    --db-bg-surface-solid: var(--at-bg-surface-solid);
    --db-bg-elevated: var(--at-bg-elevated);
    --db-bg-hover: var(--at-bg-hover);
    --db-border: var(--at-border);
    --db-border-accent: var(--at-border-accent);
    --db-primary: var(--at-primary);
    --db-primary-hover: var(--at-primary-hover);
    --db-primary-soft: var(--at-primary-soft);
    --db-primary-text: var(--at-primary-text);
    --db-text-on-primary: var(--at-text-on-primary);
    --db-success: var(--at-success);
    --db-success-soft: var(--at-success-soft);
    --db-warning: var(--at-warning);
    --db-warning-soft: var(--at-warning-soft);
    --db-danger: var(--at-danger);
    --db-danger-soft: var(--at-danger-soft);
    --db-info: var(--at-info);
    --db-info-soft: var(--at-info-soft);
    --db-text: var(--at-text);
    --db-text-secondary: var(--at-text-secondary);
    --db-text-muted: var(--at-text-muted);
    --db-font: var(--at-font);
    --db-radius: var(--at-radius);
    --db-radius-lg: var(--at-radius-lg);
    --db-radius-sm: var(--at-radius-sm);
    --db-glass: var(--at-glass);
    --db-glass-hover: var(--at-glass-hover);
    --db-glass-sm: var(--at-glass-sm);
    --db-transition: var(--at-transition);

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

/* ── Dashboard-specific card z-index ── */
.dashboard-glass .card {
    position: relative;
    z-index: 1;
}

/* ── Dashboard text accents ── */
.dashboard-glass .db-text-accent {
    color: var(--db-primary-text) !important;
}

.dashboard-glass .db-text-secondary {
    color: var(--db-text-secondary) !important;
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

/* ── Dashboard-specific border overrides ── */
.dashboard-glass .card .border-end {
    border-right-color: rgba(255, 255, 255, 0.2) !important;
}

.dashboard-glass .border-gray-300 {
    border-color: rgba(255, 255, 255, 0.25) !important;
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
