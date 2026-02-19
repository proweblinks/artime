<div class="cs-app" x-data="{ section: @entangle('section') }">
    @include('appaicontents::livewire.partials._design-system')

    {{-- Gradient Mesh Background --}}
    <div class="cs-mesh-bg" aria-hidden="true">
        <div class="cs-mesh-blob cs-mesh-blob--cyan"></div>
        <div class="cs-mesh-blob cs-mesh-blob--teal"></div>
        <div class="cs-mesh-blob cs-mesh-blob--blue"></div>
    </div>

    <div class="cs-layout">
        {{-- Sidebar --}}
        @include('appaicontents::livewire.partials._sidebar')

        {{-- Main Content --}}
        <main class="cs-main {{ $sidebarCollapsed ? 'sidebar-collapsed' : '' }}">
            <div class="cs-content">
                @if($section === 'dna')
                    @livewire('app-ai-contents::business-dna', ['dnaId' => $dnaId], key('dna-' . ($dnaId ?? 'new')))

                @elseif($section === 'campaigns')
                    @if($activeCreativeId)
                        @livewire('app-ai-contents::creative-editor', ['creativeId' => $activeCreativeId], key('editor-' . $activeCreativeId))
                    @elseif($activeCampaignId)
                        @livewire('app-ai-contents::campaign-creatives', ['campaignId' => $activeCampaignId], key('creatives-' . $activeCampaignId))
                    @else
                        @livewire('app-ai-contents::campaigns-hub', ['dnaId' => $dnaId], key('campaigns-hub'))
                    @endif

                @elseif($section === 'photoshoot')
                    @livewire('app-ai-contents::photoshoot-hub', ['dnaId' => $dnaId], key('photoshoot-hub'))
                @endif
            </div>
        </main>
    </div>
</div>
