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
            'transitions' => ['default' => 'fadeblack', 'action' => 'wipeleft', 'dialogue' => 'dissolve'],
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

        // Track camera picks per scene type to cycle through options
        $cameraCounters = [];

        $visualScript = [];
        foreach ($scenes as $i => $scene) {
            $direction = $scene['direction'] ?? '';
            $body = $scene['text'] ?? '';
            $isVisualOnly = !empty($scene['is_visual_only']);

            $sceneType = $this->detectSceneType($i, $total, $direction, $isVisualOnly, $body);
            $detectedChars = $this->detectCharactersInScene($body, $characters);
            $mood = $this->detectMood($direction, $body, $sceneType);

            // --- Image prompt ---
            $imagePrompt = '';
            if ($imagePrefix) {
                $imagePrompt .= $imagePrefix . '. ';
            }
            if ($direction) {
                $imagePrompt .= $direction . '. ';
            }
            if ($imageSuffix) {
                $imagePrompt .= $imageSuffix;
            }

            // Inject character visual identity for detected characters
            if (!empty($detectedChars)) {
                $charDescriptions = [];
                foreach ($detectedChars as $charName) {
                    foreach ($characters as $char) {
                        if (strtolower($char['name']) === strtolower($charName)) {
                            $charDescriptions[] = "{$char['name']}: {$char['description']}";
                            break;
                        }
                    }
                }
                if (!empty($charDescriptions)) {
                    $imagePrompt .= "\n\nCHARACTER VISUAL IDENTITY (maintain exact appearance):\n" . implode("\n", $charDescriptions);
                }
            }

            // --- Video action ---
            $videoAction = $direction;
            if ($isVisualOnly && $atmosphere) {
                $videoAction .= '. ' . $atmosphere;
            }

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

            $visualScript[] = [
                'image_prompt' => trim($imagePrompt),
                'video_action' => trim($videoAction),
                'camera_motion' => $cameraMotion,
                'mood' => $mood,
                'voice_emotion' => $mood,
                'characters_in_scene' => $detectedChars,
                'transition_type' => $transitionType,
                'transition_duration' => $transitionDuration,
            ];
        }

        return $visualScript;
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
     * Detect which template characters appear in the scene body.
     */
    public function detectCharactersInScene(string $body, array $characters): array
    {
        $found = [];
        foreach ($characters as $char) {
            $name = strtoupper($char['name']);
            // Match "CHARACTER:" dialogue pattern
            if (preg_match('/\b' . preg_quote($name, '/') . '\s*:/i', $body)) {
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
}
