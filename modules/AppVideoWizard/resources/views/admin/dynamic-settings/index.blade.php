@extends('layouts.app')

@push('styles')
<style>
/* ========================================
   MODERN COLOR PALETTE
   ======================================== */
:root {
    --vw-primary: #6366f1;
    --vw-primary-dark: #4f46e5;
    --vw-primary-light: #a5b4fc;
    --vw-secondary: #8b5cf6;
    --vw-success: #10b981;
    --vw-warning: #f59e0b;
    --vw-danger: #ef4444;
    --vw-info: #0ea5e9;
    --vw-dark: #1e293b;
    --vw-gray: #64748b;
    --vw-light: #f8fafc;
    --vw-border: #e2e8f0;
    --vw-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --vw-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
    --vw-shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}

/* ========================================
   PAGE CONTAINER
   ======================================== */
.settings-page-container {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: calc(100vh - 200px);
    padding-bottom: 2rem;
}

/* ========================================
   STATS CARDS - Modern Design
   ======================================== */
.stats-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: none;
    border-radius: 16px;
    box-shadow: var(--vw-shadow);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}
.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--vw-primary), var(--vw-secondary));
}
.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--vw-shadow-lg);
}
.stats-card .stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.stats-card .stats-icon.primary {
    background: linear-gradient(135deg, var(--vw-primary), var(--vw-secondary));
    color: white;
}
.stats-card .stats-icon.success {
    background: linear-gradient(135deg, var(--vw-success), #34d399);
    color: white;
}
.stats-card .stats-icon.info {
    background: linear-gradient(135deg, var(--vw-info), #38bdf8);
    color: white;
}
.stats-card .stats-value {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--vw-dark), var(--vw-gray));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.stats-card .stats-label {
    color: var(--vw-gray);
    font-size: 0.875rem;
    font-weight: 500;
}

/* ========================================
   SCROLLABLE TABS - Modern Design
   ======================================== */
.tabs-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 16px;
    box-shadow: var(--vw-shadow);
    padding: 1rem;
    border: 1px solid var(--vw-border);
}
.nav-tabs-scrollable {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    padding-bottom: 4px;
    gap: 0.5rem;
    border: none !important;
}
.nav-tabs-scrollable::-webkit-scrollbar {
    height: 6px;
}
.nav-tabs-scrollable::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}
.nav-tabs-scrollable::-webkit-scrollbar-thumb {
    background: linear-gradient(90deg, var(--vw-primary), var(--vw-secondary));
    border-radius: 3px;
}
.nav-tabs-scrollable .nav-item {
    flex-shrink: 0;
}
.nav-tabs-scrollable .nav-link {
    white-space: nowrap;
    padding: 0.625rem 1rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--vw-gray);
    background: #f1f5f9;
    border: 2px solid transparent;
    border-radius: 10px;
    transition: all 0.25s ease;
}
.nav-tabs-scrollable .nav-link:hover {
    color: var(--vw-primary);
    background: #e0e7ff;
    border-color: transparent;
}
.nav-tabs-scrollable .nav-link.active {
    color: white;
    background: linear-gradient(135deg, var(--vw-primary), var(--vw-secondary));
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}
.nav-tabs-scrollable .nav-link .badge {
    font-size: 0.65rem;
    padding: 3px 6px;
    border-radius: 6px;
    background: rgba(255,255,255,0.2);
}
.nav-tabs-scrollable .nav-link.active .badge {
    background: rgba(255,255,255,0.3);
    color: white;
}

/* ========================================
   CATEGORY CARD WRAPPER
   ======================================== */
.category-card {
    background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
    border: 1px solid var(--vw-border);
    border-radius: 20px;
    box-shadow: var(--vw-shadow);
    overflow: hidden;
}
.category-card-header {
    background: linear-gradient(135deg, var(--vw-primary) 0%, var(--vw-secondary) 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    border: none;
}
.category-card-header h5 {
    margin: 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.category-card-header h5 i {
    font-size: 1.25rem;
    opacity: 0.9;
}
.category-card-header .btn-outline-secondary {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    font-size: 0.8125rem;
    padding: 0.375rem 0.875rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}
.category-card-header .btn-outline-secondary:hover {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
    color: white;
}
.category-card-body {
    padding: 1.5rem;
    background: #fafbfc;
}

/* ========================================
   TWO-COLUMN LAYOUT
   ======================================== */
.settings-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -12px;
}
.settings-row > .col-md-6 {
    padding: 0 12px;
    margin-bottom: 24px !important;
}

/* ========================================
   SETTING CARD - Clear Section Design
   ======================================== */
.setting-card {
    background: #ffffff;
    border: 1px solid var(--vw-border);
    border-radius: 12px;
    padding: 0 !important;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.setting-card:hover {
    border-color: var(--vw-primary-light);
    box-shadow: var(--vw-shadow);
}
.setting-card.bg-light {
    border-style: dashed;
    border-color: #cbd5e1;
}

/* ========================================
   SETTING HEADER - Colored Top Bar
   ======================================== */
.setting-card .setting-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid var(--vw-border);
}
.setting-card:hover .setting-header {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
}
.setting-card.bg-light .setting-header {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}
.setting-card .form-label {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0;
    line-height: 1.4;
    color: var(--vw-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}
.setting-card .form-label i {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--vw-primary), var(--vw-secondary));
    color: white;
    border-radius: 6px;
    font-size: 0.75rem;
}
.setting-card.bg-light .form-label i {
    background: linear-gradient(135deg, var(--vw-gray), #94a3b8);
}
.setting-card .form-label .badge {
    font-size: 0.625rem;
    padding: 3px 8px;
    font-weight: 600;
    border-radius: 6px;
    background: linear-gradient(135deg, var(--vw-gray), #94a3b8);
    color: white;
}

/* ========================================
   SETTING BODY - Main Content Area
   ======================================== */
.setting-card .setting-body {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* ========================================
   DESCRIPTION TEXT - Boxed Style
   ======================================== */
.setting-card .setting-description {
    font-size: 0.8125rem;
    line-height: 1.6;
    color: var(--vw-gray);
    margin-bottom: 14px;
    padding: 10px 12px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 3px solid var(--vw-primary-light);
}

/* ========================================
   INPUT CONTAINER - Visual Grouping
   ======================================== */
.setting-card .input-container {
    background: #ffffff;
    border: 1px solid var(--vw-border);
    border-radius: 8px;
    padding: 12px;
}
.setting-card .input-label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--vw-gray);
    margin-bottom: 8px;
}

/* ========================================
   INPUT FIELDS - Clean Styling
   ======================================== */
.setting-card .form-control,
.setting-card .form-select {
    height: 42px;
    padding: 10px 12px;
    font-size: 0.875rem;
    border: 1px solid var(--vw-border);
    border-radius: 8px;
    background-color: #fff;
    transition: all 0.2s ease;
}
.setting-card .form-control:hover,
.setting-card .form-select:hover {
    border-color: var(--vw-primary-light);
    background-color: #fafbff;
}
.setting-card .form-control:focus,
.setting-card .form-select:focus {
    border-color: var(--vw-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    background-color: #fff;
}
.setting-card textarea.form-control {
    height: auto;
    min-height: 68px;
    resize: vertical;
    line-height: 1.5;
}

/* Input Group */
.setting-card .input-group {
    display: flex;
    align-items: stretch;
}
.setting-card .input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}
.setting-card .input-group-text {
    height: 42px;
    padding: 10px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--vw-primary);
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    border: 1px solid var(--vw-border);
    border-left: none;
    border-radius: 0 8px 8px 0;
    display: flex;
    align-items: center;
    white-space: nowrap;
}

/* ========================================
   TOGGLE SWITCH - Boxed Design
   ======================================== */
.setting-card .form-check.form-switch {
    padding: 12px 14px 12px 60px;
    min-height: 44px;
    display: flex;
    align-items: center;
    margin: 0;
    background: linear-gradient(135deg, #fef2f2, #fce7f3);
    border: 1px solid #fecdd3;
    border-radius: 8px;
}
.setting-card .form-check.form-switch:has(.form-check-input:checked) {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border-color: #a7f3d0;
}
.setting-card .form-check-input {
    width: 44px;
    height: 24px;
    margin-left: -46px;
    margin-top: 0;
    cursor: pointer;
    border: 1px solid #fca5a5;
    background-color: #fecaca;
    transition: all 0.25s ease;
}
.setting-card .form-check-input:checked {
    background-color: var(--vw-success);
    border-color: var(--vw-success);
}
.setting-card .form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}
.setting-card .form-check-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--vw-dark);
    cursor: pointer;
}

/* ========================================
   SELECT / DROPDOWN
   ======================================== */
.setting-card .form-select {
    padding-right: 36px;
    background-position: right 12px center;
    background-size: 12px;
    cursor: pointer;
}

/* ========================================
   INPUT HINT - Inline format indicator
   ======================================== */
.setting-card .input-hint {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 6px;
    padding: 4px 10px;
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #7c3aed;
    background: linear-gradient(135deg, #ede9fe, #ddd6fe);
    border-radius: 4px;
}
.setting-card .input-hint i {
    font-size: 0.625rem;
}

/* ========================================
   STATUS BADGE - API Key status etc.
   ======================================== */
.setting-card .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 6px;
}
.setting-card .status-badge i {
    font-size: 0.875rem;
}
.setting-card .status-badge.status-success {
    color: #065f46;
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border: 1px solid #6ee7b7;
}
.setting-card .status-badge.status-warning {
    color: #92400e;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 1px solid #fcd34d;
}

/* ========================================
   SETTING FOOTER - Info Section
   ======================================== */
.setting-card .setting-footer {
    margin-top: auto;
    padding: 12px 16px;
    background: #f8fafc;
    border-top: 1px solid var(--vw-border);
}

/* ========================================
   HELP TEXT - In Footer
   ======================================== */
.setting-card .setting-help {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 0.75rem;
    line-height: 1.5;
    color: var(--vw-gray);
    margin: 0;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: 0;
}
.setting-card .setting-help i {
    color: var(--vw-info);
    margin-top: 2px;
    flex-shrink: 0;
}
.setting-card .setting-help.text-success {
    color: var(--vw-success) !important;
}
.setting-card .setting-help.text-success i {
    color: var(--vw-success);
}
.setting-card .setting-help.text-warning {
    color: var(--vw-warning) !important;
}
.setting-card .setting-help.text-warning i {
    color: var(--vw-warning);
}

/* Divider between help items */
.setting-card .setting-footer .setting-help + .setting-help,
.setting-card .setting-footer .setting-help + .setting-default,
.setting-card .setting-footer .setting-default + .setting-help {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed #e2e8f0;
}

/* Default value indicator */
.setting-card .setting-default {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 0.75rem;
    line-height: 1.5;
    color: #b45309;
    margin: 0;
    padding: 0;
    background: transparent;
    border: none;
}
.setting-card .setting-default i {
    color: #d97706;
    margin-top: 2px;
    flex-shrink: 0;
}

/* ========================================
   PASSWORD INPUT SPECIAL
   ======================================== */
.setting-card .input-group .btn-outline-secondary {
    border: 1px solid var(--vw-border);
    border-left: none;
    border-radius: 0 8px 8px 0;
    background: #f8fafc;
    color: var(--vw-gray);
    transition: all 0.2s ease;
}
.setting-card .input-group .btn-outline-secondary:hover {
    background: var(--vw-primary);
    border-color: var(--vw-primary);
    color: white;
}

/* ========================================
   SAVE BUTTON AREA
   ======================================== */
.save-settings-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 2px solid var(--vw-border);
    border-radius: 16px;
    box-shadow: var(--vw-shadow);
}
.save-settings-card .btn-primary {
    background: linear-gradient(135deg, var(--vw-primary), var(--vw-secondary));
    border: none;
    padding: 0.875rem 2rem;
    font-weight: 600;
    font-size: 1rem;
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
    transition: all 0.3s ease;
}
.save-settings-card .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
}
.save-settings-card .btn-primary:active {
    transform: translateY(0);
}

/* ========================================
   RESPONSIVE ADJUSTMENTS
   ======================================== */
@media (max-width: 768px) {
    .settings-row > .col-md-6 {
        margin-bottom: 16px !important;
    }
    .setting-card .setting-body {
        padding: 12px;
    }
    .setting-card .setting-footer {
        padding: 10px 12px;
    }
    .category-card-header {
        padding: 1rem;
    }
    .category-card-body {
        padding: 1rem;
    }
}

/* ========================================
   ANIMATIONS
   ======================================== */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.tab-pane.show {
    animation: fadeInUp 0.3s ease;
}
</style>
@endpush

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Dynamic Settings') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Video Wizard Settings') }}</div>
                <p class="text-muted mb-0 small">{{ __('Configure AI providers, API endpoints, credit costs, shot intelligence, animation, and more') }}</p>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('admin.video-wizard.dynamic-settings.seed-defaults') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('{{ __('This will add any missing default settings. Existing values will be preserved. Continue?') }}')">
                        <i class="fa fa-database me-1"></i> {{ __('Seed Defaults') }}
                    </button>
                </form>
                <form action="{{ route('admin.video-wizard.dynamic-settings.reset-all') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('{{ __('Reset ALL settings to their default values? This cannot be undone.') }}')">
                        <i class="fa fa-undo me-1"></i> {{ __('Reset All') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container py-4 settings-page-container">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Overview - Modern Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="stats-icon primary me-4">
                        <i class="fa fa-cog"></i>
                    </div>
                    <div>
                        <div class="stats-value">{{ $stats['total'] }}</div>
                        <div class="stats-label">{{ __('Total Settings') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="stats-icon success me-4">
                        <i class="fa fa-check"></i>
                    </div>
                    <div>
                        <div class="stats-value">{{ $stats['active'] }}</div>
                        <div class="stats-label">{{ __('Active Settings') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="stats-icon info me-4">
                        <i class="fa fa-layer-group"></i>
                    </div>
                    <div>
                        <div class="stats-value">{{ $stats['categories'] }}</div>
                        <div class="stats-label">{{ __('Categories') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form action="{{ route('admin.video-wizard.dynamic-settings.update') }}" method="POST">
        @csrf

        <!-- Category Tabs (Scrollable) - Modern Design -->
        <div class="tabs-container mb-4">
            <ul class="nav nav-tabs nav-tabs-scrollable" id="settingsTabs" role="tablist">
                    @foreach($categories as $categorySlug => $categoryName)
                        @if(isset($settingsByCategory[$categorySlug]) && $settingsByCategory[$categorySlug]->count() > 0)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                        id="tab-{{ $categorySlug }}"
                                        data-bs-toggle="tab"
                                        data-bs-target="#content-{{ $categorySlug }}"
                                        type="button"
                                        role="tab">
                                    <i class="{{ $categoryIcons[$categorySlug] ?? 'fa fa-cog' }} me-1"></i>
                                    {{ $categoryName }}
                                    <span class="badge bg-secondary ms-1">{{ $settingsByCategory[$categorySlug]->count() }}</span>
                                </button>
                            </li>
                        @endif
                    @endforeach
                </ul>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="settingsTabContent">
            @foreach($categories as $categorySlug => $categoryName)
                @if(isset($settingsByCategory[$categorySlug]) && $settingsByCategory[$categorySlug]->count() > 0)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                         id="content-{{ $categorySlug }}"
                         role="tabpanel">

                        <div class="category-card mb-4">
                            <div class="category-card-header d-flex justify-content-between align-items-center">
                                <h5>
                                    <i class="{{ $categoryIcons[$categorySlug] ?? 'fa fa-cog' }}"></i>
                                    {{ $categoryName }}
                                </h5>
                                <form action="{{ route('admin.video-wizard.dynamic-settings.reset-category', $categorySlug) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('{{ __('Reset all settings in this category to defaults?') }}')">
                                        <i class="fa fa-undo me-1"></i> {{ __('Reset Category') }}
                                    </button>
                                </form>
                            </div>
                            <div class="category-card-body">
                                <div class="row settings-row">
                                    @foreach($settingsByCategory[$categorySlug] as $setting)
                                        <div class="col-md-6">
                                            <div class="setting-card {{ $setting->is_system ? 'bg-light' : '' }}">
                                                {{-- HEADER SECTION --}}
                                                <div class="setting-header">
                                                    <label class="form-label" for="setting-{{ $setting->slug }}">
                                                        @if($setting->icon)
                                                            <i class="{{ $setting->icon }}"></i>
                                                        @else
                                                            <i class="fa fa-sliders"></i>
                                                        @endif
                                                        {{ $setting->name }}
                                                        @if($setting->is_system)
                                                            <span class="badge bg-secondary" title="{{ __('System setting') }}">
                                                                <i class="fa fa-lock"></i>
                                                            </span>
                                                        @endif
                                                    </label>
                                                </div>

                                                {{-- BODY SECTION --}}
                                                <div class="setting-body">
                                                    @if($setting->description)
                                                        <p class="setting-description">{{ $setting->description }}</p>
                                                    @endif

                                                    @php
                                                        $currentValue = $setting->getTypedValue() ?? $setting->getTypedDefaultValue();
                                                    @endphp

                                                    {{-- Input based on type --}}
                                                @switch($setting->input_type)
                                                    @case('checkbox')
                                                        <div class="form-check form-switch">
                                                            <input type="hidden" name="settings[{{ $setting->slug }}]" value="0">
                                                            <input class="form-check-input"
                                                                   type="checkbox"
                                                                   id="setting-{{ $setting->slug }}"
                                                                   name="settings[{{ $setting->slug }}]"
                                                                   value="1"
                                                                   {{ $currentValue ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="setting-{{ $setting->slug }}">
                                                                {{ $currentValue ? __('Enabled') : __('Disabled') }}
                                                            </label>
                                                        </div>
                                                        @break

                                                    @case('select')
                                                        <select class="form-select"
                                                                id="setting-{{ $setting->slug }}"
                                                                name="settings[{{ $setting->slug }}]">
                                                            @php
                                                                $allowedValues = $setting->allowed_values;
                                                                if (is_string($allowedValues)) {
                                                                    $allowedValues = json_decode($allowedValues, true) ?? [];
                                                                }
                                                            @endphp
                                                            @if(!empty($allowedValues) && is_array($allowedValues))
                                                                @foreach($allowedValues as $option)
                                                                    <option value="{{ $option }}" {{ $currentValue == $option ? 'selected' : '' }}>
                                                                        {{ is_string($option) ? ucfirst($option) : $option }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                        @break

                                                    @case('number')
                                                        <div class="input-group">
                                                            <input type="number"
                                                                   class="form-control"
                                                                   id="setting-{{ $setting->slug }}"
                                                                   name="settings[{{ $setting->slug }}]"
                                                                   value="{{ $currentValue }}"
                                                                   @if($setting->min_value !== null) min="{{ $setting->min_value }}" @endif
                                                                   @if($setting->max_value !== null) max="{{ $setting->max_value }}" @endif
                                                                   placeholder="{{ $setting->input_placeholder }}">
                                                            @if($setting->min_value !== null && $setting->max_value !== null)
                                                                <span class="input-group-text text-muted small">
                                                                    {{ $setting->min_value }}-{{ $setting->max_value }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        @break

                                                    @case('password')
                                                        <div class="input-group">
                                                            <input type="password"
                                                                   class="form-control"
                                                                   id="setting-{{ $setting->slug }}"
                                                                   name="settings[{{ $setting->slug }}]"
                                                                   value="{{ $currentValue }}"
                                                                   placeholder="{{ $setting->input_placeholder ?: '••••••••' }}"
                                                                   autocomplete="new-password">
                                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('setting-{{ $setting->slug }}')" title="{{ __('Toggle visibility') }}">
                                                                <i class="fa fa-eye" id="eye-setting-{{ $setting->slug }}"></i>
                                                            </button>
                                                        </div>
                                                        @if($currentValue)
                                                            <small class="status-badge status-success">
                                                                <i class="fa fa-check-circle"></i>
                                                                {{ __('API key is configured') }}
                                                            </small>
                                                        @else
                                                            <small class="status-badge status-warning">
                                                                <i class="fa fa-exclamation-triangle"></i>
                                                                {{ __('Not configured') }}
                                                            </small>
                                                        @endif
                                                        @break

                                                    @case('textarea')
                                                        <textarea class="form-control font-monospace"
                                                                  id="setting-{{ $setting->slug }}"
                                                                  name="settings[{{ $setting->slug }}]"
                                                                  rows="6"
                                                                  placeholder="{{ $setting->input_placeholder }}">{{ is_array($currentValue) ? json_encode($currentValue, JSON_PRETTY_PRINT) : $currentValue }}</textarea>
                                                        @break

                                                    @case('json_editor')
                                                        <textarea class="form-control font-monospace"
                                                                  id="setting-{{ $setting->slug }}"
                                                                  name="settings[{{ $setting->slug }}]"
                                                                  rows="3"
                                                                  placeholder="{{ $setting->input_placeholder }}">{{ is_array($currentValue) ? json_encode($currentValue, JSON_PRETTY_PRINT) : $currentValue }}</textarea>
                                                        <small class="input-hint"><i class="fa fa-code"></i> {{ __('Enter valid JSON') }}</small>
                                                        @break

                                                    @default
                                                        @if(is_array($currentValue))
                                                            {{-- Array value - show as JSON textarea --}}
                                                            <textarea class="form-control font-monospace"
                                                                      id="setting-{{ $setting->slug }}"
                                                                      name="settings[{{ $setting->slug }}]"
                                                                      rows="4"
                                                                      placeholder="{{ $setting->input_placeholder }}">{{ json_encode($currentValue, JSON_PRETTY_PRINT) }}</textarea>
                                                            <small class="input-hint"><i class="fa fa-code"></i> {{ __('JSON format') }}</small>
                                                        @elseif(is_string($currentValue) && (strlen($currentValue) > 40 || str_contains($currentValue, ',')))
                                                            {{-- Long text or comma-separated values - show as textarea --}}
                                                            <textarea class="form-control"
                                                                      id="setting-{{ $setting->slug }}"
                                                                      name="settings[{{ $setting->slug }}]"
                                                                      rows="2"
                                                                      placeholder="{{ $setting->input_placeholder }}">{{ $currentValue }}</textarea>
                                                            @if(str_contains($currentValue ?? '', ','))
                                                                <small class="input-hint"><i class="fa fa-list"></i> {{ __('Comma-separated list') }}</small>
                                                            @endif
                                                        @else
                                                            <input type="text"
                                                                   class="form-control"
                                                                   id="setting-{{ $setting->slug }}"
                                                                   name="settings[{{ $setting->slug }}]"
                                                                   value="{{ $currentValue }}"
                                                                   placeholder="{{ $setting->input_placeholder }}">
                                                        @endif
                                                @endswitch
                                                </div>{{-- End .setting-body --}}

                                                {{-- FOOTER SECTION - Only show if has help or default info --}}
                                                @if($setting->input_help || ($setting->default_value && $setting->value !== $setting->default_value))
                                                <div class="setting-footer">
                                                    @if($setting->input_help)
                                                        <small class="setting-help">
                                                            <i class="fa fa-info-circle"></i>
                                                            <span>{{ $setting->input_help }}</span>
                                                        </small>
                                                    @endif

                                                    @if($setting->default_value && $setting->value !== $setting->default_value)
                                                        <small class="setting-default">
                                                            <i class="fa fa-exclamation-triangle"></i>
                                                            <span>{{ __('Default:') }} {{ is_array($setting->getTypedDefaultValue()) ? json_encode($setting->getTypedDefaultValue()) : $setting->getTypedDefaultValue() }}</span>
                                                        </small>
                                                    @endif
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Save Button - Modern Design -->
        <div class="card save-settings-card">
            <div class="card-body d-flex justify-content-between align-items-center p-4">
                <div class="text-muted d-flex align-items-center gap-2">
                    <i class="fa fa-info-circle text-info"></i>
                    {{ __('Changes take effect immediately after saving. Caches will be automatically cleared.') }}
                </div>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa fa-save me-2"></i>
                    {{ __('Save All Settings') }}
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Toggle checkbox label text
    document.querySelectorAll('.form-check-input').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (label) {
                label.textContent = this.checked ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}';
            }
        });
    });

    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const eyeIcon = document.getElementById('eye-' + inputId);
        if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }

    // JSON validation for json_editor inputs
    document.querySelectorAll('textarea[id^="setting-"]').forEach(textarea => {
        if (textarea.closest('.col-md-6')?.querySelector('small')?.textContent.includes('JSON')) {
            textarea.addEventListener('blur', function() {
                try {
                    if (this.value.trim()) {
                        JSON.parse(this.value);
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                } catch (e) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        }
    });
</script>
@endpush
@endsection
