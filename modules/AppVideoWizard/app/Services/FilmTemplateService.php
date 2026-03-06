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

        $visualScript = [];
        foreach ($scenes as $i => $scene) {
            $direction = $scene['direction'] ?? '';
            $body = $scene['text'] ?? '';
            $isVisualOnly = !empty($scene['is_visual_only']);

            $sceneType = $this->detectSceneType($i, $total, $direction, $isVisualOnly, $body);
            // V2: scan both direction AND body for character detection
            $detectedChars = $this->detectCharactersInScene($direction . ' ' . $body, $characters);
            $mood = $this->detectMood($direction, $body, $sceneType);
            $locationHint = $this->detectLocation($direction);

            // --- Shot framing (first instruction in image prompt) ---
            $framing = $this->getShotFraming($sceneType, $i, $total, $dialogueCounter);
            if ($sceneType === 'dialogue') {
                $dialogueCounter++;
            }

            // --- Smart prefix: outdoor elements only for exterior scenes ---
            $isExterior = in_array($locationHint, ['exterior_urban', 'exterior_natural', 'exterior_unknown']);
            $smartPrefix = $isExterior ? $outdoorPrefix : $indoorPrefix;

            // --- Image prompt: flowing narrative format ---
            $imagePrompt = $this->buildFlowingImagePrompt(
                $framing, $direction, $smartPrefix, $imageSuffix, $detectedChars, $characters
            );

            // --- Video action (for Seedance: 30-100 words, detailed scene description) ---
            $videoAction = $this->buildConciseVideoAction($direction, $detectedChars, $characters, $isVisualOnly);

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

            // Add aspect ratio instruction for 16:9 film format
            $templateAspect = $template['aspect_ratio'] ?? '16:9';
            if ($templateAspect === '16:9') {
                $imagePrompt .= '. MANDATORY: Compose in landscape 16:9 widescreen format with wide horizontal cinematic composition';
            }

            $visualScript[] = [
                'image_prompt' => trim($imagePrompt),
                'video_action' => trim($videoAction),
                'camera_motion' => $cameraMotion,
                'mood' => $mood,
                'voice_emotion' => $mood,
                'characters_in_scene' => $detectedChars,
                'transition_type' => $transitionType,
                'transition_duration' => $transitionDuration,
                'location_hint' => $locationHint,
                'scene_type' => $sceneType,
            ];
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

        // Interior tech
        if (preg_match('/\b(room|lab|terminal|console|screen|monitor|cockpit|server|office|apartment|chamber|headquarters)\b/', $lower)) {
            return 'interior_tech';
        }
        // Interior industrial
        if (preg_match('/\b(warehouse|dock|underground|tunnel|bunker|factory|basement|garage|sewers?)\b/', $lower)) {
            return 'interior_industrial';
        }
        // Interior generic
        if (preg_match('/\b(inside|interior|indoors|bar|club|shop|store|elevator|corridor|hallway|stairwell)\b/', $lower)) {
            return 'interior_generic';
        }
        // Exterior urban
        if (preg_match('/\b(street|alley|rooftop|city|skyline|district|neon|plaza|market|bridge|highway|overpass)\b/', $lower)) {
            return 'exterior_urban';
        }
        // Exterior natural
        if (preg_match('/\b(forest|ocean|mountain|field|desert|river|lake|sky|horizon|cliff|shore)\b/', $lower)) {
            return 'exterior_natural';
        }

        return 'exterior_unknown'; // Default to exterior (safe for cyberpunk)
    }

    /**
     * Condense a full character description to 2-3 key visual traits.
     * "Late 20s, sharp angular features, short dark hair, cybernetic implant above right ear, dark leather jacket with glowing circuit patterns"
     * → "short dark hair, cybernetic ear implant, leather jacket with glowing circuits"
     */
    protected function condensCharacterDescription(string $description): string
    {
        // Split on commas and take up to 3 most visual traits (skip age/generic)
        $parts = array_map('trim', explode(',', $description));
        $visual = [];
        foreach ($parts as $part) {
            // Skip age descriptors and generic body type
            if (preg_match('/^\b(late|early|mid)\s+\d+s?\b/i', $part)) continue;
            if (preg_match('/^\b(tall|short|average|slim|athletic|stocky)\b$/i', $part)) continue;
            $visual[] = trim($part);
            if (count($visual) >= 3) break;
        }
        return implode(', ', $visual) ?: $description;
    }

    /**
     * Build concise video_action for Seedance (30-100 words).
     * Focuses on: subject + motion + key visual detail + degree adverbs.
     * No template prefix/suffix — those go into buildVideoPrompt's style layers.
     */
    protected function buildConciseVideoAction(string $direction, array $detectedChars, array $characters, bool $isVisualOnly): string
    {
        if (empty($direction)) return '';

        // Replace character names with brief visual descriptors for Seedance
        // (Seedance doesn't know who "Ren" is — describe what we see)
        $action = $direction;
        foreach ($detectedChars as $charName) {
            foreach ($characters as $char) {
                if (strtolower($char['name']) === strtolower($charName)) {
                    $brief = $this->getCharacterVisualBrief($char);
                    // Replace "REN" or "Ren" with brief descriptor (first occurrence only)
                    $action = preg_replace('/\b' . preg_quote($charName, '/') . '\b/i', $brief, $action, 1);
                    break;
                }
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

        // Extract the most distinctive visual trait
        $desc = $char['description'] ?? '';
        $parts = array_map('trim', explode(',', $desc));
        foreach ($parts as $part) {
            if (preg_match('/\b(cybernetic|implant|scar|tattoo|visor|prosthetic|augmented|glowing|silver|chrome)\b/i', $part)) {
                return $subject . ' with ' . strtolower(trim($part));
            }
        }
        // Fallback: use hair descriptor
        foreach ($parts as $part) {
            if (preg_match('/\b(hair|bald|shaved|dreadlocks|braids|mohawk)\b/i', $part)) {
                return $subject . ' with ' . strtolower(trim($part));
            }
        }

        return $subject;
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
        // Start with framing as the shot instruction
        $prompt = $framing;

        // Weave direction (the main visual description from the screenplay)
        if (!empty($direction)) {
            $prompt .= '. ' . $direction;
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

        // Inject character visual traits naturally
        if (!empty($detectedChars)) {
            $charDescriptions = [];
            foreach ($detectedChars as $charName) {
                foreach ($characters as $char) {
                    if (strtolower($char['name']) === strtolower($charName)) {
                        $charDescriptions[] = $this->condensCharacterDescription($char['description']);
                        break;
                    }
                }
            }
            if (!empty($charDescriptions)) {
                $charText = implode(', ', $charDescriptions);
                if (!$this->alreadyContainsTerms($prompt, $charText)) {
                    $prompt = rtrim($prompt, '. ') . '. Character: ' . $charText;
                }
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
