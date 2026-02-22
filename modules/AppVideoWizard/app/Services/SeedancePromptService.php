<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwCameraMovement;
use Modules\AppVideoWizard\Models\VwSeedanceStyle;
use Modules\AppVideoWizard\Models\VwSetting;

/**
 * SeedancePromptService — Seedance-native four-part prompt builder.
 *
 * Builds prompts in the Subject → Action → Camera → Style format
 * recommended by the Seedance 2.0 prompt guide, replacing the legacy
 * 7-layer concatenation pipeline.
 */
class SeedancePromptService
{
    /**
     * Fallback action library organized by shot type.
     * Single present-tense verbs — not emotional prose.
     */
    protected const ACTION_FALLBACKS = [
        'establishing'     => ['surveys the landscape', 'enters the scene slowly', 'approaches from the distance'],
        'extreme-wide'     => ['stands in the vast space', 'moves through the environment', 'emerges from the distance'],
        'wide'             => ['walks forward deliberately', 'crosses the open space', 'stands at the edge'],
        'full'             => ['walks forward with purpose', 'stands facing the scene', 'pauses mid-stride'],
        'medium'           => ['turns to face the light', 'reaches for an object', 'leans against the surface'],
        'medium-close'     => ['pauses mid-motion', 'glances over one shoulder', 'lifts a hand slowly'],
        'close-up'         => ['breathes steadily', 'blinks slowly', 'tilts head to one side'],
        'extreme-close-up' => ['focuses intently', 'swallows nervously', 'narrows eyes slightly'],
        'reaction'         => ['freezes momentarily', 'steps back suddenly', 'inhales sharply'],
        'detail'           => ['rotates slowly in the light', 'rests on the surface', 'catches the light'],
        'pov'              => ['scans the environment', 'moves through the doorway', 'looks down at hands'],
        'over-shoulder'    => ['speaks while gesturing', 'listens intently', 'nods slowly'],
    ];

    /**
     * Build a complete Seedance-native prompt from shot data and context.
     *
     * @param array $shot Shot data (type, subjectAction, description, cameraMovement, etc.)
     * @param array $context Scene context (mood, genre, narration, characterBible, etc.)
     * @return array {success, prompt, parts: {subject, action, camera, style, audio}, version, metadata}
     */
    public function buildPrompt(array $shot, array $context): array
    {
        try {
            $subject = $this->buildSubjectPart($shot, $context);
            $action = $this->buildActionPart($shot, $context);
            $camera = $this->buildCameraPart($shot, $context);
            $style = $this->buildStylePart($shot, $context);
            $audio = $this->buildAudioDirection($shot, $context);

            // Assemble: Subject + Action. Camera. Style. Audio.
            $parts = array_filter([$subject . ' ' . $action, $camera, $style]);
            $prompt = implode('. ', $parts) . '.';

            if (!empty($audio)) {
                $prompt .= ' ' . $audio;
            }

            // Clean up
            $prompt = preg_replace('/\.\s*\./', '.', $prompt);
            $prompt = preg_replace('/\s{2,}/', ' ', $prompt);
            $prompt = trim($prompt);

            return [
                'success' => true,
                'prompt' => $prompt,
                'parts' => [
                    'subject' => $subject,
                    'action' => $action,
                    'camera' => $camera,
                    'style' => $style,
                    'audio' => $audio,
                ],
                'version' => $this->getActiveVersion(),
                'metadata' => [
                    'format' => 'four_part',
                    'shot_type' => $shot['type'] ?? $shot['shotType'] ?? 'medium',
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('SeedancePromptService::buildPrompt failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'prompt' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the Subject part. For I2V: "The subject" (Seedance best practice).
     */
    public function buildSubjectPart(array $shot, array $context): string
    {
        // For image-to-video, the image IS the subject. Seedance docs recommend "The subject".
        $characters = $shot['charactersInShot'] ?? $shot['characters'] ?? [];

        if (count($characters) > 1) {
            return 'The subjects';
        }

        // If character bible has a name, use it
        $characterBible = $context['characterBible'] ?? [];
        if (!empty($characters) && !empty($characterBible)) {
            $charName = is_array($characters[0]) ? ($characters[0]['name'] ?? null) : $characters[0];
            if ($charName) {
                return 'The subject';
            }
        }

        return 'The subject';
    }

    /**
     * Build the Action part. ONE clear verb, present tense.
     *
     * Priority: shot['subjectAction'] → extractActualStoryAction() → narration extraction → fallback.
     */
    public function buildActionPart(array $shot, array $context): string
    {
        // Priority 1: Explicit subjectAction from decomposition
        $subjectAction = $shot['subjectAction'] ?? '';
        if (!empty($subjectAction) && strlen($subjectAction) > 5) {
            return $this->cleanActionVerb($subjectAction);
        }

        // Priority 2: Extract from shot description
        $description = $shot['description'] ?? '';
        if (!empty($description) && strlen($description) > 10) {
            $extracted = $this->extractActionFromDescription($description);
            if ($extracted) {
                return $extracted;
            }
        }

        // Priority 3: Extract from scene narration
        $narration = $context['narration'] ?? '';
        if (!empty($narration) && strlen($narration) > 10) {
            $extracted = $this->extractActionFromDescription($narration);
            if ($extracted) {
                return $extracted;
            }
        }

        // Priority 4: Fallback library by shot type
        $shotType = $shot['type'] ?? $shot['shotType'] ?? 'medium';
        return $this->getFallbackAction($shotType);
    }

    /**
     * Build the Camera part: [Shot size] + [Movement] + [Angle] + [Lens type].
     */
    public function buildCameraPart(array $shot, array $context): string
    {
        $shotType = $shot['type'] ?? $shot['shotType'] ?? 'medium';

        // Try to use Seedance prompt syntax from DB movement
        $movementSlug = $shot['movementSlug'] ?? null;
        $cameraMovement = $shot['cameraMovement'] ?? [];

        if (is_array($cameraMovement) && !empty($cameraMovement['type'])) {
            $movementSlug = $movementSlug ?? $cameraMovement['type'];
        } elseif (is_string($cameraMovement) && !empty($cameraMovement)) {
            $movementSlug = $movementSlug ?? $cameraMovement;
        }

        // Also check seedanceCameraMove (from UI picker)
        $seedanceMove = $shot['seedanceCameraMove'] ?? null;
        if ($seedanceMove && $seedanceMove !== 'none') {
            $movementSlug = $this->mapMovementToSeedance($seedanceMove) ?: $movementSlug;
            // Map back to a DB slug that has seedance_prompt_syntax
            $movementSlug = $seedanceMove;
        }

        // Look up Seedance prompt syntax from DB
        $seedanceSyntax = null;
        if ($movementSlug) {
            $dbMovement = VwCameraMovement::getBySlug($movementSlug);
            if ($dbMovement && !empty($dbMovement['seedancePromptSyntax'])) {
                $seedanceSyntax = $dbMovement['seedancePromptSyntax'];
            }
        }

        // Build shot size from shot type
        $shotSize = $this->mapShotTypeToSize($shotType);

        if ($seedanceSyntax) {
            return "{$shotSize}, {$seedanceSyntax}";
        }

        // Construct manually: size + default movement
        return "{$shotSize}, Eye-level, Normal lens";
    }

    /**
     * Build the Style part: [Visual anchor] + [Lighting] + [Color treatment].
     */
    public function buildStylePart(array $shot, array $context): string
    {
        $parts = [];

        // Visual style
        $visualSlug = VwSetting::getValue('seedance_default_visual_style', 'cinematic');
        $visualStyle = VwSeedanceStyle::getBySlug($visualSlug);
        if ($visualStyle) {
            $parts[] = $visualStyle['promptSyntax'];
        } else {
            $parts[] = 'Cinematic, photorealistic';
        }

        // Lighting
        $lightingSlug = VwSetting::getValue('seedance_default_lighting', 'natural-window-light');
        $lightingStyle = VwSeedanceStyle::getBySlug($lightingSlug);
        if ($lightingStyle) {
            $parts[] = $lightingStyle['promptSyntax'];
        }

        // Color treatment (optional)
        $colorSlug = VwSetting::getValue('seedance_default_color_treatment', '');
        if (!empty($colorSlug)) {
            $colorStyle = VwSeedanceStyle::getBySlug($colorSlug);
            if ($colorStyle) {
                $parts[] = $colorStyle['promptSyntax'];
            }
        }

        // Genre-specific override if context provides one
        $genre = $context['genre'] ?? '';
        if (!empty($genre) && empty($colorSlug)) {
            $genreAtmosphere = $context['atmosphere'] ?? '';
            if (!empty($genreAtmosphere) && strlen($genreAtmosphere) < 50) {
                $parts[] = $genreAtmosphere;
            }
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Build context-aware audio direction.
     *
     * Replaces brute-force anti-speech with genre-aware ambient descriptions.
     */
    public function buildAudioDirection(array $shot, array $context): string
    {
        // If shot has dialogue/lip-sync, no audio direction needed
        $needsLipSync = $shot['needsLipSync'] ?? false;
        $hasDialogue = $shot['hasDialogue'] ?? false;
        if ($needsLipSync || $hasDialogue) {
            return '';
        }

        // Check if audio direction is enabled
        $enabled = VwSetting::getValue('seedance_audio_direction_enabled', true);
        if (!$enabled) {
            return 'No speech, no dialogue, no voiceover, no dubbing, no singing, no spoken words. Sound effects and ambient audio only.';
        }

        // Extract environmental cues from visual description
        $description = strtolower($shot['description'] ?? $shot['visualDescription'] ?? '');
        $ambientCues = $this->extractAmbientCues($description);

        if (!empty($ambientCues)) {
            return "No music. Only {$ambientCues}.";
        }

        // Generic fallback
        return 'Sound effects and ambient audio only. No speech.';
    }

    /**
     * Final assembly before API call. Handles music stripping, camera injection,
     * style anchor, chaos mode, and audio direction.
     *
     * Replaces assembleSeedancePrompt() in VideoWizard.php.
     */
    public function assemble(string $basePrompt, array $shot, string $version = '1.5'): string
    {
        $prompt = trim($basePrompt);
        $chaosMode = $shot['seedanceChaosMode'] ?? false;

        // Strip background music references
        $bgMusic = $shot['seedanceBackgroundMusic'] ?? false;
        if (!$bgMusic) {
            $prompt = preg_replace(
                '/\b(upbeat|dramatic|energetic|soft|gentle|intense|epic|cinematic|rhythmic|pulsing|driving|ambient|orchestral|electronic|funky|jazzy|lively|triumphant|suspenseful|melancholic|cheerful|playful)?\s*(background\s+)?music\s+(plays?|playing|in the background|throughout|swells?|builds?|intensifies|fades?|loops?|accompanies)\b[^.]*\.\s*/i',
                '',
                $prompt
            );
            $prompt = preg_replace(
                '/\b(soundtrack|musical score|score plays|orchestral score|beat drops|bass drops|drum beat|rhythm plays)\b[^.]*\.\s*/i',
                '',
                $prompt
            );
        } else {
            $musicMood = $shot['musicMood'] ?? '';
            $musicDesc = !empty($musicMood) ? ucfirst($musicMood) : 'Energetic';
            $prompt .= " {$musicDesc} background music playing throughout.";
        }

        // Camera: Chaos Mode auto-forces handheld+dynamic
        if ($chaosMode) {
            $prompt .= ' Shaky handheld camera rapidly whip-pans and jerks violently tracking the chaos.';
        } else {
            // In four_part mode, camera is already in the prompt from buildCameraPart.
            // In legacy mode, inject camera instruction here.
            $format = VwSetting::getValue('seedance_prompt_format', 'four_part');
            if ($format !== 'four_part') {
                $cameraMove = $shot['seedanceCameraMove'] ?? 'none';
                $intensity = $shot['seedanceCameraMoveIntensity'] ?? 'moderate';
                if ($cameraMove !== 'none') {
                    $cameraInstruction = $this->getSeedanceCameraInstruction($cameraMove, $intensity);
                    if ($cameraInstruction) {
                        $prompt .= ' ' . $cameraInstruction;
                    }
                }
            }
        }

        // Style anchor (ensure present)
        if (!preg_match('/cinematic|photorealistic/i', $prompt)) {
            $prompt .= ' Cinematic, photorealistic.';
        }

        // Audio direction (append if not already present)
        $hasAudioDirection = preg_match('/\b(no speech|ambient audio only|no music\. only)\b/i', $prompt);
        if (!$hasAudioDirection) {
            $audioDirection = $this->buildAudioDirection($shot, []);
            if (!empty($audioDirection)) {
                $prompt .= ' ' . $audioDirection;
            }
        }

        return trim($prompt);
    }

    // =========================================================================
    // Compliance methods (consolidated from ConceptService)
    // =========================================================================

    /**
     * Sanitize a prompt for Seedance compliance.
     */
    public static function sanitize(string $text): string
    {
        // Phase 1: Fix compound phrases
        $compounds = [
            '/\brazor[\s-]*sharp\b/i' => 'sharp',
            '/\brazor\s+claws\b/i' => 'sharp claws',
        ];
        foreach ($compounds as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Phase 2: Replace passive/weak verbs
        $passiveReplacements = [
            '/\bnestled\b/i' => 'pressing',
            '/\bnestling\b/i' => 'pressing',
            '/\bbegins to\b/i' => '',
            '/\bstarts to\b/i' => '',
        ];
        foreach ($passiveReplacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Phase 3: Remove banned facial expression descriptions
        $facialPatterns = [
            '/,?\s*\beyes?\s+(?:crinkling|crinkel|crinkle|crinkled|widening|widen|widened|narrowing|narrow|narrowed|squinting|squint|twinkling|twinkle|sparkling|sparkle|gleaming|gleam|glinting|glint)\s*\w*/i' => '',
            '/,?\s*\beyes?\s+wide\s*\w*/i' => '',
            '/,?\s*\beyes?\s+(?:stare|stares?|staring|gaze|gazes?|gazing|peer|peers?|peering|glare|glares?|glaring|look|looks?|looking)\b[^,.]*[.,]?/i' => '',
            '/,?\s*(?:with\s+)?(?:crinkled|squinted|narrowed|widened|teary|watery|half-closed|droopy)\s+eyes?\b[^,.]*[.,]?/i' => '',
            '/,?\s*\bmouth\s+(?:curves?|twists?|forms?|breaks?|turns?)\s+(?:wide\s+)?(?:into|in)\s+(?:\w+\s+){0,2}(?:smile|grin|frown|smirk)/i' => '',
            '/,?\s*\bmouth\s+curves?\s+wide\s+in\s+\w+[^.]*(?=\.)/i' => '',
            '/\beyes\s+lock\s+on\s+[^,.]*(?:glint|gleam|look|gaze|stare)\b/i' => '',
            '/\bbrows?\s+(?:furrowing|furrowed|knitting|knitted|raised|raising)\b/i' => '',
            '/\bjaws?\s+(?:clenching|clenched|dropping|dropped|setting|set)\b/i' => '',
            '/\bin\s+(?:amusement|delight|horror|disgust|surprise|wonder|disbelief|shock)\b/i' => '',
            '/\bwith\s+(?:a\s+)?(?:\w+\s+)?(?:glint|grin|smirk|sneer)\b/i' => '',
            '/\bexpression\s+(?:shifts?|changes?)\s+to\s+[^,.]*(?:smile|grin|frown|smirk)/i' => '',
            '/\b(?:\w+\s+)?(?:toothless|wide|bright|warm|soft|gentle|sly|knowing|wicked)\s+smile\b/i' => '',
            '/\bbreaks?\s+into\s+(?:a\s+)?(?:smile|grin|laugh)\b/i' => '',
            '/,?\s*eyes?\s+(?:looking|gazing|staring|glancing|locked|fixed|focused|trained)\s+(?:\w+\s+)?(?:at|on|toward|towards)\s+(?:the\s+)?camera\b[^,.]*[.,]?/i' => '',
            '/,?\s*(?:looking|facing|turning|glancing|locked|fixed|focused)\s+(?:at|on|toward|towards)\s+(?:the\s+)?camera\b/i' => '',
            '/\s+(?:at|to|toward|towards)\s+(?:the\s+)?camera\b/i' => '',
            '/,?\s*(?:maintaining|making|holding|with)\s+(?:direct\s+)?eye\s+contact\b[^,.]*[.,]?/i' => '',
            '/,?\s*(?:with\s+)?cheeks?\s+(?:puffing|puffed|bulging|inflating|inflated)\b[^,.]*[.,]?/i' => '',
            '/,?\s*\b(?:smiles?|smiled|smiling)\s+\w*\s*(?:with\s+\w+)?/i' => '',
            '/,?\s*\bface\s+(?:transforms?|changes?|shifts?|contorts?|morphs?|lights?\s+up)\s*\w*[^,.]*[.,]?/i' => '',
            '/\bmouth\s+(?:forms?|makes?|creates?)\s+(?:a?\s*)?(?:shape|circle|oval|o\b)[^,.]*[.,]?/i' => 'mouth opens',
            '/\bwith\s+(?:joy|delight|glee|satisfaction|pleasure|excitement|enthusiasm|pride|happiness)\b/i' => 'powerfully',
            '/\bin\s+(?:laugh|laughter|giggle|giggling)\s+with\s+[^,.]+/i' => 'producing crazy giggle',
            '/,?\s*\bface\s+(?:brightens?|glows?|beams?|softens?|hardens?|relaxes?|tenses?|scrunches?|crumples?|falls?)\b[^,.]*[.,]?/i' => '',
            '/,?\s*\blooks?\s+(?:satisfied|happy|pleased|guilty|innocent|content|proud|sad|angry|worried|confused|surprised|shocked|terrified|bored|amused|annoyed|disgusted|excited)\b[^,.]*[.,]?/i' => '',
            '/\b(?:sleeping|resting|dozing|napping)\s+(?=(?:mother|father|woman|man|person|baby|infant|child))/i' => '',
        ];
        foreach ($facialPatterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Phase 4: Remove appearance/clothing descriptions
        $appearancePatterns = [
            '/,?\s*wrapped\s+(?:from|around)\s+[^,.]+/i' => '',
            '/,?\s*(?:with\s+)?(?:food|sauce|liquid|cream|crumbs?)\s+(?:residue\s+)?(?:smudged|splattered|dripping|stuck|remaining|visible|smeared|caked)\s+(?:on|around|over)\s+[^,.]+/i' => '',
            '/,?\s*(?:with\s+)?(?:food|sauce|liquid|cream|crumbs?)\s+residue\b[^,.]*[.,]?/i' => '',
            '/,?\s*(?:wearing|dressed\s+in|clad\s+in)\s+[^,.]+/i' => '',
            '/,?\s*(?:in\s+)?(?:a\s+)?(?:white|blue|red|black|green|pink|yellow|brown)\s+(?:shirt|jacket|hoodie|polo|sweater|dress|gown|towel|blanket)\b/i' => '',
            '/\b(?:brightly|dimly|softly|warmly|harshly)\s+lit\b/i' => '',
            '/\blit\s+(?=(?:hospital|room|space|corridor|hallway|ward|chamber|studio|kitchen|office|area))/i' => '',
            '/\bbright\s+(?=\w)/i' => '',
            '/\bclear\s+(?=(?:plastic|glass|shhh|shush|gesture|chewing|slapping|tapping|sound))/i' => '',
        ];
        foreach ($appearancePatterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Phase 5: Fix dangling "wrapped"
        $text = preg_replace('/\b(?<!un)wrapped(?!\s+(?:around|in|from|shawarma|food|burger|wrap))\s*,/i', ',', $text);

        // Phase 6: Clean up artifacts
        $text = preg_replace('/,\s*,\s*,/i', ',', $text);
        $text = preg_replace('/,\s*,/', ',', $text);
        $text = preg_replace('/\s+([.,!])/', '$1', $text);
        $text = preg_replace('/,\./', '.', $text);
        $text = preg_replace('/\.\s*,/', '.', $text);
        $text = preg_replace('/\.{2,}/', '.', $text);
        $text = preg_replace('/,\s*\./', '.', $text);

        // Phase 7: Deduplicate adverbs
        $text = self::deduplicateAdverbs($text);

        $text = preg_replace('/\s{2,}/', ' ', $text);
        return trim($text);
    }

    /**
     * Deduplicate overused Seedance adverbs using count-aware replacement.
     */
    public static function deduplicateAdverbs(string $text): string
    {
        $allAdverbs = ['crazily', 'violently', 'rapidly', 'intensely', 'slowly', 'gently', 'steadily', 'smoothly'];

        $counts = [];
        foreach ($allAdverbs as $adv) {
            $counts[$adv] = preg_match_all('/\b' . $adv . '\b/i', $text);
        }

        foreach ($allAdverbs as $adverb) {
            if ($counts[$adverb] <= 2) continue;

            $available = array_filter($allAdverbs, fn($a) => $a !== $adverb && $counts[$a] < 2);
            if (empty($available)) continue;

            $available = array_values($available);
            $n = 0;
            $altIdx = 0;
            $text = preg_replace_callback('/\b' . $adverb . '\b/i', function ($match) use (&$n, &$altIdx, $available, &$counts, $adverb) {
                $n++;
                if ($n > 2) {
                    $alt = $available[$altIdx % count($available)];
                    $altIdx++;
                    $counts[$alt]++;
                    $counts[$adverb]--;
                    return $alt;
                }
                return $match[0];
            }, $text);
        }
        return $text;
    }

    /**
     * Validate a prompt for Seedance compliance.
     */
    public function validate(string $prompt, int $teamId, string $engine = 'economy'): array
    {
        // Delegate to ConceptService for AI-powered validation (preserves existing logic)
        try {
            $conceptService = app(ConceptService::class);
            return $conceptService->validateSeedanceCompliance($prompt, $teamId, $engine);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Seedance technical rules for prompt building.
     */
    public function getTechnicalRules(string $version = '1.5'): string
    {
        return <<<'RULES'
SEEDANCE VIDEO PROMPT RULES:

ADVERBS — Use natural, descriptive adverbs freely:
- High intensity: rapidly, violently, crazily, intensely, aggressively, wildly, fiercely, powerfully
- Medium intensity: slowly, gently, steadily, smoothly, carefully, cautiously
- Temporal: suddenly, immediately, then, finally, instantly
- Place adverbs BEFORE or AFTER verbs naturally. Write as you would narrate the scene.

EXPLICIT MOTION — Seedance CANNOT infer motion:
Every movement must be EXPLICITLY described. The model will NOT animate what you don't write.
WRONG: "the cat attacks" (too vague — HOW does it attack?)
RIGHT: "the cat slaps the man's face with its right paw"
If a body part should move, DESCRIBE the motion. If an object should fly, DESCRIBE the trajectory.

DIALOGUE & SOUNDS — INCLUDE THEM:
- Include character dialogue in quotes: yells "Get off me!"
- Include character sounds: meows, yells, screams, growls, hisses
- Include environmental sounds caused by actions: crashes, clattering, shattering
- These help Seedance generate accurate audio and mouth movements.

CAMERA STYLE — Describe when relevant:
- "A chaotic, shaking handheld camera follows the action"
- "Smooth tracking shot" or "Static wide shot"
- Camera style helps set the visual tone.

PHYSICAL ACTION — SPECIFIC BODY PARTS:
GOOD: "slaps the man's face with its right paw"
GOOD: "lands violently on the man's left shoulder, its claws gripping wildly"
BAD: "the cat attacks him" (which body part? what motion? what gets hit?)

OBJECT DISPLACEMENT — ALWAYS INCLUDE:
When characters interact with objects during action, describe what happens.

FACE & IDENTITY PRESERVATION:
- Do NOT add face/identity prefix text — the source IMAGE defines the face.
- NEVER describe face structure changes.
- You may mention mouth opening for SPEAKING or SOUND PRODUCTION.

STYLE ANCHOR — ALWAYS end with: "Cinematic, photorealistic."

BANNED:
- No semicolons
- No appearance/clothing descriptions
- No facial micro-expression descriptions
- No passive voice — only active verbs
- No weak/generic verbs: "goes", "moves", "does", "gets", "starts", "begins"
- ABSOLUTELY NO background music descriptions.
RULES;
    }

    // =========================================================================
    // Camera mapping methods (consolidated from VideoWizard.php)
    // =========================================================================

    /**
     * Map a decomposition camera slug to a Seedance pill value.
     */
    public function mapMovementToSeedance(string $slug): string
    {
        $normalized = str_replace(' ', '-', strtolower(trim($slug)));

        $mapping = [
            'push-in'   => 'push-in',
            'pull-out'  => 'pull-out',
            'pan-left'  => 'pan-left',
            'pan-right' => 'pan-right',
            'orbit'     => 'orbit',
            'tracking'  => 'tracking',
            'handheld'  => 'handheld',
            'crane-up'  => 'crane-up',
            // Slug aliases
            'slow-push' => 'push-in',
            'slow-pan'  => 'pan-left',
            'dolly-in'  => 'push-in',
            'dolly-out' => 'pull-out',
            'dolly-zoom' => 'push-in',
            'zoom-in'   => 'push-in',
            'zoom-out'  => 'pull-out',
            'tilt-up'   => 'crane-up',
            'tilt-down' => 'crane-up',
            'crane'     => 'crane-up',
            'jib'       => 'crane-up',
            'steadicam' => 'tracking',
            'follow'    => 'tracking',
            'whip-pan'  => 'pan-left',
            'rack-focus' => 'none',
            'static'    => 'none',
            'hold'      => 'none',
            'locked'    => 'none',
            // Natural language from AI decomposition
            'gentle-push-in' => 'push-in',
            'gentle-pull-out' => 'pull-out',
            'gentle-pan' => 'pan-left',
            'subtle-movement' => 'handheld',
            'subtle-push' => 'push-in',
            'subtle-drift' => 'handheld',
            'drift' => 'handheld',
            'slow-drift' => 'handheld',
            'slight-push-in' => 'push-in',
            'slow-zoom-in' => 'push-in',
            'slow-zoom-out' => 'pull-out',
            'slow-orbit' => 'orbit',
            'slow-track' => 'tracking',
            'slow-tracking' => 'tracking',
            'pan' => 'pan-left',
            'push' => 'push-in',
            'pull' => 'pull-out',
            'dolly' => 'push-in',
            'zoom' => 'push-in',
            'tilt' => 'crane-up',
        ];

        return $mapping[$normalized] ?? 'none';
    }

    /**
     * Map intensity value to Seedance adverb.
     */
    public function mapIntensityToSeedance($intensity): string
    {
        if (is_numeric($intensity)) {
            $intensity = match (true) {
                $intensity <= 3 => 'subtle',
                $intensity <= 6 => 'moderate',
                $intensity <= 8 => 'dynamic',
                default => 'intense',
            };
        }

        $map = [
            'subtle' => 'subtle',
            'moderate' => 'moderate',
            'dynamic' => 'dynamic',
            'intense' => 'intense',
            'low' => 'subtle',
            'medium' => 'moderate',
            'high' => 'dynamic',
        ];

        return $map[strtolower($intensity)] ?? 'moderate';
    }

    /**
     * Get camera instruction string for legacy mode.
     */
    public function getSeedanceCameraInstruction(string $move, string $intensity): string
    {
        $adverbs = [
            'subtle'   => 'gently',
            'moderate' => 'slowly',
            'dynamic'  => 'rapidly',
        ];

        $adverb = $adverbs[$intensity] ?? 'slowly';

        $movements = [
            'push-in'   => "Camera {$adverb} pushes in toward the subject.",
            'pull-out'  => "Camera {$adverb} pulls back, revealing the scene.",
            'pan-left'  => "Camera pans {$adverb} to the left.",
            'pan-right' => "Camera pans {$adverb} to the right.",
            'orbit'     => "Camera {$adverb} orbits around the subject.",
            'tracking'  => "Camera tracks {$adverb} alongside the subject.",
            'handheld'  => "Handheld camera with organic, natural movement.",
            'crane-up'  => "Camera {$adverb} rises in a crane shot.",
        ];

        return $movements[$move] ?? '';
    }

    /**
     * Build timecoded prompt for Seedance v2.0 (inactive until v2.0 available).
     */
    public function buildTimecodedPrompt(array $segments, int $totalDuration): string
    {
        $parts = [];
        $currentTime = 0;

        foreach ($segments as $segment) {
            $segDuration = $segment['duration'] ?? 4;
            $endTime = min($currentTime + $segDuration, $totalDuration);
            $desc = $segment['description'] ?? '';

            $start = sprintf('%02d:%02d', floor($currentTime / 60), $currentTime % 60);
            $end = sprintf('%02d:%02d', floor($endTime / 60), $endTime % 60);

            $parts[] = "{$start}–{$end} – {$desc}";
            $currentTime = $endTime;
        }

        return implode('. ', $parts) . '.';
    }

    /**
     * Get the active Seedance version from settings.
     */
    public function getActiveVersion(): string
    {
        return VwSetting::getValue('seedance_active_version', '1.5');
    }

    /**
     * Check if v2.0 endpoint is configured and available.
     */
    public function isV2Available(): bool
    {
        $endpoint = VwSetting::getValue('seedance_v2_endpoint', '');
        return !empty($endpoint);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Clean an action string to a single present-tense verb phrase.
     */
    protected function cleanActionVerb(string $action): string
    {
        // Remove emotional prose markers
        $action = preg_replace('/\b(showing|displaying|exuding|emanating|radiating)\s+(steely|fierce|subtle|quiet|inner)\s+\w+/i', '', $action);
        $action = preg_replace('/\b(with|showing)\s+(determination|resilience|vulnerability|strength|emotion)\b/i', '', $action);

        // Clean up
        $action = preg_replace('/\s{2,}/', ' ', trim($action));
        $action = preg_replace('/^,\s*/', '', $action);

        // If still too long (>80 chars), truncate at first period or comma
        if (strlen($action) > 80) {
            $cut = strpos($action, '.');
            if ($cut === false || $cut > 80) {
                $cut = strpos($action, ',');
            }
            if ($cut !== false && $cut > 10) {
                $action = substr($action, 0, $cut);
            }
        }

        return trim($action);
    }

    /**
     * Extract a concrete action from a visual description.
     */
    protected function extractActionFromDescription(string $description): ?string
    {
        // Look for action verbs in the description
        $actionPatterns = [
            '/\b(walks|runs|stands|sits|turns|reaches|holds|picks up|puts down|lifts|drops|pushes|pulls|opens|closes|looks at|points|waves|nods|shakes)\b[^.]{0,40}/i',
            '/\b(the (?:subject|figure|person|character))\s+([\w\s]{5,40})/i',
        ];

        foreach ($actionPatterns as $pattern) {
            if (preg_match($pattern, $description, $match)) {
                $extracted = trim($match[0]);
                if (strlen($extracted) > 10 && strlen($extracted) < 80) {
                    return $extracted;
                }
            }
        }

        return null;
    }

    /**
     * Get a fallback action for a shot type.
     */
    protected function getFallbackAction(string $shotType): string
    {
        $actions = self::ACTION_FALLBACKS[$shotType] ?? self::ACTION_FALLBACKS['medium'];
        return $actions[array_rand($actions)];
    }

    /**
     * Map shot type to Seedance shot size bucket.
     */
    protected function mapShotTypeToSize(string $shotType): string
    {
        $sizeMap = [
            'establishing'     => 'Wide shot',
            'extreme-wide'     => 'Wide shot',
            'wide'             => 'Wide shot',
            'full'             => 'Full shot',
            'medium'           => 'Medium shot',
            'medium-close'     => 'Medium close-up',
            'close-up'         => 'Close-up',
            'extreme-close-up' => 'Extreme close-up',
            'reaction'         => 'Close-up',
            'detail'           => 'Extreme close-up',
            'pov'              => 'POV shot',
            'over-shoulder'    => 'Over-the-shoulder',
        ];

        return $sizeMap[$shotType] ?? 'Medium shot';
    }

    /**
     * Extract ambient sound cues from a visual description.
     */
    protected function extractAmbientCues(string $description): string
    {
        $cueMap = [
            'rain'      => 'ambient rain and distant thunder',
            'storm'     => 'thunder and heavy rainfall',
            'wind'      => 'natural wind and rustling',
            'ocean'     => 'ocean waves and seagulls',
            'sea'       => 'ocean waves crashing',
            'beach'     => 'waves and wind on sand',
            'forest'    => 'birds and rustling leaves',
            'city'      => 'distant traffic and urban hum',
            'street'    => 'footsteps and ambient city noise',
            'office'    => 'quiet keyboard clicks and air conditioning hum',
            'kitchen'   => 'sizzling and clinking dishes',
            'fire'      => 'crackling fire',
            'night'     => 'crickets and distant nighttime ambiance',
            'snow'      => 'quiet crunching snow underfoot',
            'desert'    => 'wind over sand and distant silence',
            'water'     => 'flowing water and gentle splashing',
            'garden'    => 'birds chirping and gentle breeze',
            'crowd'     => 'murmuring crowd ambiance',
            'car'       => 'engine hum and road noise',
            'church'    => 'quiet reverberant space',
            'hospital'  => 'quiet beeping monitors and soft footsteps',
            'warehouse' => 'echoing footsteps in empty space',
        ];

        $found = [];
        foreach ($cueMap as $keyword => $cue) {
            if (str_contains($description, $keyword)) {
                $found[] = $cue;
                if (count($found) >= 2) break;
            }
        }

        return implode(' and ', $found);
    }
}
