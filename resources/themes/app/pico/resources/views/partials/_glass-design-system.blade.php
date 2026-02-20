{{-- Platform Glass Design System --}}
{{-- Shared tokens + Bootstrap overrides for frosted glass aesthetic --}}
{{-- Loaded AFTER main.css in app.blade.php — wins by cascade order --}}

<style>
/* ============================================
   ARTIME GLASS DESIGN SYSTEM
   Frosted Glass — Cyan Accent (#03fcf4)
   Global Bootstrap Override Layer
   ============================================ */

/* --- A. Design Tokens --- */
:root {
    /* Backgrounds */
    --at-bg-deep: #f0f4f8;
    --at-bg-surface: rgba(255, 255, 255, 0.55);
    --at-bg-surface-solid: #ffffff;
    --at-bg-elevated: rgba(255, 255, 255, 0.35);
    --at-bg-hover: rgba(255, 255, 255, 0.65);
    --at-bg-input: rgba(255, 255, 255, 0.7);
    --at-bg-overlay: rgba(240, 244, 248, 0.85);

    /* Borders */
    --at-border: rgba(255, 255, 255, 0.35);
    --at-border-strong: rgba(0, 0, 0, 0.08);
    --at-border-accent: rgba(3, 252, 244, 0.2);
    --at-border-focus: #03fcf4;

    /* Primary accent (cyan) */
    --at-primary: #03fcf4;
    --at-primary-rgb: 3, 252, 244;
    --at-primary-hover: #00d4cc;
    --at-primary-soft: rgba(3, 252, 244, 0.08);
    --at-primary-glow: 0 0 0 3px rgba(3, 252, 244, 0.15);
    --at-primary-text: #0891b2;
    --at-primary-text-rgb: 8, 145, 178;
    --at-text-on-primary: #0a2e2e;

    /* Semantic */
    --at-success: #22c55e;
    --at-success-soft: rgba(34, 197, 94, 0.1);
    --at-warning: #f59e0b;
    --at-warning-soft: rgba(245, 158, 11, 0.1);
    --at-danger: #ef4444;
    --at-danger-soft: rgba(239, 68, 68, 0.08);
    --at-info: #0ea5e9;
    --at-info-soft: rgba(14, 165, 233, 0.08);

    /* Text */
    --at-text: #1a1a2e;
    --at-text-secondary: #5a6178;
    --at-text-muted: #94a0b8;
    --at-text-bright: #ffffff;

    /* Typography */
    --at-font: 'Inter', system-ui, -apple-system, sans-serif;

    /* Radius */
    --at-radius-sm: 0.625rem;
    --at-radius: 0.875rem;
    --at-radius-lg: 1.25rem;
    --at-radius-xl: 1.5rem;
    --at-radius-full: 9999px;

    /* Glass shadows */
    --at-glass:
        0 8px 32px rgba(0, 0, 0, 0.06),
        0 2px 8px rgba(0, 0, 0, 0.04);
    --at-glass-hover:
        0 12px 40px rgba(0, 0, 0, 0.08),
        0 4px 12px rgba(0, 0, 0, 0.05);
    --at-glass-sm:
        0 4px 12px rgba(0, 0, 0, 0.05),
        0 1px 3px rgba(0, 0, 0, 0.03);
    --at-glass-lg:
        0 16px 48px rgba(0, 0, 0, 0.08),
        0 4px 16px rgba(0, 0, 0, 0.05);
    --at-glass-btn:
        0 4px 16px rgba(0, 0, 0, 0.08),
        0 1px 4px rgba(0, 0, 0, 0.04);
    --at-glass-btn-hover:
        0 8px 24px rgba(0, 0, 0, 0.12),
        0 2px 6px rgba(0, 0, 0, 0.06);
    --at-glass-inset:
        inset 0 1px 3px rgba(0, 0, 0, 0.06),
        inset 0 0 0 1px rgba(255, 255, 255, 0.2);

    /* Transition */
    --at-transition: 200ms ease;
}


/* --- B. Body + Main Background --- */
body {
    background-color: var(--at-bg-deep) !important;
    font-family: var(--at-font);
}

.main {
    background: var(--at-bg-deep);
}


/* --- C. Sidebar Glass Override --- */
.sidebar {
    background-color: var(--at-bg-surface) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-right-color: var(--at-border) !important;
    box-shadow: 2px 0 16px rgba(0, 0, 0, 0.04);
}

/* Sidebar toggle button */
.sidebar .sidebar-toggle .bg-light,
.sidebar .sidebar-toggle a {
    background: var(--at-bg-elevated) !important;
    border-color: var(--at-border) !important;
}

/* Menu heading */
.sidebar .menu .menu-item .menu-heading {
    color: var(--at-text-muted);
}

/* Menu links — hover */
.sidebar .menu .menu-item .menu-link:hover {
    color: var(--at-primary-text) !important;
    background-color: rgba(3, 252, 244, 0.08) !important;
}

/* Menu links — active state */
.sidebar .menu .menu-item .menu-link.text-primary,
.sidebar .menu .menu-item .menu-link.active {
    background-color: rgba(3, 252, 244, 0.12) !important;
    color: var(--at-primary-text) !important;
}

/* Menu icon — active */
.sidebar .menu .menu-item .menu-link.text-primary .menu-icon,
.sidebar .menu .menu-item .menu-link.active .menu-icon {
    color: var(--at-primary-text) !important;
}

/* Menu title — active */
.sidebar .menu .menu-item .menu-link.text-primary .menu-title,
.sidebar .menu .menu-item .menu-link.active .menu-title {
    color: var(--at-primary-text) !important;
}

/* Menu bullet — active */
.sidebar .menu .menu-item.active .menu-link .menu-bullet,
.sidebar .menu .menu-item .menu-link.active .menu-bullet {
    background-color: var(--at-primary-text) !important;
}

/* Submenu active link */
.sidebar .menu .menu-accordion .menu-item .menu-link.active.text-primary {
    background-color: rgba(3, 252, 244, 0.12) !important;
    color: var(--at-primary-text) !important;
}

/* Sidebar dividers */
.sidebar .menu .menu-item.h-1.bg-gray-200 {
    background-color: var(--at-border) !important;
}

/* Sidebar accordion tree lines */
.sidebar .menu .menu-accordion:before {
    border-left-color: rgba(3, 252, 244, 0.15);
}

.sidebar .menu .menu-item .menu-item .menu-link:before {
    border-left-color: rgba(3, 252, 244, 0.15);
    border-bottom-color: rgba(3, 252, 244, 0.15);
}


/* --- D. Header Glass Override --- */
.header {
    background-color: var(--at-bg-surface) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom-color: var(--at-border) !important;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.04);
}

/* Sub-header bar */
.bg-polygon {
    background-color: var(--at-bg-elevated) !important;
    background-image: none !important;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom-color: var(--at-border) !important;
}


/* --- E. Bootstrap Component Overrides --- */

/* --- Text Primary --- */
.text-primary {
    color: var(--at-primary-text) !important;
}

/* --- Cards --- */
.card {
    background: var(--at-bg-surface) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--at-border) !important;
    border-radius: var(--at-radius-lg) !important;
    box-shadow: var(--at-glass);
    transition: box-shadow 200ms ease, border-color 200ms ease;
}

.card:hover {
    box-shadow: var(--at-glass-hover);
    border-color: rgba(255, 255, 255, 0.45) !important;
}

.card-header {
    background: transparent !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: var(--at-radius-lg) var(--at-radius-lg) 0 0 !important;
}

.card-body {
    color: var(--at-text);
}

.card .card {
    background: rgba(255, 255, 255, 0.4) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    border-radius: var(--at-radius) !important;
    box-shadow: var(--at-glass-sm);
}


/* --- Tables --- */
.table {
    color: var(--at-text);
    --bs-table-bg: transparent;
}

.table thead,
.table-light {
    background: rgba(255, 255, 255, 0.35) !important;
    --bs-table-bg: rgba(255, 255, 255, 0.35) !important;
}

.table thead th {
    border-bottom: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: var(--at-text-secondary);
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.table tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.3);
    --bs-table-bg-state: rgba(255, 255, 255, 0.3);
}

.table-bordered,
.table-bordered td,
.table-bordered th {
    border-color: rgba(255, 255, 255, 0.15) !important;
}


/* --- Buttons --- */
.btn-primary {
    background-color: var(--at-primary) !important;
    border-color: var(--at-primary) !important;
    color: var(--at-text-on-primary) !important;
    box-shadow: var(--at-glass-btn);
    transition: all 200ms ease;
}

.btn-primary:hover,
.btn-primary:focus {
    background-color: var(--at-primary-hover) !important;
    border-color: var(--at-primary-hover) !important;
    color: var(--at-text-on-primary) !important;
    box-shadow: var(--at-glass-btn-hover);
    transform: translateY(-1px);
}

.btn-primary:active {
    background-color: var(--at-primary-hover) !important;
    border-color: var(--at-primary-hover) !important;
    transform: translateY(0);
}

.btn-outline-primary {
    color: var(--at-primary-text) !important;
    border-color: var(--at-border-accent) !important;
}

.btn-outline-primary:hover {
    background-color: var(--at-primary-soft) !important;
    border-color: rgba(3, 252, 244, 0.35) !important;
    color: var(--at-primary-text) !important;
}

.btn-light {
    background: rgba(255, 255, 255, 0.5) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid var(--at-border) !important;
    color: var(--at-text-secondary) !important;
    box-shadow: var(--at-glass-sm);
}

.btn-light:hover {
    background: rgba(3, 252, 244, 0.08) !important;
    color: var(--at-primary-text) !important;
    border-color: var(--at-border-accent) !important;
}

.btn-success {
    background-color: var(--at-success) !important;
    border-color: var(--at-success) !important;
    box-shadow: var(--at-glass-btn);
}

.btn-success:hover {
    filter: brightness(1.05);
    box-shadow: var(--at-glass-btn-hover);
}

.btn-danger {
    background-color: var(--at-danger) !important;
    border-color: var(--at-danger) !important;
    box-shadow: var(--at-glass-btn);
}

.btn-warning {
    background-color: var(--at-warning) !important;
    border-color: var(--at-warning) !important;
    box-shadow: var(--at-glass-btn);
}

.btn-info {
    background-color: var(--at-info) !important;
    border-color: var(--at-info) !important;
    box-shadow: var(--at-glass-btn);
}


/* --- Form Controls --- */
.form-control:focus,
.form-select:focus {
    border-color: rgba(3, 252, 244, 0.4) !important;
    box-shadow: 0 0 0 3px rgba(3, 252, 244, 0.12) !important;
}

.form-check-input:checked {
    background-color: var(--at-primary) !important;
    border-color: var(--at-primary) !important;
}

.form-check-input:focus {
    border-color: rgba(3, 252, 244, 0.4) !important;
    box-shadow: 0 0 0 3px rgba(3, 252, 244, 0.12) !important;
}

/* Select2 focus */
.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--focus .select2-selection--multiple,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: rgba(3, 252, 244, 0.4) !important;
    box-shadow: 0 0 0 3px rgba(3, 252, 244, 0.12) !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--at-primary-soft) !important;
    color: var(--at-primary-text) !important;
}


/* --- Alerts --- */
.alert-primary {
    background: var(--at-primary-soft) !important;
    border: 1px solid var(--at-border-accent) !important;
    color: var(--at-primary-text) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.alert-success {
    background: var(--at-success-soft) !important;
    border: 1px solid rgba(34, 197, 94, 0.2) !important;
    color: #16a34a !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.alert-danger {
    background: var(--at-danger-soft) !important;
    border: 1px solid rgba(239, 68, 68, 0.15) !important;
    color: #dc2626 !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.alert-warning {
    background: var(--at-warning-soft) !important;
    border: 1px solid rgba(245, 158, 11, 0.2) !important;
    color: #92400e !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.alert-info {
    background: var(--at-info-soft) !important;
    border: 1px solid rgba(14, 165, 233, 0.2) !important;
    color: #0369a1 !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}


/* --- Badges --- */
.badge.bg-primary,
.bg-primary:not(.btn):not(.alert):not(.nav-link):not(.list-group-item) {
    background-color: rgba(3, 252, 244, 0.12) !important;
    color: var(--at-primary-text) !important;
    border: 1px solid rgba(3, 252, 244, 0.2);
}

.badge.bg-success {
    background-color: var(--at-success-soft) !important;
    color: #16a34a !important;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.badge.bg-danger {
    background-color: var(--at-danger-soft) !important;
    color: #dc2626 !important;
    border: 1px solid rgba(239, 68, 68, 0.15);
}

.badge.bg-warning {
    background-color: var(--at-warning-soft) !important;
    color: #d97706 !important;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.badge.bg-info {
    background-color: var(--at-info-soft) !important;
    color: #0284c7 !important;
    border: 1px solid rgba(14, 165, 233, 0.2);
}

.badge.rounded-pill {
    background: rgba(255, 255, 255, 0.5) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    box-shadow: var(--at-glass-sm);
    color: var(--at-primary-text) !important;
    font-weight: 600;
}


/* --- Dropdown Menu --- */
.dropdown-menu {
    background: var(--at-bg-surface-solid);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--at-border-strong);
    border-radius: var(--at-radius) !important;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.dropdown-item:hover,
.dropdown-item:focus {
    background: var(--at-primary-soft);
    color: var(--at-primary-text);
}

.dropdown-item.active,
.dropdown-item:active {
    background: rgba(3, 252, 244, 0.12);
    color: var(--at-primary-text);
}


/* --- Modal --- */
.modal-content {
    background: var(--at-bg-surface-solid);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--at-border);
    border-radius: var(--at-radius-lg) !important;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-bottom-color: var(--at-border) !important;
}

.modal-footer {
    border-top-color: var(--at-border) !important;
}


/* --- Progress Bars --- */
.progress {
    background: rgba(255, 255, 255, 0.3) !important;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-radius: var(--at-radius-full);
    overflow: hidden;
}

.progress-bar {
    background: linear-gradient(90deg, var(--at-primary), var(--at-primary-hover)) !important;
}


/* --- Pagination --- */
.page-link {
    color: var(--at-text-secondary);
    background: var(--at-bg-surface);
    border-color: var(--at-border);
}

.page-link:hover {
    color: var(--at-primary-text);
    background: var(--at-primary-soft);
    border-color: var(--at-border-accent);
}

.page-item.active .page-link {
    background-color: var(--at-primary) !important;
    border-color: var(--at-primary) !important;
    color: var(--at-text-on-primary) !important;
}


/* --- Nav Tabs / Pills --- */
.nav-tabs .nav-link.active {
    color: var(--at-primary-text) !important;
    border-bottom-color: var(--at-primary) !important;
}

.nav-pills .nav-link.active {
    background-color: var(--at-primary) !important;
    color: var(--at-text-on-primary) !important;
}

.nav-link:hover {
    color: var(--at-primary-text);
}


/* --- Borders --- */
.border-primary {
    border-color: var(--at-border-accent) !important;
}

.border-bottom {
    border-bottom-color: var(--at-border) !important;
}


/* --- Text Utilities Override --- */
.text-muted {
    color: var(--at-text-muted) !important;
}

.text-gray-500 {
    color: var(--at-text-muted) !important;
}

.text-gray-700 {
    color: var(--at-text-secondary) !important;
}

.text-gray-800 {
    color: var(--at-text) !important;
}

.text-gray-900 {
    color: var(--at-text) !important;
}

.text-dark {
    color: var(--at-text) !important;
}


/* --- Background Utilities --- */
.bg-light {
    background-color: var(--at-bg-elevated) !important;
}

.bg-white {
    background-color: var(--at-bg-surface) !important;
}


/* --- Highcharts transparent background --- */
.highcharts-background {
    fill: transparent !important;
}


/* --- List Group --- */
.list-group-item {
    background: var(--at-bg-surface);
    border-color: var(--at-border);
}

.list-group-item:hover {
    background: var(--at-bg-hover);
}

.list-group-item.active {
    background-color: var(--at-primary) !important;
    border-color: var(--at-primary) !important;
    color: var(--at-text-on-primary) !important;
}


/* --- Breadcrumb --- */
.breadcrumb-item a {
    color: var(--at-primary-text);
}

.breadcrumb-item.active {
    color: var(--at-text-muted);
}


/* --- iziToast overrides --- */
.iziToast-wrapper .iziToast {
    border-radius: var(--at-radius) !important;
}


/* --- SweetAlert2 overrides --- */
.swal2-popup {
    border-radius: var(--at-radius-lg) !important;
}

.swal2-confirm.swal2-styled {
    background-color: var(--at-primary) !important;
    color: var(--at-text-on-primary) !important;
    border: none !important;
    box-shadow: var(--at-glass-btn) !important;
}


/* --- DataTables overrides --- */
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--at-primary) !important;
    color: var(--at-text-on-primary) !important;
    border-color: var(--at-primary) !important;
    border-radius: var(--at-radius-sm);
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--at-primary-soft) !important;
    color: var(--at-primary-text) !important;
    border-color: var(--at-border-accent) !important;
}


/* --- Loading Spinner --- */
.loading .loader,
.app-loading div {
    border-color: rgba(3, 252, 244, 0.15);
    border-top-color: var(--at-primary);
}


/* --- F. Scrollbar Styling --- */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.2);
}

::-webkit-scrollbar-thumb {
    background: rgba(3, 252, 244, 0.25);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(3, 252, 244, 0.4);
}

/* Firefox scrollbar */
* {
    scrollbar-width: thin;
    scrollbar-color: rgba(3, 252, 244, 0.25) rgba(255, 255, 255, 0.2);
}
</style>
