{{-- Scene DNA Overview Modal --}}
@if($showSceneDNAModal ?? false)
<div class="vw-modal-overlay"
     wire:key="scene-dna-modal"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 0.5rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(6,182,212,0.3); border-radius: 0.75rem; width: 100%; max-width: 1000px; max-height: 96vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 0.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: linear-gradient(90deg, rgba(6,182,212,0.1), rgba(139,92,246,0.1));">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: linear-gradient(135deg, #06b6d4, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        {{ __('Scene DNA') }}
                    </span>
                </h3>
                <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('Unified Bible data - single source of truth for each scene') }}</p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                @php
                    $sceneDNA = $sceneMemory['sceneDNA'] ?? [];
                    $isEnabled = $sceneDNA['enabled'] ?? false;
                @endphp
                @if(!$isEnabled)
                    <button type="button"
                            wire:click="buildSceneDNA"
                            wire:loading.attr="disabled"
                            wire:target="buildSceneDNA"
                            style="padding: 0.3rem 0.6rem; background: linear-gradient(135deg, #06b6d4, #8b5cf6); border: none; border-radius: 0.35rem; color: white; font-size: 0.65rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                        <span wire:loading.remove wire:target="buildSceneDNA">{{ __('Build Scene DNA') }}</span>
                        <span wire:loading wire:target="buildSceneDNA">{{ __('Building...') }}</span>
                    </button>
                @else
                    <button type="button"
                            wire:click="buildSceneDNA"
                            wire:loading.attr="disabled"
                            wire:target="buildSceneDNA"
                            style="padding: 0.3rem 0.6rem; background: rgba(6,182,212,0.2); border: 1px solid rgba(6,182,212,0.4); border-radius: 0.35rem; color: #67e8f9; font-size: 0.65rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                        <span wire:loading.remove wire:target="buildSceneDNA">{{ __('Rebuild') }}</span>
                        <span wire:loading wire:target="buildSceneDNA">{{ __('Rebuilding...') }}</span>
                    </button>
                @endif
                <button type="button" wire:click="$set('showSceneDNAModal', false)" style="background: none; border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
            </div>
        </div>

        {{-- Status Bar --}}
        @if($isEnabled)
            @php
                $summary = $this->getSceneDNASummary();
                $continuityIssues = $sceneDNA['continuityIssues'] ?? [];
                $affinities = $sceneDNA['characterAffinities'] ?? [];
            @endphp
            <div style="padding: 0.5rem 1rem; background: rgba(6,182,212,0.08); border-bottom: 1px solid rgba(6,182,212,0.2); display: flex; gap: 1.5rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.65rem;">{{ __('Scenes:') }}</span>
                    <span style="color: #67e8f9; font-weight: 600; font-size: 0.7rem;">{{ $summary['totalScenes'] ?? 0 }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.65rem;">{{ __('With Characters:') }}</span>
                    <span style="color: #a78bfa; font-weight: 600; font-size: 0.7rem;">{{ $summary['scenesWithCharacters'] ?? 0 }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.65rem;">{{ __('With Locations:') }}</span>
                    <span style="color: #34d399; font-weight: 600; font-size: 0.7rem;">{{ $summary['scenesWithLocations'] ?? 0 }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.65rem;">{{ __('Unique Characters:') }}</span>
                    <span style="color: #f472b6; font-weight: 600; font-size: 0.7rem;">{{ $summary['uniqueCharacters'] ?? 0 }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.65rem;">{{ __('Continuity Issues:') }}</span>
                    <span style="color: {{ count($continuityIssues) > 0 ? '#f97316' : '#10b981' }}; font-weight: 600; font-size: 0.7rem;">
                        {{ count($continuityIssues) }}
                    </span>
                </div>
                @if(!empty($sceneDNA['lastSyncedAt']))
                    <div style="margin-left: auto; display: flex; align-items: center; gap: 0.35rem;">
                        <span style="color: rgba(255,255,255,0.4); font-size: 0.6rem;">{{ __('Last synced:') }}</span>
                        <span style="color: rgba(255,255,255,0.6); font-size: 0.6rem;">{{ \Carbon\Carbon::parse($sceneDNA['lastSyncedAt'])->diffForHumans() }}</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 0.75rem;">
            @if(!$isEnabled)
                {{-- Not yet built state --}}
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; text-align: center;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(139,92,246,0.2)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <span style="font-size: 2rem; opacity: 0.8;">&#x1F9EC;</span>
                    </div>
                    <h4 style="color: white; margin: 0 0 0.5rem 0; font-size: 1rem;">{{ __('Scene DNA Not Built') }}</h4>
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.75rem; max-width: 400px; margin-bottom: 1rem;">
                        {{ __('Scene DNA combines your Character Bible, Location Bible, and Style Bible into a unified per-scene data structure. This enables:') }}
                    </p>
                    <ul style="color: rgba(255,255,255,0.7); font-size: 0.7rem; text-align: left; margin: 0 0 1.5rem 0; padding-left: 1.5rem; line-height: 1.8;">
                        <li>{{ __('Single source of truth for each scene') }}</li>
                        <li>{{ __('Automatic continuity validation') }}</li>
                        <li>{{ __('Character-location affinity tracking') }}</li>
                        <li>{{ __('More accurate image generation prompts') }}</li>
                    </ul>
                    <button type="button"
                            wire:click="buildSceneDNA"
                            wire:loading.attr="disabled"
                            wire:target="buildSceneDNA"
                            style="padding: 0.6rem 1.5rem; background: linear-gradient(135deg, #06b6d4, #8b5cf6); border: none; border-radius: 0.5rem; color: white; font-size: 0.8rem; cursor: pointer; font-weight: 600;">
                        <span wire:loading.remove wire:target="buildSceneDNA">{{ __('Build Scene DNA Now') }}</span>
                        <span wire:loading wire:target="buildSceneDNA">{{ __('Building...') }}</span>
                    </button>
                </div>
            @else
                {{-- Built state with tabs --}}
                @php
                    $activeTab = $sceneDNAActiveTab ?? 'overview';
                @endphp
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    {{-- Tabs --}}
                    <div style="display: flex; gap: 0.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">
                        <button type="button"
                                wire:click="$set('sceneDNAActiveTab', 'overview')"
                                style="padding: 0.4rem 0.8rem; background: {{ $activeTab === 'overview' ? 'rgba(6,182,212,0.2)' : 'transparent' }}; border: 1px solid {{ $activeTab === 'overview' ? 'rgba(6,182,212,0.4)' : 'transparent' }}; border-radius: 0.35rem; color: {{ $activeTab === 'overview' ? '#67e8f9' : 'rgba(255,255,255,0.6)' }}; font-size: 0.7rem; cursor: pointer;">
                            {{ __('Scene Overview') }}
                        </button>
                        <button type="button"
                                wire:click="$set('sceneDNAActiveTab', 'continuity')"
                                style="padding: 0.4rem 0.8rem; background: {{ $activeTab === 'continuity' ? 'rgba(6,182,212,0.2)' : 'transparent' }}; border: 1px solid {{ $activeTab === 'continuity' ? 'rgba(6,182,212,0.4)' : 'transparent' }}; border-radius: 0.35rem; color: {{ $activeTab === 'continuity' ? '#67e8f9' : 'rgba(255,255,255,0.6)' }}; font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; gap: 0.3rem;">
                            {{ __('Continuity') }}
                            @if(count($continuityIssues) > 0)
                                <span style="background: #f97316; color: white; padding: 0.1rem 0.35rem; border-radius: 999px; font-size: 0.55rem; font-weight: 600;">{{ count($continuityIssues) }}</span>
                            @endif
                        </button>
                        <button type="button"
                                wire:click="$set('sceneDNAActiveTab', 'affinities')"
                                style="padding: 0.4rem 0.8rem; background: {{ $activeTab === 'affinities' ? 'rgba(6,182,212,0.2)' : 'transparent' }}; border: 1px solid {{ $activeTab === 'affinities' ? 'rgba(6,182,212,0.4)' : 'transparent' }}; border-radius: 0.35rem; color: {{ $activeTab === 'affinities' ? '#67e8f9' : 'rgba(255,255,255,0.6)' }}; font-size: 0.7rem; cursor: pointer;">
                            {{ __('Character-Location Affinities') }}
                        </button>
                    </div>

                    {{-- Tab Content --}}
                    @if($activeTab === 'overview')
                        {{-- Scene Overview Grid --}}
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.5rem;">
                            @foreach(($sceneDNA['scenes'] ?? []) as $sceneIndex => $sceneData)
                                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; padding: 0.6rem; display: flex; flex-direction: column; gap: 0.4rem;">
                                    {{-- Scene Header --}}
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <div style="color: #67e8f9; font-size: 0.65rem; font-weight: 600;">{{ __('Scene') }} {{ $sceneIndex + 1 }}</div>
                                            <div style="color: white; font-size: 0.75rem; font-weight: 500; margin-top: 0.1rem;">{{ Str::limit($sceneData['sceneTitle'] ?? '', 30) }}</div>
                                        </div>
                                        <div style="display: flex; gap: 0.25rem;">
                                            @if(!empty($sceneData['characters']))
                                                <span style="background: rgba(167,139,250,0.2); color: #c4b5fd; padding: 0.15rem 0.35rem; border-radius: 0.25rem; font-size: 0.55rem;">
                                                    {{ count($sceneData['characters']) }} {{ __('char') }}
                                                </span>
                                            @endif
                                            @if(!empty($sceneData['location']))
                                                <span style="background: rgba(52,211,153,0.2); color: #6ee7b7; padding: 0.15rem 0.35rem; border-radius: 0.25rem; font-size: 0.55rem;">
                                                    {{ __('loc') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Characters --}}
                                    @if(!empty($sceneData['characterNames']))
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.2rem;">
                                            @foreach($sceneData['characterNames'] as $charName)
                                                <span style="background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); color: #a78bfa; padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.55rem;">
                                                    {{ $charName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Location --}}
                                    @if(!empty($sceneData['location']))
                                        <div style="display: flex; align-items: center; gap: 0.35rem; padding-top: 0.2rem; border-top: 1px solid rgba(255,255,255,0.05);">
                                            <span style="color: rgba(255,255,255,0.4); font-size: 0.55rem;">&#x1F4CD;</span>
                                            <span style="color: rgba(255,255,255,0.7); font-size: 0.6rem;">{{ $sceneData['location']['name'] ?? __('Unknown') }}</span>
                                            @if(!empty($sceneData['location']['timeOfDay']))
                                                <span style="color: rgba(255,255,255,0.4); font-size: 0.55rem;">{{ $sceneData['location']['timeOfDay'] }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Style indicator --}}
                                    @if(!empty($sceneData['style']))
                                        <div style="display: flex; align-items: center; gap: 0.25rem;">
                                            <span style="color: rgba(255,255,255,0.4); font-size: 0.55rem;">&#x1F3A8;</span>
                                            <span style="color: rgba(255,255,255,0.5); font-size: 0.55rem;">{{ __('Style applied') }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if(empty($sceneDNA['scenes']))
                            <div style="padding: 2rem; text-align: center; color: rgba(255,255,255,0.5); font-size: 0.75rem;">
                                {{ __('No scenes available. Add scenes to your script first.') }}
                            </div>
                        @endif

                    @elseif($activeTab === 'continuity')
                        {{-- Continuity Issues --}}
                        @if(count($continuityIssues) > 0)
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                @foreach($continuityIssues as $issue)
                                    <div style="background: rgba(249,115,22,0.1); border: 1px solid rgba(249,115,22,0.3); border-radius: 0.5rem; padding: 0.6rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.35rem;">
                                            <div style="display: flex; align-items: center; gap: 0.35rem;">
                                                @if(($issue['type'] ?? '') === 'character_teleport')
                                                    <span style="font-size: 0.8rem;">&#x1F3C3;</span>
                                                @elseif(($issue['type'] ?? '') === 'location_state_reset')
                                                    <span style="font-size: 0.8rem;">&#x1F504;</span>
                                                @elseif(($issue['type'] ?? '') === 'time_discontinuity')
                                                    <span style="font-size: 0.8rem;">&#x23F0;</span>
                                                @else
                                                    <span style="font-size: 0.8rem;">&#x26A0;</span>
                                                @endif
                                                <span style="color: #fdba74; font-weight: 600; font-size: 0.7rem;">
                                                    {{ ucfirst(str_replace('_', ' ', $issue['type'] ?? 'Issue')) }}
                                                </span>
                                            </div>
                                            <span style="color: rgba(255,255,255,0.5); font-size: 0.6rem;">
                                                {{ __('Scene') }} {{ ($issue['fromScene'] ?? 0) + 1 }} → {{ ($issue['toScene'] ?? 0) + 1 }}
                                            </span>
                                        </div>
                                        <p style="color: rgba(255,255,255,0.8); font-size: 0.65rem; margin: 0;">
                                            {{ $issue['description'] ?? '' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; text-align: center;">
                                <div style="width: 60px; height: 60px; background: rgba(16,185,129,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                    <span style="font-size: 1.5rem;">&#x2705;</span>
                                </div>
                                <h4 style="color: #10b981; margin: 0 0 0.35rem 0; font-size: 0.9rem;">{{ __('No Continuity Issues') }}</h4>
                                <p style="color: rgba(255,255,255,0.6); font-size: 0.7rem;">
                                    {{ __('Your scene flow is consistent across all characters, locations, and timeline.') }}
                                </p>
                            </div>
                        @endif

                    @elseif($activeTab === 'affinities')
                        {{-- Character-Location Affinities --}}
                        @if(!empty($affinities))
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                @foreach($affinities as $charName => $locationData)
                                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; padding: 0.6rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                            <div style="display: flex; align-items: center; gap: 0.35rem;">
                                                <span style="color: #a78bfa; font-size: 0.7rem;">&#x1F464;</span>
                                                <span style="color: white; font-weight: 600; font-size: 0.75rem;">{{ $charName }}</span>
                                            </div>
                                            <span style="color: rgba(255,255,255,0.5); font-size: 0.6rem;">
                                                {{ count($locationData) }} {{ __('locations') }}
                                            </span>
                                        </div>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                            @foreach($locationData as $locName => $count)
                                                <div style="background: rgba(52,211,153,0.15); border: 1px solid rgba(52,211,153,0.3); border-radius: 0.35rem; padding: 0.25rem 0.5rem; display: flex; align-items: center; gap: 0.3rem;">
                                                    <span style="color: #6ee7b7; font-size: 0.6rem;">{{ $locName }}</span>
                                                    <span style="background: rgba(52,211,153,0.3); color: #34d399; padding: 0.1rem 0.25rem; border-radius: 0.2rem; font-size: 0.5rem; font-weight: 600;">{{ $count }}x</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; text-align: center;">
                                <div style="width: 60px; height: 60px; background: rgba(139,92,246,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                    <span style="font-size: 1.5rem;">&#x1F465;</span>
                                </div>
                                <h4 style="color: rgba(255,255,255,0.8); margin: 0 0 0.35rem 0; font-size: 0.9rem;">{{ __('No Affinities Yet') }}</h4>
                                <p style="color: rgba(255,255,255,0.6); font-size: 0.7rem;">
                                    {{ __('Character-location affinities will appear once you have characters and locations assigned to scenes.') }}
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            @endif
        </div>

        {{-- Footer Actions --}}
        @if($isEnabled)
            <div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: rgba(0,0,0,0.2);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    {{-- Auto-sync toggle --}}
                    <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                        <input type="checkbox"
                               wire:model.live="sceneMemory.sceneDNA.autoSync"
                               style="width: 14px; height: 14px; accent-color: #06b6d4;">
                        <span style="color: rgba(255,255,255,0.7); font-size: 0.65rem;">{{ __('Auto-sync when Bibles change') }}</span>
                    </label>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button"
                            wire:click="analyzeAndSyncAllBibles"
                            wire:loading.attr="disabled"
                            wire:target="analyzeAndSyncAllBibles"
                            style="padding: 0.35rem 0.7rem; background: linear-gradient(135deg, #f59e0b, #ec4899); border: none; border-radius: 0.35rem; color: white; font-size: 0.65rem; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                        <span wire:loading.remove wire:target="analyzeAndSyncAllBibles">&#x1F9E0; {{ __('AI Sync Analysis') }}</span>
                        <span wire:loading wire:target="analyzeAndSyncAllBibles">{{ __('Analyzing...') }}</span>
                    </button>
                    <button type="button"
                            wire:click="$set('showSceneDNAModal', false)"
                            style="padding: 0.35rem 0.7rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.35rem; color: white; font-size: 0.65rem; cursor: pointer;">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
@endif
