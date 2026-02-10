{{-- Tool History Partial - Shared across all regular tools --}}
@if(count($history) > 0)
<div id="aith-history-panel" class="aith-card" style="display:none; margin-top: 1rem;">
    <div class="aith-th-header">
        <h3 class="aith-section-title" style="margin:0;">
            <i class="fa-light fa-clock-rotate-left"></i> {{ __('Recent') }}
        </h3>
        <span class="aith-th-count">{{ count($history) }}</span>
    </div>

    <div class="aith-th-list">
        @foreach($history as $i => $item)
        <div class="aith-th-item" wire:click="loadHistoryItem({{ $i }})">
            <div class="aith-th-item-left">
                <div class="aith-th-info">
                    <div class="aith-th-title">{{ \Illuminate\Support\Str::limit($item['title'] ?? 'Untitled', 60) }}</div>
                    <div class="aith-th-meta">
                        @if(!empty($item['platform']))
                        <span class="aith-badge aith-badge-ghost">{{ $item['platform'] }}</span>
                        @endif
                        <span>{{ $item['time_ago'] }}</span>
                        @if(!empty($item['credits']) && $item['credits'] > 0)
                        <span class="aith-th-sep">&middot;</span>
                        <span><i class="fa-light fa-coins" style="font-size:0.6rem;"></i> {{ $item['credits'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="aith-th-actions">
                <span class="aith-th-load" title="{{ __('Load result') }}">
                    <i class="fa-light fa-arrow-rotate-left"></i>
                </span>
                <span class="aith-th-delete" title="{{ __('Delete') }}"
                      wire:click.stop="deleteHistoryItem({{ $i }})"
                      onclick="event.stopPropagation()">
                    <i class="fa-light fa-trash-can"></i>
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
<style>
    #aith-history-panel.aith-open { display: block !important; }

    .aith-th-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 0.75rem;
    }
    .aith-th-count {
        padding: 0.15rem 0.5rem; border-radius: 9999px;
        background: #f5f3ff; color: #7c3aed;
        font-size: 0.7rem; font-weight: 600;
    }
    .aith-th-list {
        display: flex; flex-direction: column; gap: 0.25rem;
    }
    .aith-th-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.625rem 0.75rem;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
        gap: 0.75rem;
    }
    .aith-th-item:hover {
        background: #f5f3ff;
    }
    .aith-th-item-left {
        display: flex; align-items: center; gap: 0.75rem;
        flex: 1; min-width: 0;
    }
    .aith-th-info { min-width: 0; flex: 1; }
    .aith-th-title {
        font-size: 0.8rem; font-weight: 500; color: #334155;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .aith-th-meta {
        display: flex; align-items: center; gap: 0.375rem;
        font-size: 0.7rem; color: #94a3b8;
        margin-top: 0.125rem;
    }
    .aith-th-sep { opacity: 0.4; }
    .aith-th-actions {
        display: flex; align-items: center; gap: 0.25rem;
        flex-shrink: 0;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .aith-th-item:hover .aith-th-actions { opacity: 1; }
    .aith-th-load, .aith-th-delete {
        width: 1.75rem; height: 1.75rem;
        display: flex; align-items: center; justify-content: center;
        border-radius: 0.375rem;
        font-size: 0.7rem;
        transition: all 0.15s;
    }
    .aith-th-load { color: #94a3b8; }
    .aith-th-load:hover { background: #f5f3ff; color: #7c3aed; }
    .aith-th-delete { color: #cbd5e1; }
    .aith-th-delete:hover { background: #fef2f2; color: #ef4444; }

    @media (max-width: 576px) {
        .aith-th-actions { opacity: 1; }
        .aith-th-meta { flex-wrap: wrap; }
    }
</style>
@endif
