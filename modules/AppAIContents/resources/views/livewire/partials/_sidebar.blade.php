{{-- Content Studio Sidebar (Pomelli-style) --}}
<aside class="cs-sidebar {{ $sidebarCollapsed ? 'collapsed' : '' }}" x-data>
    {{-- Logo / Brand --}}
    <div class="cs-sidebar-header">
        <div class="cs-logo">
            <i class="fa-light fa-sparkles"></i>
        </div>
        <span class="cs-brand-text">Content Studio</span>
    </div>

    {{-- Navigation --}}
    <nav class="cs-sidebar-nav">
        <div class="cs-nav-item {{ $section === 'dna' ? 'active' : '' }}"
             wire:click="switchSection('dna')"
             title="{{ __('Business DNA') }}">
            <i class="fa-light fa-dna"></i>
            <span class="cs-nav-label">{{ __('Business DNA') }}</span>
        </div>

        <div class="cs-nav-item {{ $section === 'campaigns' ? 'active' : '' }}"
             wire:click="switchSection('campaigns')"
             title="{{ __('Campaigns') }}">
            <i class="fa-light fa-bullhorn"></i>
            <span class="cs-nav-label">{{ __('Campaigns') }}</span>
        </div>

        <div class="cs-nav-item {{ $section === 'photoshoot' ? 'active' : '' }}"
             wire:click="switchSection('photoshoot')"
             title="{{ __('Photoshoot') }}">
            <i class="fa-light fa-camera-retro"></i>
            <span class="cs-nav-label">{{ __('Photoshoot') }}</span>
        </div>
    </nav>

    {{-- Toggle Collapse --}}
    <div class="cs-sidebar-toggle">
        <button wire:click="toggleSidebar" title="{{ $sidebarCollapsed ? __('Expand sidebar') : __('Collapse sidebar') }}">
            <i class="fa-light {{ $sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left' }}"></i>
        </button>
    </div>
</aside>
