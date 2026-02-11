{{-- YouTube Connect Section (optional enrichment for TikTok tools) --}}
@php $fieldVar = $youtubeField ?? 'youtubeChannel'; @endphp
<div x-data="{ ytOpen: false }" class="aith-form-group" style="margin-top:0.25rem;">
    <button type="button" @click="ytOpen = !ytOpen"
            style="display:flex;align-items:center;gap:0.5rem;width:100%;padding:0.625rem 0.75rem;border-radius:0.5rem;background:rgba(255,0,0,0.06);border:1px solid rgba(255,0,0,0.15);color:rgba(255,255,255,0.7);font-size:0.8rem;cursor:pointer;transition:all 0.2s;"
            :style="ytOpen ? 'border-color:rgba(255,0,0,0.3);background:rgba(255,0,0,0.1)' : ''">
        <i class="fa-brands fa-youtube" style="color:#FF0000;font-size:0.9rem;"></i>
        <span>Connect YouTube Channel</span>
        <span style="margin-left:auto;font-size:0.7rem;color:rgba(255,255,255,0.35);">Optional</span>
        <i class="fa-light fa-chevron-down" style="font-size:0.65rem;transition:transform 0.2s;" :style="ytOpen ? 'transform:rotate(180deg)' : ''"></i>
    </button>
    <div x-show="ytOpen" x-collapse style="margin-top:0.5rem;">
        <div style="padding:0.75rem;border-radius:0.5rem;background:rgba(255,0,0,0.04);border:1px solid rgba(255,0,0,0.1);">
            <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin-bottom:0.5rem;line-height:1.4;">
                <i class="fa-light fa-circle-info" style="color:rgba(255,0,0,0.5);margin-right:0.25rem;"></i>
                Add a YouTube {{ isset($youtubeField) && $youtubeField === 'youtubeUrl' ? 'video URL' : 'channel URL' }} to enrich results with real YouTube API data.
            </div>
            <input type="text" wire:model="{{ $fieldVar }}" class="aith-input"
                   placeholder="{{ isset($youtubeField) && $youtubeField === 'youtubeUrl' ? 'https://youtube.com/watch?v=...' : 'https://youtube.com/@channel' }}"
                   style="font-size:0.85rem;">
        </div>
    </div>
</div>
