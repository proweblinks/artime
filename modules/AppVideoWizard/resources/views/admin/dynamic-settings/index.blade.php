@extends('layouts.app')

@push('styles')
<style>
/* Scrollable tabs for many categories */
.nav-tabs-scrollable {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    padding-bottom: 2px;
}
.nav-tabs-scrollable::-webkit-scrollbar {
    height: 4px;
}
.nav-tabs-scrollable::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 2px;
}
.nav-tabs-scrollable .nav-item {
    flex-shrink: 0;
}
.nav-tabs-scrollable .nav-link {
    white-space: nowrap;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}
.nav-tabs-scrollable .nav-link .badge {
    font-size: 0.7rem;
}
/* Settings card improvements */
.card-body .col-md-6 {
    margin-bottom: 1.5rem !important;
}
.card-body .col-md-6 > .border {
    background: #fff;
    border-color: #e5e7eb !important;
    padding: 1rem !important;
}
.card-body .col-md-6 .form-label {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    word-break: break-word;
    line-height: 1.3;
}
.card-body .col-md-6 p.text-muted.small {
    font-size: 0.8rem;
    line-height: 1.5;
    margin-bottom: 0.75rem !important;
    color: #6b7280 !important;
}
/* Fix input text overflow */
.card-body .form-control,
.card-body .form-select {
    font-size: 0.875rem;
}
.card-body textarea.form-control {
    min-height: 60px;
    resize: vertical;
    line-height: 1.5;
}
/* Input group text size */
.card-body .input-group-text {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
/* Help text spacing */
.card-body small.text-muted {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    line-height: 1.4;
}
/* Checkbox switch alignment */
.card-body .form-check.form-switch {
    padding-top: 0.25rem;
}
/* Setting card specific */
.setting-card {
    display: flex;
    flex-direction: column;
}
.setting-card .form-control:last-of-type,
.setting-card .form-select:last-of-type,
.setting-card .form-check:last-of-type {
    margin-bottom: 0;
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

<div class="container py-4">
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

    <!-- Stats Overview -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fa fa-cog fa-lg text-primary"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['total'] }}</div>
                        <div class="text-muted small">{{ __('Total Settings') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fa fa-check fa-lg text-success"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['active'] }}</div>
                        <div class="text-muted small">{{ __('Active Settings') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fa fa-layer-group fa-lg text-info"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['categories'] }}</div>
                        <div class="text-muted small">{{ __('Categories') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form action="{{ route('admin.video-wizard.dynamic-settings.update') }}" method="POST">
        @csrf

        <!-- Category Tabs (Scrollable) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-2">
                <ul class="nav nav-tabs nav-tabs-scrollable border-0" id="settingsTabs" role="tablist">
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
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="settingsTabContent">
            @foreach($categories as $categorySlug => $categoryName)
                @if(isset($settingsByCategory[$categorySlug]) && $settingsByCategory[$categorySlug]->count() > 0)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                         id="content-{{ $categorySlug }}"
                         role="tabpanel">

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="{{ $categoryIcons[$categorySlug] ?? 'fa fa-cog' }} me-2 text-muted"></i>
                                        {{ $categoryName }}
                                    </h5>
                                </div>
                                <form action="{{ route('admin.video-wizard.dynamic-settings.reset-category', $categorySlug) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('{{ __('Reset all settings in this category to defaults?') }}')">
                                        <i class="fa fa-undo me-1"></i> {{ __('Reset Category') }}
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($settingsByCategory[$categorySlug] as $setting)
                                        <div class="col-md-6 mb-3">
                                            <div class="border rounded p-3 h-100 setting-card {{ $setting->is_system ? 'bg-light' : '' }}">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <label class="form-label fw-bold mb-0" for="setting-{{ $setting->slug }}">
                                                        @if($setting->icon)
                                                            <i class="{{ $setting->icon }} me-1 text-muted"></i>
                                                        @endif
                                                        {{ $setting->name }}
                                                        @if($setting->is_system)
                                                            <span class="badge bg-secondary ms-1" title="{{ __('System setting') }}">
                                                                <i class="fa fa-lock"></i>
                                                            </span>
                                                        @endif
                                                    </label>
                                                </div>

                                                @if($setting->description)
                                                    <p class="text-muted small mb-3">{{ $setting->description }}</p>
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
                                                            <small class="text-success d-block mt-1">
                                                                <i class="fa fa-check-circle me-1"></i>
                                                                {{ __('API key is configured') }}
                                                            </small>
                                                        @else
                                                            <small class="text-warning d-block mt-1">
                                                                <i class="fa fa-exclamation-triangle me-1"></i>
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
                                                        <small class="text-muted">{{ __('Enter valid JSON') }}</small>
                                                        @break

                                                    @default
                                                        @if(is_array($currentValue))
                                                            {{-- Array value - show as JSON textarea --}}
                                                            <textarea class="form-control font-monospace"
                                                                      id="setting-{{ $setting->slug }}"
                                                                      name="settings[{{ $setting->slug }}]"
                                                                      rows="4"
                                                                      placeholder="{{ $setting->input_placeholder }}">{{ json_encode($currentValue, JSON_PRETTY_PRINT) }}</textarea>
                                                            <small class="text-muted">{{ __('JSON format') }}</small>
                                                        @elseif(is_string($currentValue) && (strlen($currentValue) > 40 || str_contains($currentValue, ',')))
                                                            {{-- Long text or comma-separated values - show as textarea --}}
                                                            <textarea class="form-control"
                                                                      id="setting-{{ $setting->slug }}"
                                                                      name="settings[{{ $setting->slug }}]"
                                                                      rows="2"
                                                                      placeholder="{{ $setting->input_placeholder }}">{{ $currentValue }}</textarea>
                                                            @if(str_contains($currentValue ?? '', ','))
                                                                <small class="text-muted">{{ __('Comma-separated list') }}</small>
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

                                                @if($setting->input_help)
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="fa fa-info-circle me-1"></i>
                                                        {{ $setting->input_help }}
                                                    </small>
                                                @endif

                                                @if($setting->default_value && $setting->value !== $setting->default_value)
                                                    <small class="text-warning d-block mt-1">
                                                        <i class="fa fa-exclamation-triangle me-1"></i>
                                                        {{ __('Default:') }} {{ is_array($setting->getTypedDefaultValue()) ? json_encode($setting->getTypedDefaultValue()) : $setting->getTypedDefaultValue() }}
                                                    </small>
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

        <!-- Save Button -->
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    <i class="fa fa-info-circle me-1"></i>
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
