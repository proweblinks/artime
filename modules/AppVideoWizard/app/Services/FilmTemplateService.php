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
- Character dialogue: CHARACTER_NAME: "Spoken dialogue here"
- Scene directions: [Scene: Description of what we see, setting, action, atmosphere]
- NO narrator. ALL spoken content = character dialogue only.
- Scene directions describe visuals only — they are NOT spoken by anyone.
- Each [Scene: ...] block starts a new visual scene.
- Keep dialogue punchy and cinematic — short lines, not monologues.

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
  "transcript": "The complete screenplay text with [Scene: ...] markers and CHARACTER: dialogue",
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
     * Get comma-separated character names for prompt.
     */
    protected function getCharacterNameList(array $template): string
    {
        return implode(', ', array_map(fn ($c) => strtoupper($c['name']), $template['characters']));
    }
}
