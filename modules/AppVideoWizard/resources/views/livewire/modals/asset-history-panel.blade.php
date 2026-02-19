{{-- Asset History Panel — Slide-in right panel --}}
@if($showAssetHistoryPanel)
{{-- Backdrop --}}
<div wire:click="closeAssetHistory"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.3); backdrop-filter: blur(4px); z-index: 10000000; cursor: pointer;"></div>

{{-- Panel --}}
<div x-data="{ filterType: 'all' }"
     style="position: fixed; top: 0; right: 0; bottom: 0; width: 380px; max-width: 90vw; background: var(--vw-bg-surface); border-left: 2px solid var(--vw-border-accent); z-index: 10000001; display: flex; flex-direction: column; animation: assetHistorySlideIn 0.3s ease-out;">

    {{-- Header --}}
    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--vw-border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="margin: 0; color: var(--vw-text); font-size: 1rem; font-weight: 600;">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--vw-text-secondary); margin-right: 0.4rem;"></i>
                {{ __('Asset History') }}
            </h3>
            <p style="margin: 0.2rem 0 0 0; color: var(--vw-text-muted); font-size: 0.75rem;">
                @if(($assetHistoryTarget['type'] ?? '') === 'shot')
                    {{ __('Scene') }} {{ ($assetHistoryTarget['sceneIndex'] ?? 0) + 1 }}, {{ __('Shot') }} {{ ($assetHistoryTarget['shotIndex'] ?? 0) + 1 }}
                @else
                    {{ __('Scene') }} {{ ($assetHistoryTarget['sceneIndex'] ?? 0) + 1 }}
                @endif
            </p>
        </div>
        <button type="button" wire:click="closeAssetHistory" style="background: none; border: none; color: var(--vw-text); font-size: 1.3rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
    </div>

    {{-- Filter Tabs --}}
    <div style="padding: 0.65rem 1.25rem; display: flex; gap: 0.35rem; border-bottom: 1px solid var(--vw-border);">
        @foreach(['all' => __('All'), 'image' => __('Images'), 'video' => __('Videos')] as $filterKey => $filterLabel)
            <button type="button"
                    @click="filterType = '{{ $filterKey }}'"
                    :style="filterType === '{{ $filterKey }}'
                        ? 'padding: 0.3rem 0.65rem; border-radius: 2rem; border: 1px solid rgba(var(--vw-primary-rgb),0.4); background: rgba(var(--vw-primary-rgb),0.15); color: var(--vw-text-secondary); font-size: 0.75rem; cursor: pointer;'
                        : 'padding: 0.3rem 0.65rem; border-radius: 2rem; border: 1px solid var(--vw-border); background: none; color: var(--vw-text-muted); font-size: 0.75rem; cursor: pointer;'">
                {{ $filterLabel }}
            </button>
        @endforeach
    </div>

    {{-- History Items --}}
    <div style="flex: 1; overflow-y: auto; padding: 0.75rem 1rem;">
        @php
            $history = $this->getAssetHistoryForTarget();
            $reversedHistory = array_reverse($history);
        @endphp

        @if(empty($history))
            {{-- Empty State --}}
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; color: var(--vw-text-muted);">
                <i class="fa-solid fa-clock" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p style="margin: 0; font-size: 0.9rem;">{{ __('No history yet') }}</p>
                <p style="margin: 0.3rem 0 0 0; font-size: 0.75rem;">{{ __('Generate or edit images to build history') }}</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach($reversedHistory as $entry)
                    @php
                        $entryType = $entry['type'] ?? 'image';
                        $isActive = $entry['isActive'] ?? false;
                        $actionLabel = match($entry['action'] ?? 'generated') {
                            'generated' => __('Generated'),
                            'regenerated' => __('Regenerated'),
                            'edited' => __('Edited'),
                            'reimagined' => __('Reimagined'),
                            'uploaded' => __('Uploaded'),
                            'stock' => __('Stock'),
                            'animated' => __('Animated'),
                            'restored' => __('Restored'),
                            default => ucfirst($entry['action'] ?? 'Unknown'),
                        };
                        $actionColor = match($entry['action'] ?? 'generated') {
                            'generated', 'regenerated' => '#16a34a',
                            'edited' => '#71717a',
                            'reimagined' => '#db2777',
                            'uploaded' => '#0284c7',
                            'stock' => '#2563eb',
                            'animated' => '#0891b2',
                            'restored' => '#d97706',
                            default => '#71717a',
                        };
                        $timestamp = $entry['timestamp'] ?? null;
                        $timeAgo = $timestamp ? \Carbon\Carbon::parse($timestamp)->diffForHumans() : '';
                    @endphp

                    <div x-show="filterType === 'all' || filterType === '{{ $entryType }}'"
                         style="background: {{ $isActive ? 'rgba(var(--vw-primary-rgb),0.08)' : 'var(--vw-bg-elevated)' }}; border: none; box-shadow: var(--vw-clay); border-radius: 0.5rem; overflow: hidden;">

                        {{-- Thumbnail --}}
                        <div style="position: relative;">
                            @if($entryType === 'video')
                                <div style="height: 120px; background: var(--vw-bg-elevated); display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-film" style="font-size: 2rem; color: rgba(6,182,212,0.5);"></i>
                                </div>
                            @else
                                <img src="{{ $entry['url'] ?? '' }}" alt="{{ $actionLabel }}"
                                     style="width: 100%; height: 120px; object-fit: cover; display: block;" loading="lazy">
                            @endif

                            {{-- Current badge --}}
                            @if($isActive)
                                <div style="position: absolute; top: 0.4rem; left: 0.4rem; padding: 0.15rem 0.45rem; background: rgba(var(--vw-primary-rgb),0.9); border-radius: 0.25rem; color: white; font-size: 0.65rem; font-weight: 600;">
                                    {{ __('Current') }}
                                </div>
                            @endif

                            {{-- Action badge --}}
                            <div style="position: absolute; top: 0.4rem; right: 0.4rem; padding: 0.15rem 0.45rem; background: {{ $actionColor }}30; border: 1px solid {{ $actionColor }}60; border-radius: 0.25rem; color: {{ $actionColor }}; font-size: 0.6rem; font-weight: 600;">
                                {{ $actionLabel }}
                            </div>
                        </div>

                        {{-- Info --}}
                        <div style="padding: 0.5rem 0.65rem;">
                            @if(!empty($entry['prompt']))
                                <p style="margin: 0 0 0.3rem 0; color: var(--vw-text-secondary); font-size: 0.72rem; line-height: 1.3; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    {{ Str::limit($entry['prompt'], 80) }}
                                </p>
                            @endif

                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                    @if(!empty($entry['model']))
                                        <span style="font-size: 0.6rem; color: var(--vw-text-muted);">{{ $entry['model'] }}</span>
                                    @endif
                                    <span style="font-size: 0.6rem; color: var(--vw-text-muted); opacity: 0.7;">{{ $timeAgo }}</span>
                                </div>

                                @if(!$isActive)
                                    <button type="button"
                                            wire:click="restoreAssetFromHistory('{{ $entry['id'] ?? '' }}')"
                                            style="padding: 0.25rem 0.5rem; background: rgba(var(--vw-primary-rgb),0.15); border: 1px solid rgba(var(--vw-primary-rgb),0.3); border-radius: 0.3rem; color: var(--vw-text-secondary); font-size: 0.65rem; cursor: pointer; font-weight: 500;"
                                            onmouseover="this.style.background='rgba(3,252,244,0.12)'"
                                            onmouseout="this.style.background='rgba(3,252,244,0.06)'">
                                        <i class="fa-solid fa-rotate-left" style="font-size: 0.55rem; margin-right: 0.2rem;"></i>
                                        {{ __('Restore') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
    @keyframes assetHistorySlideIn {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }
</style>
@endif
