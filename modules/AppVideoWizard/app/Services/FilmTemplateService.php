<?php

namespace Modules\AppVideoWizard\Services;

class FilmTemplateService
{
    protected const TEMPLATES = [
        'cyberpunk_thriller' => [
            'name' => 'Cyberpunk Thriller',
            'slug' => 'cyberpunk_thriller',
            'icon' => 'fa-light fa-microchip',
            'color' => '#a855f7',
            'description' => 'Neon-lit noir with character dialogue, anamorphic cinematography',
            'visual_style' => 'cyberpunk',
            'visual_overrides' => [
                'imagePrefix' => 'Anamorphic cinematography, teal-and-orange color grading, neon reflections, rain-slicked streets, horizontal lens flares, oval bokeh',
                'imageSuffix' => 'Blade Runner aesthetic, futuristic dystopia',
                'videoAnchor' => 'Anamorphic cyberpunk neon, rain reflections, teal-orange color grade',
                'videoLighting' => 'neon rim lights in teal and hot pink, volumetric fog',
                'videoColor' => 'teal-and-orange anamorphic color grading with crushed blacks',
            ],
            'characters' => [
                [
                    'id' => 'ren',
                    'name' => 'Ren',
                    'role' => 'protagonist',
                    'gender' => 'male',
                    'description' => 'Late 20s, sharp angular features, short dark hair, cybernetic implant above right ear, dark leather jacket with glowing circuit patterns',
                    'voice' => ['id' => 'echo', 'provider' => 'openai'],
                ],
                [
                    'id' => 'kira',
                    'name' => 'Kira',
                    'role' => 'deuteragonist',
                    'gender' => 'female',
                    'description' => 'Early 30s, athletic, silver-white cropped hair, reflective visor pushed up on forehead, dark tactical bodysuit with neon blue accents',
                    'voice' => ['id' => 'nova', 'provider' => 'openai'],
                ],
            ],
            'camera_rules' => [
                'establishing' => ['rise and reveal', 'slow zoom out', 'pan right slow'],
                'dialogue' => ['slow zoom in', 'push to subject', 'breathe'],
                'action' => ['zoom in pan right', 'dramatic zoom in', 'diagonal drift'],
                'tension' => ['slow zoom in', 'settle in'],
                'closing' => ['slow zoom out', 'rise and reveal'],
            ],
            'transitions' => ['default' => 'none', 'action' => 'none', 'dialogue' => 'none'],
            'scene_count_target' => 25,
            'duration_default' => 120,
            'aspect_ratio' => '16:9',
            'script_format' => 'screenplay',
            'no_narrator' => true,
            'atmosphere' => 'Rain-slicked neon streets, distant hover-car engines, electronic hum of holographic billboards',
            'music_style' => 'dark synthwave, brooding electronic',
        ],
    ];

    /**
     * Get template summaries for UI cards.
     */
    public function getTemplates(): array
    {
        return array_map(fn ($t) => [
            'slug' => $t['slug'],
            'name' => $t['name'],
            'icon' => $t['icon'],
            'color' => $t['color'],
            'description' => $t['description'],
            'character_count' => count($t['characters']),
            'duration' => $t['duration_default'],
            'aspect_ratio' => $t['aspect_ratio'],
        ], self::TEMPLATES);
    }

    /**
     * Get full template config by slug.
     */
    public function getTemplate(string $slug): ?array
    {
        return self::TEMPLATES[$slug] ?? null;
    }

    /**
     * Build AI prompt that generates a screenplay-format script.
     */
    public function buildScreenplayPrompt(array $template, string $userConcept, int $duration = 120): string
    {
        $sceneTarget = $template['scene_count_target'] ?? 25;
        $wordsPerMinute = 140;
        $dialogueWords = (int) round(($duration / 60) * $wordsPerMinute);

        // Build character list
        $characterLines = [];
        foreach ($template['characters'] as $char) {
            $role = ucfirst($char['role'] ?? 'character');
            $characterLines[] = "- " . strtoupper($char['name']) . " ({$role}): {$char['description']}";
        }
        $characterBlock = implode("\n", $characterLines);

        $genre = $template['name'];
        $atmosphere = $template['atmosphere'] ?? '';
        $musicStyle = $template['music_style'] ?? '';

        return <<<PROMPT
You are a screenplay writer. Write a CINEMATIC SHORT FILM SCREENPLAY.

FORMAT (CRITICAL — follow exactly):
- Character dialogue: CHARACTER_NAME: Spoken dialogue here
- Scene directions: [Scene: Description of what we see, setting, action, atmosphere]
- NO narrator. ALL spoken content = character dialogue only.
- Scene directions describe visuals only — they are NOT spoken by anyone.
- Each [Scene: ...] block starts a new visual scene.
- Keep dialogue punchy and cinematic — short lines, not monologues.
- NEVER use double quotes around dialogue text. Write: REN: I can hear it — NOT: REN: "I can hear it"

CHARACTERS (use these EXACTLY — do not invent new characters):
{$characterBlock}

STORY STRUCTURE:
- Opening (20%): Establish world, introduce character via action — no exposition dumps
- Rising tension (30%): Confrontation or mystery through dialogue and visuals
- Climax (30%): Peak action or dramatic revelation
- Resolution (20%): Aftermath, final image, emotional closure

TARGET: ~{$sceneTarget} scenes, {$duration} seconds of video, ~{$dialogueWords} words of dialogue
GENRE: {$genre}
ATMOSPHERE: {$atmosphere}
MUSIC MOOD: {$musicStyle}

USER'S CONCEPT: {$userConcept}

IMPORTANT RULES:
- Every scene MUST start with [Scene: ...] — this is how the system splits scenes
- Character names in dialogue MUST match exactly: {$this->getCharacterNameList($template)}
- Vary scene lengths — some quick cuts (1-2 lines), some longer beats (4-6 lines)
- Include purely visual scenes with no dialogue (just [Scene: direction]) for establishing shots
- End with a strong final image or line
- Each [Scene: ...] direction must describe a VISUALLY DISTINCT composition — vary the camera angle, distance, environment detail, and subject positioning. Never repeat similar framing or setting descriptions across consecutive scenes.
- Alternate between: establishing wide shots, intimate close-ups, over-the-shoulder angles, high angle overviews, low angle dramatic shots, detail inserts (hands, objects, screens).
- Vary locations within the story world — don't set multiple consecutive scenes in the same spot. Move between rooms, areas, or vantage points.

Respond with ONLY a JSON object:
{{
  "title": "Film title",
  "transcript": "The complete screenplay text with [Scene: ...] markers and CHARACTER: dialogue lines (no quotes around dialogue)",
  "concept_title": "One-line concept hook",
  "concept_pitch": "2-3 sentence pitch"
}}

Output ONLY valid JSON, no markdown formatting. Do NOT truncate the transcript.
PROMPT;
    }

    /**
     * Convert template characters to CharacterBible format for the pipeline.
     */
    public function getCharacterBibleForTemplate(array $template): array
    {
        $bible = [];
        foreach ($template['characters'] as $char) {
            $bible[] = [
                'id' => $char['id'],
                'name' => $char['name'],
                'description' => $char['description'],
                'gender' => $char['gender'] ?? 'unknown',
                'role' => $char['role'] ?? 'character',
                'voice' => $char['voice'],
                'appears_in' => [], // Will be populated by visual script
            ];
        }
        return $bible;
    }

    /**
     * Get visual style config by merging template overrides with base preset.
     */
    public function getVisualStyleConfig(array $template): array
    {
        return $template['visual_overrides'] ?? [];
    }

    /**
     * Build visual script deterministically from screenplay directions + template config.
     * Skips AI entirely — the [Scene: ...] directions are already rich visual descriptions.
     *
     * V2: Separate image vs video prompts, shot framing, smart prefix, concise character traits,
     * location-aware styling, and properly scoped video_action for Seedance (30-100 words).
     */
    public function buildFilmVisualScript(array $scenes, array $template): array
    {
        $overrides = $template['visual_overrides'] ?? [];
        $imagePrefix = $overrides['imagePrefix'] ?? '';
        $imageSuffix = $overrides['imageSuffix'] ?? '';
        $atmosphere = $template['atmosphere'] ?? '';
        $cameraRules = $template['camera_rules'] ?? [];
        $transitions = $template['transitions'] ?? [];
        $characters = $template['characters'] ?? [];
        $total = count($scenes);

        // Split prefix into outdoor and indoor elements
        $outdoorPrefix = $imagePrefix; // Full prefix: rain-slicked streets, lens flares, etc.
        $indoorPrefix = $this->extractIndoorPrefix($imagePrefix); // Lighting/color only

        // Track camera picks per scene type to cycle through options
        $cameraCounters = [];
        // Track dialogue scene counter for alternating OTS/medium shots
        $dialogueCounter = 0;

        // Location carry-forward: track current location across scenes
        $currentLocation = 'exterior_unknown';
        $currentLocationDesc = '';

        $visualScript = [];
        foreach ($scenes as $i => $scene) {
            $direction = $scene['direction'] ?? '';
            $body = $scene['text'] ?? '';
            $isVisualOnly = !empty($scene['is_visual_only']);

            // Fallback: if direction is empty, extract scene description from body text
            if (empty($direction) && !empty($body)) {
                // Try to extract from [Scene: ...] marker embedded in text
                if (preg_match('/\[Scene:\s*([^\]]+)\]/i', $body, $sceneMatch)) {
                    $direction = trim($sceneMatch[1]);
                } else {
                    // Use the body text directly — first 2-3 sentences as scene description
                    $cleanBody = preg_replace('/^[A-Z_]+:\s*/m', '', $body); // strip dialogue speaker labels
                    $sentences = preg_split('/(?<=[.!?])\s+/', trim($cleanBody), -1, PREG_SPLIT_NO_EMPTY);
                    $direction = implode(' ', array_slice($sentences, 0, min(3, count($sentences))));
                }
            }

            $sceneType = $this->detectSceneType($i, $total, $direction, $isVisualOnly, $body);
            // V3: Separate physical presence (direction) from dialogue mentions (body)
            // Physical chars appear in images/videos; all chars needed for voice/tracking
            $physicalChars = $this->detectCharactersInScene($direction, $characters);
            $allDetectedChars = $this->detectCharactersInScene($direction . ' ' . $body, $characters);

            // V4: Pronoun resolution — "he/his" → male char, "she/her" → female char
            // Only when a single character of that gender exists and isn't already detected
            $physicalChars = $this->resolvePronouns($direction, $physicalChars, $characters);
            $allDetectedChars = $this->resolvePronouns($direction . ' ' . $body, $allDetectedChars, $characters);
            $mood = $this->detectMood($direction, $body, $sceneType);
            $locationHint = $this->detectLocation($direction);

            // Location carry-forward: if this scene has a specific location, update tracker
            if ($locationHint !== 'exterior_unknown') {
                $currentLocation = $locationHint;
                $currentLocationDesc = $this->extractLocationDescription($direction);
            }
            // Use carried-forward location when current scene has no location keywords
            $effectiveLocation = ($locationHint === 'exterior_unknown' && $currentLocation !== 'exterior_unknown')
                ? $currentLocation
                : $locationHint;

            // --- Shot framing (skip if direction already has framing) ---
            $directionHasFraming = $this->directionContainsFraming($direction);
            $framing = $directionHasFraming ? '' : $this->getShotFraming($sceneType, $i, $total, $dialogueCounter);
            if ($sceneType === 'dialogue') {
                $dialogueCounter++;
            }

            // --- Smart prefix: outdoor elements only for exterior scenes ---
            $isExterior = in_array($effectiveLocation, ['exterior_urban', 'exterior_natural', 'exterior_unknown']);
            $smartPrefix = $isExterior ? $outdoorPrefix : $indoorPrefix;

            // --- Image prompt: flowing narrative format (physical chars only) ---
            $imagePrompt = $this->buildFlowingImagePrompt(
                $framing, $direction, $smartPrefix, $imageSuffix, $physicalChars, $characters
            );

            // --- Video action (for Seedance: 30-100 words, physical chars only) ---
            $videoAction = $this->buildConciseVideoAction($direction, $physicalChars, $characters, $isVisualOnly, $body);

            // --- Camera motion (cycle through template rules by scene type) ---
            $typeRules = $cameraRules[$sceneType] ?? $cameraRules['dialogue'] ?? ['slow zoom in'];
            if (!isset($cameraCounters[$sceneType])) {
                $cameraCounters[$sceneType] = 0;
            }
            $cameraMotion = $typeRules[$cameraCounters[$sceneType] % count($typeRules)];
            $cameraCounters[$sceneType]++;

            // --- Transition ---
            $isLast = ($i === $total - 1);
            if ($isLast) {
                $transitionType = 'fade';
            } else {
                $transitionType = $transitions[$sceneType] ?? $transitions['default'] ?? 'fadeblack';
            }

            // Transition duration by scene type
            $transitionDuration = match ($sceneType) {
                'action' => 0.3,
                'establishing' => 0.8,
                default => 0.5,
            };

            // --- Location prefix: prepend SETTING and append constraint ---
            $locationType = $isExterior ? 'EXTERIOR' : 'INTERIOR';
            $locationName = $this->getLocationTypeName($effectiveLocation);
            // Use carried-forward description if available, otherwise use type name
            $locDesc = (!empty($currentLocationDesc) && $effectiveLocation === $currentLocation)
                ? $currentLocationDesc
                : $locationName;
            $imagePrompt = "SETTING: {$locationType} — {$locDesc}. " . $imagePrompt;

            // Add interior constraint to prevent outdoor elements bleeding in
            if (!$isExterior) {
                $imagePrompt .= ". INTERIOR scene — show walls, ceiling, indoor lighting. Do NOT show outdoor sky, city skyline, or open air";
            }

            // Add aspect ratio instruction for 16:9 film format
            $templateAspect = $template['aspect_ratio'] ?? '16:9';
            if ($templateAspect === '16:9') {
                $imagePrompt .= '. MANDATORY: Compose in landscape 16:9 format with wide horizontal cinematic composition. Fill the ENTIRE frame edge-to-edge — no black bars, no letterboxing, no borders';
            }

            $visualScript[] = [
                'image_prompt' => trim($imagePrompt),
                'video_action' => trim($videoAction),
                'camera_motion' => $cameraMotion,
                'mood' => $mood,
                'voice_emotion' => $mood,
                'characters_in_scene' => $allDetectedChars,
                'transition_type' => $transitionType,
                'transition_duration' => $transitionDuration,
                'location_hint' => $effectiveLocation,
                'scene_type' => $sceneType,
            ];
        }

        // V4: Character continuity carry-forward pass
        // If character was in scene N-1 AND N+1 but NOT in N, add them to N
        // (unless scene N explicitly indicates solitude)
        $visualScript = $this->applyCharacterCarryForward($visualScript, $characters, $scenes);

        return $visualScript;
    }

    /**
     * Post-processing: carry forward characters across adjacent scenes.
     * Fixes gaps where a character appears in scenes 10 and 12 but not 11.
     */
    protected function applyCharacterCarryForward(array $visualScript, array $characters, array $scenes): array
    {
        $total = count($visualScript);
        if ($total < 3) return $visualScript;

        // Solitude keywords — scene explicitly excludes other characters
        $solitudePattern = '/\b(alone|solitary|solo|by\s+(?:him|her|them)self|isolated|empty\s+room)\b/i';

        for ($i = 1; $i < $total - 1; $i++) {
            $sceneText = ($scenes[$i]['direction'] ?? '') . ' ' . ($scenes[$i]['text'] ?? '');

            // Skip if scene explicitly indicates solitude
            if (preg_match($solitudePattern, $sceneText)) continue;

            $currentChars = $visualScript[$i]['characters_in_scene'];
            $prevChars = $visualScript[$i - 1]['characters_in_scene'];
            $nextChars = $visualScript[$i + 1]['characters_in_scene'];

            // Also check same-location continuity (char at same location should persist)
            $sameLocationAsPrev = ($visualScript[$i]['location_hint'] === $visualScript[$i - 1]['location_hint']
                && $visualScript[$i]['location_hint'] !== 'exterior_unknown');

            foreach ($characters as $char) {
                $charName = $char['name'];
                if (in_array($charName, $currentChars)) continue; // Already present

                $inPrev = in_array($charName, $prevChars);
                $inNext = in_array($charName, $nextChars);

                // Carry forward if: (in prev AND next) OR (in prev AND same location)
                if (($inPrev && $inNext) || ($inPrev && $sameLocationAsPrev)) {
                    $visualScript[$i]['characters_in_scene'][] = $charName;
                }
            }
        }

        return $visualScript;
    }

    /**
     * Get shot framing instruction based on scene type.
     */
    protected function getShotFraming(string $sceneType, int $index, int $total, int $dialogueCounter): string
    {
        return match ($sceneType) {
            'establishing' => match ($index % 3) {
                0 => 'Wide establishing shot from high angle',
                1 => 'Sweeping wide shot at eye level',
                2 => 'Low angle wide shot looking up',
            },
            'action' => match ($index % 4) {
                0 => 'Dynamic medium shot',
                1 => 'Low angle action shot',
                2 => 'Tight tracking shot',
                3 => 'Dutch angle medium shot',
            },
            'tension' => match ($index % 3) {
                0 => 'Extreme close-up',
                1 => 'Close-up with shallow depth of field',
                2 => 'Tight medium close-up',
            },
            'closing' => ($index >= $total - 1) ? 'Intimate close-up' : 'Wide pullback shot',
            'dialogue' => match ($dialogueCounter % 4) {
                0 => 'Medium shot',
                1 => 'Over-the-shoulder shot',
                2 => 'Two-shot medium',
                3 => 'Close-up reaction shot',
            },
            default => match ($index % 3) {
                0 => 'Medium shot',
                1 => 'Medium close-up',
                2 => 'Wide medium shot',
            },
        };
    }

    /**
     * Check if the screenplay direction already contains shot framing keywords.
     * If so, we skip prepending getShotFraming() to avoid contradictory instructions.
     */
    protected function directionContainsFraming(string $direction): bool
    {
        if (empty($direction)) return false;
        // Check first ~80 chars for framing keywords (they're typically at the start)
        $start = strtolower(substr($direction, 0, 80));
        return (bool) preg_match('/\b(close[\s-]?up|wide\s+shot|medium\s+shot|pov\s+shot|tracking\s+shot|over[\s-]?the[\s-]?shoulder|two[\s-]?shot|detail\s+insert|dutch\s+angle|high\s+angle|low\s+angle|aerial\s+shot|establishing\s+shot|extreme\s+close|tight\s+shot)\b/i', $start);
    }

    /**
     * Extract indoor-safe prefix elements (lighting, color, bokeh) — skip outdoor elements.
     */
    protected function extractIndoorPrefix(string $fullPrefix): string
    {
        if (empty($fullPrefix)) return '';

        // Outdoor-specific terms to strip for indoor scenes
        $outdoorTerms = ['rain-slicked streets', 'rain-slicked', 'streets', 'wet pavement', 'city skyline', 'neon reflections'];
        $parts = array_map('trim', explode(',', $fullPrefix));
        $indoor = [];
        foreach ($parts as $part) {
            $lower = strtolower($part);
            $isOutdoor = false;
            foreach ($outdoorTerms as $term) {
                if (str_contains($lower, $term)) {
                    $isOutdoor = true;
                    break;
                }
            }
            if (!$isOutdoor) {
                $indoor[] = $part;
            }
        }
        return implode(', ', $indoor);
    }

    /**
     * Detect location type from direction text for smart prefix application.
     */
    protected function detectLocation(string $direction): string
    {
        $lower = strtolower($direction);

        // Explicit INT./EXT. markers from screenplay take absolute priority
        if (preg_match('/\bINT\b\.?\s/i', $direction)) {
            if (preg_match('/\b(server|terminal|console|screen|monitor|lab|office|apartment|chamber|headquarters|cockpit)\b/', $lower)) {
                return 'interior_tech';
            }
            if (preg_match('/\b(warehouse|dock|underground|tunnel|bunker|factory|basement|garage|sewers?)\b/', $lower)) {
                return 'interior_industrial';
            }
            return 'interior_generic';
        }
        if (preg_match('/\bEXT\b\.?\s/i', $direction)) {
            if (preg_match('/\b(forest|ocean|mountain|field|desert|river|lake)\b/', $lower)) {
                return 'exterior_natural';
            }
            return 'exterior_urban';
        }

        // Interior tech
        if (preg_match('/\b(room|lab|terminal|console|screen|monitor|cockpit|server|office|apartment|chamber|headquarters)\b/', $lower)) {
            return 'interior_tech';
        }
        // Interior industrial
        if (preg_match('/\b(warehouse|dock|underground|tunnel|bunker|factory|basement|garage|sewers?)\b/', $lower)) {
            return 'interior_industrial';
        }
        // Interior generic (note: "bar" excluded — too ambiguous with "progress bar", "scroll bar")
        if (preg_match('/\b(inside|interior|indoors|nightclub|club|shop|store|elevator|corridor|hallway|stairwell|lobby|foyer|lounge|restroom|bathroom|kitchen|bedroom|cellar|attic)\b/', $lower)) {
            return 'interior_generic';
        }
        // "bar" as location only when preceded by location context or descriptors
        if (preg_match('/\b(?:the|a|dark|crowded|smoky|neon[\w-]*|dive|hidden|quiet|grimy|lit)\s+bar\b/', $lower)) {
            return 'interior_generic';
        }
        // Exterior urban — "neon" removed (too common in cyberpunk interiors)
        if (preg_match('/\b(street|alley|rooftop|city|skyline|district|plaza|market|bridge|highway|overpass)\b/', $lower)) {
            return 'exterior_urban';
        }
        // Exterior natural
        if (preg_match('/\b(forest|ocean|mountain|field|desert|river|lake|sky|horizon|cliff|shore)\b/', $lower)) {
            return 'exterior_natural';
        }

        return 'exterior_unknown'; // Default to exterior (safe for cyberpunk)
    }

    /**
     * Extract a brief location description from direction text.
     * E.g., "a sprawling, rain-slicked industrial dockyard at night" → "rain-slicked industrial dockyard at night"
     * Stops at verbs/character actions — only captures the location noun phrase.
     */
    protected function extractLocationDescription(string $direction): string
    {
        // Location nouns for extraction — excludes ambiguous words that appear as body parts
        // or metaphors (temple=body part, forest=digital forest, field=data field, bar=progress bar, cell=battery cell)
        $locationNouns = 'room|lab|warehouse|dock(?:yard)?|underground|tunnel|bunker|factory|office|apartment|chamber|headquarters|club|shop|corridor|hallway|street|alley|rooftop|plaza|market|bridge|server\s+(?:room|farm)|basement|garage|elevator|stairwell|penthouse|balcony|loft|hangar|arena|cathedral|station|hospital|prison|courtyard|garden|pier|wharf|harbor|shipyard';

        // Strategy 1: Compound location nouns (highest priority, most specific)
        if (preg_match('/\b(server\s+(?:room|farm)|control\s+room|engine\s+room|war\s+room|board\s+room|data\s+(?:vault|center|hub)|comms?\s+(?:post|station|tower))\b/i', $direction, $match)) {
            return ucfirst(trim($match[1]));
        }

        // Strategy 2: Look for "Back in the [location]" or "inside the [location]" patterns
        if (preg_match('/(?:back\s+in|inside|within|into)\s+(?:the\s+)?([a-z\-\s]*?\b(?:' . $locationNouns . ')\b)/i', $direction, $match)) {
            return ucfirst(trim($match[1]));
        }

        // Strategy 3: Extract [adjectives] + location noun, stop BEFORE verbs/possessives
        if (preg_match('/\b((?:(?:massive|corporate|underground|sterile|white|dark|cramped|transparent|quiet|dimly[\s-]lit|rain-slicked|industrial|sprawling|narrow|abandoned|broken|cavernous|humming)\s+)*(?:' . $locationNouns . ')(?:\s+(?:at\s+\w+|with\s+\w+|of\s+\w+))?)/i', $direction, $match)) {
            $desc = trim($match[1]);
            $desc = preg_replace('/^(?:a|an|the)\s+/i', '', $desc);
            if (strlen($desc) > 50) {
                $desc = substr($desc, 0, 50);
                $desc = preg_replace('/\s+\S*$/', '', $desc);
            }
            return ucfirst($desc);
        }

        // Fallback: use the location type name from detectLocation() instead of raw text
        return '';
    }

    /**
     * Map location type enum to human-readable name.
     */
    protected function getLocationTypeName(string $locationType): string
    {
        return match ($locationType) {
            'interior_tech' => 'Tech interior (room, lab, terminal)',
            'interior_industrial' => 'Industrial interior (warehouse, dock, tunnel)',
            'interior_generic' => 'Indoor space',
            'exterior_urban' => 'Urban exterior (streets, rooftops, city)',
            'exterior_natural' => 'Natural exterior (landscape, nature)',
            default => 'Exterior scene',
        };
    }

    /**
     * Condense a full character description to 2-3 key visual traits.
     * "Late 20s, sharp angular features, short dark hair, cybernetic implant above right ear, dark leather jacket with glowing circuit patterns"
     * → "short dark hair, cybernetic ear implant, leather jacket with glowing circuits"
     */
    protected function condensCharacterDescription(string $description): string
    {
        // Keep ALL visual traits (needed for character differentiation), skip only age
        $parts = array_map('trim', explode(',', $description));
        $visual = [];
        foreach ($parts as $part) {
            // Skip age descriptors only — keep body type as it helps differentiate
            if (preg_match('/^\b(late|early|mid)\s+\d+s?\b/i', $part)) continue;
            $visual[] = trim($part);
        }
        return implode(', ', $visual) ?: $description;
    }

    /**
     * Build concise video_action for Seedance (30-100 words).
     * Focuses on: subject + motion + key visual detail + degree adverbs.
     * No template prefix/suffix — those go into buildVideoPrompt's style layers.
     */
    protected function buildConciseVideoAction(string $direction, array $detectedChars, array $characters, bool $isVisualOnly, string $dialogueText = ''): string
    {
        if (empty($direction)) return '';

        // Replace character names with NAME + visual tag on first mention,
        // then correct-gender pronouns for subsequent mentions.
        // e.g. "REN (dark hair, cybernetic implant) leans forward... he pulls out..."
        $action = $direction;
        foreach ($detectedChars as $charName) {
            foreach ($characters as $char) {
                if (strtolower($char['name']) === strtolower($charName)) {
                    $namePattern = preg_quote($charName, '/');
                    $gender = $char['gender'] ?? 'unknown';
                    $pronoun = match ($gender) { 'male' => 'his', 'female' => 'her', default => 'their' };
                    $subjectPronoun = match ($gender) { 'male' => 'he', 'female' => 'she', default => 'they' };

                    // Step 1: Replace ALL possessives (NAME's → his/her)
                    $action = preg_replace("/\b{$namePattern}[\x{0027}\x{2018}\x{2019}\x{02BC}]s\b/iu", $pronoun, $action);

                    // Step 2: First mention → NAME (visual tag), subsequent → correct pronoun
                    $nameRegex = "/\b{$namePattern}\b/i";
                    $visualTag = $this->getCompactVisualTag($char);
                    $taggedName = strtoupper($charName) . (!empty($visualTag) ? " ({$visualTag})" : '');

                    if (preg_match($nameRegex, $action)) {
                        $action = preg_replace($nameRegex, $taggedName, $action, 1);
                        // Remaining occurrences → correct-gender pronoun
                        $action = preg_replace($nameRegex, $subjectPronoun, $action);
                    }

                    break;
                }
            }
        }

        // Append dialogue emotional context for Seedance lip-sync
        // Converts "REN: We need to move now." into "he speaks urgently"
        if (!$isVisualOnly && !empty(trim($dialogueText))) {
            $dialogueHint = $this->buildDialogueHint($dialogueText, $detectedChars, $characters);
            if (!empty($dialogueHint)) {
                $action .= '. ' . $dialogueHint;
            }
        }

        // Trim to 100 words max (film mode weaves camera/style inline, not appended)
        $words = explode(' ', $action);
        if (count($words) > 100) {
            $action = implode(' ', array_slice($words, 0, 100));
        }

        return trim($action);
    }

    /**
     * Build a concise dialogue hint for video prompts.
     * Converts screenplay dialogue lines into visual/emotional descriptors.
     * "REN: We need to move now." → "A man with cybernetic implant speaks urgently"
     */
    protected function buildDialogueHint(string $dialogueText, array $detectedChars, array $characters): string
    {
        $lines = preg_split('/\n+/', trim($dialogueText));
        $hints = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!preg_match('/^([A-Z][A-Z0-9_\s]+):\s*(.+)$/s', $line, $m)) continue;

            $speakerName = trim($m[1]);
            $spokenText = trim($m[2], '" ');
            if (empty($spokenText)) continue;

            // Find matching character
            $matchedChar = null;
            foreach ($characters as $char) {
                if (strtoupper(trim($char['name'])) === strtoupper($speakerName)) {
                    $matchedChar = $char;
                    break;
                }
            }

            // Determine emotion from spoken text
            $emotion = $this->detectDialogueEmotion($spokenText);
            if ($matchedChar) {
                $tag = $this->getCompactVisualTag($matchedChar);
                $name = strtoupper(trim($matchedChar['name']));
                $brief = !empty($tag) ? "{$name} ({$tag})" : $name;
            } else {
                $brief = 'a person';
            }
            $hints[] = "{$brief} speaks {$emotion}";

            if (count($hints) >= 2) break; // Max 2 speakers for video clarity
        }

        return implode(', while ', $hints);
    }

    /**
     * Detect emotional tone from spoken dialogue text.
     */
    protected function detectDialogueEmotion(string $text): string
    {
        $lower = strtolower($text);
        if (preg_match('/[!]{2,}|damn|hell|stop|enough/', $lower)) return 'forcefully';
        if (preg_match('/\?.*\?|what|who|why|how/', $lower)) return 'questioningly';
        if (preg_match('/please|help|need|must|hurry/', $lower)) return 'urgently';
        if (preg_match('/remember|once|long ago|used to/', $lower)) return 'reflectively';
        if (preg_match('/trust|together|promise|believe/', $lower)) return 'earnestly';
        if (preg_match('/quiet|whisper|careful|listen/', $lower)) return 'softly';
        if (preg_match('/never|betray|lie|deceive/', $lower)) return 'fiercely';
        return 'firmly';
    }

    /**
     * Get a brief visual descriptor for a character (for Seedance prompts).
     * "Ren" → "A man with a cybernetic ear implant"
     */
    protected function getCharacterVisualBrief(array $char): string
    {
        $gender = $char['gender'] ?? 'unknown';
        $subject = match ($gender) {
            'male' => 'A man',
            'female' => 'A woman',
            default => 'A person',
        };

        // Extract the most distinctive visual trait and shorten it
        $desc = $char['description'] ?? '';
        $parts = array_map('trim', explode(',', $desc));
        foreach ($parts as $part) {
            if (preg_match('/\b(cybernetic|implant|scar|tattoo|visor|prosthetic|augmented|glowing|silver|chrome)\b/i', $part)) {
                $short = $this->shortenTraitPhrase(strtolower(trim($part)));
                return $subject . ' with ' . $short;
            }
        }
        // Fallback: use hair descriptor
        foreach ($parts as $part) {
            if (preg_match('/\b(hair|bald|shaved|dreadlocks|braids|mohawk)\b/i', $part)) {
                $short = $this->shortenTraitPhrase(strtolower(trim($part)));
                return $subject . ' with ' . $short;
            }
        }

        return $subject;
    }

    /**
     * Get a compact visual tag for a character — 2-3 key traits for parenthetical insertion.
     * "Ren" → "dark hair, cybernetic implant"
     * "Kira" → "silver-white cropped hair, reflective visor"
     */
    protected function getCompactVisualTag(array $char): string
    {
        $desc = $char['description'] ?? '';
        if (empty($desc)) {
            return match ($char['gender'] ?? 'unknown') {
                'male' => 'male', 'female' => 'female', default => '',
            };
        }

        $parts = array_map('trim', explode(',', $desc));
        $tags = [];

        foreach ($parts as $part) {
            $lower = strtolower(trim($part));
            // Skip age/generic descriptors like "Late 20s", "early 30s"
            if (preg_match('/^\s*(late|early|mid)?\s*\d+s?\s*$/i', $lower)) continue;
            if (preg_match('/^\s*(sharp|angular|athletic|tall|short|slim|stocky)\s/i', $lower)) continue;
            // Keep distinctive visual traits (hair, implants, clothing, accessories)
            if (preg_match('/\b(hair|implant|scar|tattoo|visor|jacket|suit|bodysuit|dress|armor|hood|cloak|prosthetic|augmented|chrome|glowing)\b/i', $lower)) {
                // Shorten positional phrases
                $lower = preg_replace('/\b(above|below|on|near|behind|across|around|over|under|pushed\s+up\s+on|attached\s+to)\b.*$/i', '', $lower);
                $tags[] = trim($lower);
                if (count($tags) >= 2) break;
            }
        }

        return implode(', ', $tags);
    }

    /**
     * Shorten a trait phrase by removing positional words and limiting length.
     * "cybernetic implant above right ear" → "cybernetic implant"
     * "reflective visor pushed up on forehead" → "reflective visor"
     */
    protected function shortenTraitPhrase(string $trait): string
    {
        // Remove positional/contextual phrases: "above right ear", "pushed up on forehead", etc.
        $trait = preg_replace('/\b(above|below|on|near|behind|across|around|over|under|pushed\s+up\s+on|attached\s+to)\b.*$/i', '', $trait);
        $trait = trim($trait, ' ,.');

        // Limit to 4 words max
        $words = explode(' ', $trait);
        if (count($words) > 4) {
            $words = array_slice($words, 0, 4);
        }
        return trim(implode(' ', $words));
    }

    /**
     * Resolve pronouns in scene text to detect implied characters.
     * If text contains "he/his/him" and only one male character exists, add that character.
     * Same for "she/her" with female characters.
     */
    protected function resolvePronouns(string $text, array $detectedChars, array $characters): array
    {
        $lower = strtolower($text);

        // Build gender-to-character mapping (only useful when a single char per gender)
        $maleChars = [];
        $femaleChars = [];
        foreach ($characters as $char) {
            $gender = strtolower($char['gender'] ?? 'unknown');
            if ($gender === 'male') $maleChars[] = $char['name'];
            elseif ($gender === 'female') $femaleChars[] = $char['name'];
        }

        // Resolve male pronouns — only if exactly one male character exists
        if (count($maleChars) === 1 && !in_array($maleChars[0], $detectedChars)) {
            if (preg_match('/\b(he|his|him|himself)\b/', $lower)) {
                $detectedChars[] = $maleChars[0];
            }
        }

        // Resolve female pronouns — only if exactly one female character exists
        if (count($femaleChars) === 1 && !in_array($femaleChars[0], $detectedChars)) {
            if (preg_match('/\b(she|her|hers|herself)\b/', $lower)) {
                $detectedChars[] = $femaleChars[0];
            }
        }

        return $detectedChars;
    }

    /**
     * Get a simple gender-based subject for a character.
     */
    protected function getGenderSubject(array $char): string
    {
        return match ($char['gender'] ?? 'unknown') {
            'male' => 'A man',
            'female' => 'A woman',
            default => 'A person',
        };
    }

    /**
     * Extract distinctive trait keywords from a character's description for stutter detection.
     */
    protected function getCharacterTraitKeywords(array $char): array
    {
        $desc = strtolower($char['description'] ?? '');
        $keywords = [];
        // Extract significant words (5+ chars, skip common words)
        $skip = ['about', 'above', 'their', 'there', 'which', 'where', 'early', 'would'];
        foreach (preg_split('/[\s,]+/', $desc) as $word) {
            $word = trim($word, '.,;:!?');
            if (strlen($word) >= 5 && !in_array($word, $skip)) {
                $keywords[] = $word;
            }
        }
        return $keywords;
    }

    /**
     * Detect scene type from content and position.
     */
    public function detectSceneType(int $index, int $total, string $direction, bool $isVisualOnly, string $body): string
    {
        // First scene
        if ($index === 0) {
            return 'establishing';
        }
        // Last 2 scenes
        if ($index >= $total - 2) {
            return 'closing';
        }
        // Visual-only scenes (no dialogue)
        if ($isVisualOnly) {
            return 'establishing';
        }

        $combined = strtolower($direction . ' ' . $body);

        // Action keywords
        if (preg_match('/\b(lunges?|grabs?|explodes?|crash|slams?|erupts?|surge|blinding|flash|fights?|chase|runs?|leaps?|strikes?|smash)\b/', $combined)) {
            return 'action';
        }

        // Tension keywords
        if (preg_match('/\b(danger|threat|confront|choice|decide|destroy|risk|chaos|trembl|hesitat|betray)\b/', $combined)) {
            return 'tension';
        }

        // Default — scenes with dialogue
        return 'dialogue';
    }

    /**
     * Detect which template characters appear in the scene text.
     * V2: Scans both direction and dialogue text — matches dialogue pattern (NAME:)
     * and plain name mentions in direction text.
     */
    public function detectCharactersInScene(string $text, array $characters): array
    {
        $found = [];
        foreach ($characters as $char) {
            $name = preg_quote($char['name'], '/');
            // Match "CHARACTER:" dialogue pattern OR plain name mention in direction
            if (preg_match('/\b' . $name . '\b/i', $text)) {
                $found[] = $char['name'];
            }
        }
        return $found;
    }

    /**
     * Detect mood from scene content with scene-type fallback.
     */
    public function detectMood(string $direction, string $body, string $sceneType): string
    {
        $combined = strtolower($direction . ' ' . $body);

        // Keyword-based detection
        if (preg_match('/\b(rain|dark|shadow|fog|mist|dim|murky|noir)\b/', $combined)) {
            return 'mysterious';
        }
        if (preg_match('/\b(explod|surge|blinding|epic|massive|powerful|thunder)\b/', $combined)) {
            return 'epic';
        }
        if (preg_match('/\b(danger|threat|tense|confront|risk|chaos|betray)\b/', $combined)) {
            return 'tense';
        }
        if (preg_match('/\b(silence|quiet|still|intimate|gentle|soft|whisper)\b/', $combined)) {
            return 'intimate';
        }
        if (preg_match('/\b(hope|light|dawn|bright|warm|smile|relief)\b/', $combined)) {
            return 'hopeful';
        }
        if (preg_match('/\b(rush|fast|chase|sprint|slam|crash|fight)\b/', $combined)) {
            return 'intense';
        }

        // Scene-type fallback
        return match ($sceneType) {
            'establishing' => 'atmospheric',
            'action' => 'intense',
            'tension' => 'tense',
            'closing' => 'reflective',
            default => 'dramatic',
        };
    }

    /**
     * Get comma-separated character names for prompt.
     */
    protected function getCharacterNameList(array $template): string
    {
        return implode(', ', array_map(fn ($c) => strtoupper($c['name']), $template['characters']));
    }

    /**
     * Build a flowing image prompt instead of period-joined fragments.
     * Framing leads into scene direction with style elements woven in naturally.
     */
    protected function buildFlowingImagePrompt(
        string $framing,
        string $direction,
        string $smartPrefix,
        string $imageSuffix,
        array $detectedChars,
        array $characters
    ): string {
        // Start with framing as the shot instruction (empty if direction already has framing)
        $prompt = $framing;

        // Weave direction (the main visual description from the screenplay)
        if (!empty($direction)) {
            if (!empty($prompt)) {
                $prompt .= '. ' . $direction;
            } else {
                $prompt = $direction;
            }
        }

        // Weave style prefix as atmospheric detail (2-3 key terms only, not the full prefix)
        if (!empty($smartPrefix)) {
            $keyTerms = $this->extractKeyStyleTerms($smartPrefix, 3);
            if (!empty($keyTerms) && !$this->alreadyContainsTerms($prompt, $keyTerms)) {
                $prompt = rtrim($prompt, '. ') . ', ' . strtolower($keyTerms);
            }
        }

        // Weave suffix as a closing atmospheric clause
        if (!empty($imageSuffix)) {
            $keySuffix = $this->extractKeyStyleTerms($imageSuffix, 2);
            if (!empty($keySuffix) && !$this->alreadyContainsTerms($prompt, $keySuffix)) {
                $prompt = rtrim($prompt, '. ') . ', ' . strtolower($keySuffix);
            }
        }

        // Inject character visual traits — ALWAYS, with names, separated per character
        if (!empty($detectedChars)) {
            $charBlocks = [];
            foreach ($detectedChars as $charName) {
                foreach ($characters as $char) {
                    if (strtolower($char['name']) === strtolower($charName)) {
                        $desc = $this->condensCharacterDescription($char['description']);
                        $charBlocks[] = strtoupper($char['name']) . ' — ' . $desc;
                        break;
                    }
                }
            }
            if (count($charBlocks) === 1) {
                $prompt = rtrim($prompt, '. ') . '. Character: ' . $charBlocks[0];
            } elseif (count($charBlocks) > 1) {
                $prompt = rtrim($prompt, '. ') . '. Characters (each is a DIFFERENT person): ' . implode('. ', $charBlocks);
            }
        }

        // Anti-text instruction
        $prompt .= '. No text, subtitles, or captions';

        // Clean up
        $prompt = preg_replace('/\.\s*\./', '.', $prompt);
        $prompt = preg_replace('/\s{2,}/', ' ', $prompt);

        return trim($prompt);
    }

    /**
     * Extract N key style terms from a prefix string, skipping generic ones.
     */
    protected function extractKeyStyleTerms(string $prefix, int $maxTerms): string
    {
        if (empty($prefix)) return '';
        $parts = array_map('trim', explode(',', $prefix));
        $generic = ['cinematic', 'professional', 'high quality', 'detailed', 'realistic'];
        $filtered = [];
        foreach ($parts as $part) {
            $lower = strtolower($part);
            $isGeneric = false;
            foreach ($generic as $g) {
                if (str_contains($lower, $g)) { $isGeneric = true; break; }
            }
            if (!$isGeneric && !empty(trim($part))) {
                $filtered[] = trim($part);
            }
            if (count($filtered) >= $maxTerms) break;
        }
        if (empty($filtered)) {
            $filtered = array_slice($parts, 0, $maxTerms);
        }
        return implode(', ', $filtered);
    }

    /**
     * Check if the prompt already contains key terms (avoid duplication).
     */
    protected function alreadyContainsTerms(string $prompt, string $terms): bool
    {
        $checkWords = explode(' ', strtolower($terms));
        $promptLower = strtolower($prompt);
        $matches = 0;
        foreach ($checkWords as $word) {
            if (strlen($word) > 4 && str_contains($promptLower, $word)) {
                $matches++;
            }
        }
        return $matches >= 2;
    }
}
