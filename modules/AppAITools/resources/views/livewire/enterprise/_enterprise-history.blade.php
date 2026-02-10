{{-- Enterprise History Partial - Shared across all enterprise tools --}}
@if(count($history) > 0 && !$result)
<div class="aith-card" style="margin-top:1rem;">
    <div class="aith-eh-header">
        <div class="aith-e-section-card-title" style="margin-bottom:0;">
            <i class="fa-light fa-clock-rotate-left"></i> Recent Analyses
        </div>
        <span class="aith-eh-count">{{ count($history) }}</span>
    </div>

    <div class="aith-eh-list">
        @foreach($history as $i => $item)
        <div class="aith-eh-item" wire:click="loadHistoryItem({{ $i }})">
            <div class="aith-eh-item-left">
                {{-- Score Badge --}}
                @if($item['score'] !== null)
                @php $s = (int) $item['score']; @endphp
                <div class="aith-eh-score {{ $s >= 80 ? 'aith-eh-score-high' : ($s >= 50 ? 'aith-eh-score-mid' : 'aith-eh-score-low') }}">
                    {{ $s }}
                </div>
                @else
                <div class="aith-eh-score aith-eh-score-none">
                    <i class="fa-light fa-chart-simple" style="font-size:0.65rem;"></i>
                </div>
                @endif

                {{-- Info --}}
                <div class="aith-eh-info">
                    <div class="aith-eh-title">{{ \Illuminate\Support\Str::limit($item['title'], 55) }}</div>
                    <div class="aith-eh-meta">
                        @if(!empty($item['subtitle']))
                        <span class="aith-eh-subtitle">{{ $item['subtitle'] }}</span>
                        <span class="aith-eh-sep">&middot;</span>
                        @endif
                        <span>{{ $item['time_ago'] }}</span>
                        @if($item['credits'] > 0)
                        <span class="aith-eh-sep">&middot;</span>
                        <span><i class="fa-light fa-coins" style="font-size:0.6rem;"></i> {{ $item['credits'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="aith-eh-actions">
                <span class="aith-eh-load" title="Load result">
                    <i class="fa-light fa-arrow-rotate-left"></i>
                </span>
                <span class="aith-eh-delete" title="Delete"
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
    /* Enterprise History Styles */
    .aith-eh-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 0.75rem;
    }
    .aith-eh-count {
        padding: 0.15rem 0.5rem; border-radius: 9999px;
        background: rgba(139,92,246,0.15);
        color: #c4b5fd; font-size: 0.7rem; font-weight: 600;
    }
    .aith-eh-list {
        display: flex; flex-direction: column; gap: 0.25rem;
    }
    .aith-eh-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.625rem 0.75rem;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
        gap: 0.75rem;
    }
    .aith-eh-item:hover {
        background: rgba(139,92,246,0.08);
    }
    .aith-eh-item-left {
        display: flex; align-items: center; gap: 0.75rem;
        flex: 1; min-width: 0;
    }

    /* Score badge */
    .aith-eh-score {
        width: 2rem; height: 2rem; border-radius: 0.5rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.7rem; font-weight: 700;
        flex-shrink: 0;
    }
    .aith-eh-score-high { background: rgba(34,197,94,0.15); color: #86efac; border: 1px solid rgba(34,197,94,0.25); }
    .aith-eh-score-mid { background: rgba(245,158,11,0.15); color: #fcd34d; border: 1px solid rgba(245,158,11,0.25); }
    .aith-eh-score-low { background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.25); }
    .aith-eh-score-none { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.08); }

    /* Info */
    .aith-eh-info { min-width: 0; flex: 1; }
    .aith-eh-title {
        font-size: 0.8rem; font-weight: 500; color: rgba(255,255,255,0.7);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .aith-eh-meta {
        display: flex; align-items: center; gap: 0.375rem;
        font-size: 0.7rem; color: rgba(255,255,255,0.25);
        margin-top: 0.125rem;
    }
    .aith-eh-subtitle { color: rgba(255,255,255,0.35); }
    .aith-eh-sep { opacity: 0.4; }

    /* Actions */
    .aith-eh-actions {
        display: flex; align-items: center; gap: 0.25rem;
        flex-shrink: 0;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .aith-eh-item:hover .aith-eh-actions { opacity: 1; }
    .aith-eh-load, .aith-eh-delete {
        width: 1.75rem; height: 1.75rem;
        display: flex; align-items: center; justify-content: center;
        border-radius: 0.375rem;
        font-size: 0.7rem;
        transition: all 0.15s;
    }
    .aith-eh-load { color: rgba(255,255,255,0.3); }
    .aith-eh-load:hover { background: rgba(139,92,246,0.2); color: #c4b5fd; }
    .aith-eh-delete { color: rgba(255,255,255,0.2); }
    .aith-eh-delete:hover { background: rgba(239,68,68,0.15); color: #fca5a5; }

    @media (max-width: 576px) {
        .aith-eh-actions { opacity: 1; }
        .aith-eh-meta { flex-wrap: wrap; }
    }
</style>
@endif
