{{-- Scene Text Inspector Modal --}}
@if($showSceneTextInspectorModal ?? false)
<div class="vw-modal-overlay"
     x-data="{ show: @entangle('showSceneTextInspectorModal') }"
     x-show="show"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="$wire.closeSceneTextInspector()"
     wire:key="scene-inspector-{{ $inspectorSceneIndex ?? 'main' }}"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 0.5rem;">

    <div class="vw-modal"
         @click.outside="$wire.closeSceneTextInspector()"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.75rem; width: 100%; max-width: 920px; max-height: 96vh; display: flex; flex-direction: column; overflow: hidden;">

        {{-- Header --}}
        <div style="padding: 0.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            @php
                $scene = $this->inspectorScene['script'] ?? null;
                $sceneNum = ($inspectorSceneIndex ?? 0) + 1;
            @endphp
            <div>
                <h3 style="margin: 0; color: white; font-size: 1rem; font-weight: 600;">
                    Scene {{ $sceneNum }}{{ !empty($scene['title']) ? ': ' . $scene['title'] : '' }}
                </h3>
                <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.7rem;">
                    {{ __('Complete scene text, prompts, and metadata') }}
                </p>
            </div>
            <button type="button"
                    wire:click="closeSceneTextInspector"
                    style="background: none; border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1rem;">
            @if($scene)
                {{-- Metadata Section --}}
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.75rem 0; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                        Scene Metadata
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem;">
                        {{-- META-01: Duration --}}
                        @php
                            $duration = $scene['metadata']['duration'] ?? null;
                            $durationFormatted = 'N/A';
                            if ($duration && is_numeric($duration)) {
                                $minutes = floor($duration / 60);
                                $seconds = $duration % 60;
                                $durationFormatted = sprintf('%02d:%02d', $minutes, $seconds);
                            }
                        @endphp
                        <div style="padding: 0.5rem 0.75rem; background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem;">⏱️</span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Duration</div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.95); font-weight: 500;">{{ $durationFormatted }}</div>
                            </div>
                        </div>

                        {{-- META-02: Transition --}}
                        @php
                            $transition = $scene['metadata']['transition'] ?? 'CUT';
                            $transitionIcons = [
                                'CUT' => '✂️',
                                'FADE' => '🌫️',
                                'DISSOLVE' => '💫',
                                'WIPE' => '↔️',
                                'IRIS' => '⭕',
                            ];
                            $transitionIcon = $transitionIcons[strtoupper($transition)] ?? '🎬';
                        @endphp
                        <div style="padding: 0.5rem 0.75rem; background: rgba(168,85,247,0.15); border: 1px solid rgba(168,85,247,0.3); border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem;">{{ $transitionIcon }}</span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Transition</div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.95); font-weight: 500;">{{ strtoupper($transition) }}</div>
                            </div>
                        </div>

                        {{-- META-03: Location --}}
                        @php
                            $location = $scene['metadata']['location'] ?? $scene['location'] ?? 'Unknown';
                            if (is_array($location)) {
                                $location = $location['name'] ?? $location['description'] ?? 'Unknown';
                            }
                            $locationDisplay = strlen($location) > 30 ? substr($location, 0, 27) . '...' : $location;
                        @endphp
                        <div style="padding: 0.5rem 0.75rem; background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem;">📍</span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Location</div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.95); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $location }}">{{ $locationDisplay }}</div>
                            </div>
                        </div>

                        {{-- META-04: Characters --}}
                        @php
                            $characters = $scene['characters'] ?? [];
                            $charCount = count($characters);
                            $charDisplay = '';
                            if ($charCount === 0) {
                                $charDisplay = 'None';
                            } elseif ($charCount <= 3) {
                                $charDisplay = implode(', ', array_map(function($char) {
                                    return is_array($char) ? ($char['name'] ?? 'Unknown') : $char;
                                }, $characters));
                            } else {
                                $firstThree = array_slice($characters, 0, 3);
                                $names = array_map(function($char) {
                                    return is_array($char) ? ($char['name'] ?? 'Unknown') : $char;
                                }, $firstThree);
                                $charDisplay = implode(', ', $names) . ' +' . ($charCount - 3) . ' more';
                            }
                        @endphp
                        <div style="padding: 0.5rem 0.75rem; background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem;">👥</span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Characters</div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.95); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $charDisplay }}">{{ $charDisplay }}</div>
                            </div>
                        </div>

                        {{-- META-05: Emotional Intensity --}}
                        @php
                            $intensity = $scene['emotionalIntensity'] ?? null;
                            $intensityColor = 'rgba(100,116,139,0.15)';
                            $intensityBorder = 'rgba(100,116,139,0.3)';
                            $intensityLabel = 'N/A';

                            if ($intensity !== null && is_numeric($intensity)) {
                                $intensityLabel = (int)$intensity . '/10';
                                if ($intensity >= 1 && $intensity <= 3) {
                                    $intensityColor = 'rgba(59,130,246,0.15)';
                                    $intensityBorder = 'rgba(59,130,246,0.3)';
                                } elseif ($intensity >= 4 && $intensity <= 6) {
                                    $intensityColor = 'rgba(251,191,36,0.15)';
                                    $intensityBorder = 'rgba(251,191,36,0.3)';
                                } elseif ($intensity >= 7 && $intensity <= 10) {
                                    $intensityColor = 'rgba(239,68,68,0.15)';
                                    $intensityBorder = 'rgba(239,68,68,0.3)';
                                }
                            }
                        @endphp
                        <div style="padding: 0.5rem 0.75rem; background: {{ $intensityColor }}; border: 1px solid {{ $intensityBorder }}; border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem;">🔥</span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Intensity</div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.95); font-weight: 500;">{{ $intensityLabel }}</div>
                            </div>
                        </div>

                        {{-- META-06: Climax Badge (full-width, only for climax scenes) --}}
                        @if(($scene['isClimax'] ?? false) || ($scene['metadata']['isClimax'] ?? false))
                            <div style="grid-column: 1 / -1; padding: 0.75rem 1rem; background: linear-gradient(135deg, rgba(236,72,153,0.2), rgba(139,92,246,0.2)); border: 2px solid transparent; background-clip: padding-box; border-radius: 0.5rem; position: relative; overflow: hidden;">
                                <div style="position: absolute; inset: 0; background: linear-gradient(135deg, rgba(236,72,153,0.4), rgba(139,92,246,0.4)); border-radius: 0.5rem; z-index: 0; opacity: 0.3;"></div>
                                <div style="position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
                                    <span style="font-size: 1.25rem;">⭐</span>
                                    <div>
                                        <div style="font-size: 0.7rem; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600;">Climactic Scene</div>
                                        <div style="font-size: 0.85rem; color: rgba(255,255,255,0.95); font-weight: 500; margin-top: 0.15rem;">This is a pivotal moment in the story</div>
                                    </div>
                                    <span style="font-size: 1.25rem;">⭐</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Speech Segments Section --}}
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.75rem 0; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                        Speech Segments
                        @if(!empty($scene['speechSegments']))
                            <span style="opacity: 0.6; font-weight: normal; font-size: 0.7rem; text-transform: none; margin-left: 0.5rem;">({{ count($scene['speechSegments']) }} segments)</span>
                        @endif
                    </h4>

                    @php
                        $speechSegments = $scene['speechSegments'] ?? [];
                        $characterBible = $sceneMemory['characterBible']['characters'] ?? [];

                        // Type configuration matching storyboard patterns
                        $typeConfig = [
                            'narrator' => ['icon' => '🎙️', 'color' => 'rgba(14, 165, 233, 0.4)', 'label' => 'NARRATOR', 'lipSync' => false],
                            'dialogue' => ['icon' => '💬', 'color' => 'rgba(16, 185, 129, 0.4)', 'label' => 'DIALOGUE', 'lipSync' => true],
                            'internal' => ['icon' => '💭', 'color' => 'rgba(168, 85, 247, 0.4)', 'label' => 'INTERNAL', 'lipSync' => false],
                            'monologue' => ['icon' => '🗣️', 'color' => 'rgba(251, 191, 36, 0.4)', 'label' => 'MONOLOGUE', 'lipSync' => true],
                        ];
                    @endphp

                    @if(!empty($speechSegments))
                        <div style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; padding-right: 0.25rem;">
                            @foreach($speechSegments as $index => $segment)
                                @php
                                    $segType = $segment['type'] ?? 'narrator';
                                    $typeData = $typeConfig[$segType] ?? $typeConfig['narrator'];
                                    $needsLipSync = $typeData['lipSync'];

                                    // Duration estimation (150 WPM)
                                    $wordCount = str_word_count($segment['text'] ?? '');
                                    $estDuration = $segment['duration'] ?? round(($wordCount / 150) * 60, 1);
                                    $durationDisplay = $estDuration >= 60
                                        ? sprintf('%d:%02d', floor($estDuration / 60), $estDuration % 60)
                                        : round($estDuration, 1) . 's';

                                    // Character Bible matching (SPCH-07)
                                    $speaker = $segment['speaker'] ?? null;
                                    $matchedChar = null;
                                    if ($speaker && !empty($characterBible)) {
                                        $speakerUpper = strtoupper($speaker);
                                        foreach ($characterBible as $char) {
                                            $charName = strtoupper($char['name'] ?? '');
                                            if ($charName === $speakerUpper || str_contains($charName, $speakerUpper) || str_contains($speakerUpper, $charName)) {
                                                $matchedChar = $char;
                                                break;
                                            }
                                        }
                                    }
                                @endphp

                                <div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border-left: 3px solid {{ $typeData['color'] }}; border-radius: 0 0.375rem 0.375rem 0;">
                                    {{-- Header: Type badge, Speaker, Lip-sync, Duration --}}
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                                        {{-- Type icon (SPCH-03) --}}
                                        <span style="font-size: 1rem;">{{ $typeData['icon'] }}</span>

                                        {{-- Type label (SPCH-02) --}}
                                        <span style="font-size: 0.65rem; font-weight: 600; color: white; padding: 0.15rem 0.4rem; background: {{ $typeData['color'] }}; border-radius: 0.25rem;">
                                            {{ $typeData['label'] }}
                                        </span>

                                        {{-- Speaker name in purple (SPCH-04) with character indicator (SPCH-07) --}}
                                        @if($speaker)
                                            <span style="color: #c4b5fd; font-size: 0.75rem; font-weight: 600;">{{ $speaker }}</span>
                                            @if($matchedChar)
                                                <span title="{{ __('Character exists in Bible') }}" style="font-size: 0.65rem; color: #10b981;">👤</span>
                                            @endif
                                        @endif

                                        {{-- Spacer --}}
                                        <span style="flex: 1;"></span>

                                        {{-- Lip-sync indicator (SPCH-05) --}}
                                        <span style="font-size: 0.6rem; padding: 0.1rem 0.35rem; border-radius: 0.2rem; font-weight: 500;
                                            {{ $needsLipSync
                                                ? 'background: rgba(16,185,129,0.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.3);'
                                                : 'background: rgba(100,116,139,0.15); color: rgba(255,255,255,0.5); border: 1px solid rgba(100,116,139,0.2);'
                                            }}">
                                            LIP-SYNC: {{ $needsLipSync ? 'YES' : 'NO' }}
                                        </span>

                                        {{-- Duration (SPCH-06) --}}
                                        <span style="font-size: 0.6rem; color: rgba(255,255,255,0.5);" title="{{ __('Estimated duration at 150 WPM') }}">
                                            ⏱️ {{ $durationDisplay }}
                                        </span>
                                    </div>

                                    {{-- Full text content - no truncation (SPCH-01) --}}
                                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.85); line-height: 1.6; white-space: pre-wrap; word-break: break-word;">
                                        {{ $segment['text'] ?? '' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif(!empty($scene['narration']))
                        {{-- Legacy narration fallback --}}
                        <div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border-left: 3px solid rgba(14, 165, 233, 0.4); border-radius: 0 0.375rem 0.375rem 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <span style="font-size: 1rem;">🎙️</span>
                                <span style="font-size: 0.65rem; font-weight: 600; color: white; padding: 0.15rem 0.4rem; background: rgba(14, 165, 233, 0.4); border-radius: 0.25rem;">NARRATOR</span>
                                <span style="flex: 1;"></span>
                                <span style="font-size: 0.6rem; padding: 0.1rem 0.35rem; border-radius: 0.2rem; font-weight: 500; background: rgba(100,116,139,0.15); color: rgba(255,255,255,0.5); border: 1px solid rgba(100,116,139,0.2);">
                                    LIP-SYNC: NO
                                </span>
                            </div>
                            <div style="font-size: 0.8rem; color: rgba(255,255,255,0.85); line-height: 1.6; white-space: pre-wrap; word-break: break-word;">
                                {{ $scene['narration'] }}
                            </div>
                        </div>
                    @else
                        <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; text-align: center; color: rgba(255,255,255,0.4); font-size: 0.75rem;">
                            {{ __('No speech segments for this scene') }}
                        </div>
                    @endif
                </div>

                {{-- Prompts Section (Phase 9) --}}
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.75rem 0; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                        Prompts
                    </h4>
                    <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; text-align: center; color: rgba(255,255,255,0.6); font-size: 0.75rem;">
                        Prompt display coming in Phase 9
                    </div>
                </div>
            @else
                <div style="padding: 2rem; text-align: center; color: rgba(255,255,255,0.4);">
                    {{ __('Scene not found') }}
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end; gap: 0.5rem; flex-shrink: 0;">
            <button type="button"
                    wire:click="closeSceneTextInspector"
                    style="padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.375rem; color: white; font-size: 0.75rem; cursor: pointer;">
                {{ __('Close') }}
            </button>
        </div>

    </div>
</div>
@endif
