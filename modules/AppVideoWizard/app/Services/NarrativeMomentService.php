<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use App\Services\GeminiService;

/**
 * NarrativeMomentService - Hollywood-Informed Narrative Decomposition
 *
 * Transforms narration into distinct micro-moments for cinematic storytelling.
 * Based on Hollywood pattern analysis (Moon Knight 359 frames):
 * - Each moment = unique verb + context
 * - Emotional intensity assigned per moment
 * - Shot type derived from intensity
 *
 * Hollywood Standard: EVERY shot must capture a DIFFERENT moment in the narrative.
 * Example: "Jack arrives → spots someone → chases → loses them"
 * NOT: Same action repeated across all shots
 */
class NarrativeMomentService
{
    protected ?GeminiService $geminiService = null;

    /**
     * PHASE 5: Keywords that indicate climax/peak moments in narrative.
     */
    protected const CLIMAX_KEYWORDS = [
        // Action peaks
        'reveals', 'discovers', 'confronts', 'attacks', 'escapes',
        'transforms', 'explodes', 'crashes', 'collapses', 'breaks',

        // Emotional peaks
        'screams', 'cries', 'confesses', 'declares', 'realizes',
        'understands', 'accepts', 'rejects', 'forgives', 'betrays',

        // Narrative turns
        'finally', 'suddenly', 'at last', 'the truth', 'everything changes',
        'no turning back', 'moment of truth', 'now or never',

        // Conflict peaks
        'showdown', 'battle', 'fight', 'duel', 'confrontation',
        'standoff', 'face to face', 'ultimate', 'final',
    ];

    /**
     * PHASE 5: Keywords that indicate resolution/falling action.
     */
    protected const RESOLUTION_KEYWORDS = [
        'peace', 'calm', 'quiet', 'settles', 'rests', 'heals',
        'forgiven', 'reconciled', 'together', 'home', 'safe',
        'aftermath', 'later', 'eventually', 'in the end',
    ];

    /**
     * PHASE 5: Arc shape templates for different narrative styles.
     * Values represent target intensities at 0%, 25%, 50%, 75%, 100% of narrative.
     */
    protected const ARC_TEMPLATES = [
        // Standard Hollywood three-act structure
        'hollywood' => [
            0.0 => 0.35,   // Setup
            0.25 => 0.50,  // Rising action
            0.50 => 0.65,  // Midpoint turn
            0.70 => 0.85,  // Climax
            0.85 => 0.60,  // Falling action
            1.0 => 0.40,   // Resolution
        ],

        // Action-heavy with multiple peaks
        'action' => [
            0.0 => 0.40,
            0.20 => 0.70,  // Early action
            0.40 => 0.55,  // Brief lull
            0.60 => 0.80,  // Major setpiece
            0.80 => 0.90,  // Climax
            1.0 => 0.45,
        ],

        // Drama with slow build
        'drama' => [
            0.0 => 0.25,
            0.30 => 0.35,
            0.50 => 0.50,
            0.75 => 0.80,  // Late climax
            0.90 => 0.70,
            1.0 => 0.35,
        ],

        // Thriller with sustained tension
        'thriller' => [
            0.0 => 0.50,
            0.25 => 0.65,
            0.50 => 0.70,
            0.75 => 0.85,
            0.90 => 0.80,  // Sustained high
            1.0 => 0.50,
        ],

        // Comedy with lighter tone
        'comedy' => [
            0.0 => 0.40,
            0.25 => 0.50,
            0.50 => 0.55,
            0.70 => 0.65,
            0.85 => 0.55,
            1.0 => 0.50,
        ],

        // Flat/documentary style
        'documentary' => [
            0.0 => 0.45,
            0.25 => 0.50,
            0.50 => 0.55,
            0.75 => 0.55,
            1.0 => 0.50,
        ],
    ];

    /**
     * Emotion to intensity mapping based on Hollywood cinematography analysis.
     * Higher intensity = tighter framing (close-up at climax)
     */
    protected const EMOTION_INTENSITY_MAP = [
        // Low intensity (0.1-0.3) - Wide/Establishing shots
        'calm' => 0.2,
        'arrival' => 0.25,
        'observation' => 0.2,
        'anticipation' => 0.3,
        'contemplation' => 0.25,
        'peace' => 0.15,
        'reflection' => 0.2,

        // Medium-Low intensity (0.3-0.45) - Wide to Medium shots
        'curiosity' => 0.4,
        'conversation' => 0.45,
        'engagement' => 0.5,
        'interest' => 0.35,
        'awareness' => 0.35,

        // Medium intensity (0.45-0.6) - Medium/OTS shots
        'recognition' => 0.55,
        'concern' => 0.6,
        'focus' => 0.5,
        'attention' => 0.5,
        'dialogue' => 0.45,
        'interaction' => 0.5,

        // High intensity (0.6-0.8) - MCU/CU shots
        'tension' => 0.7,
        'urgency' => 0.75,
        'frustration' => 0.7,
        'determination' => 0.75,
        'realization' => 0.8,
        'surprise' => 0.75,
        'anger' => 0.75,
        'sadness' => 0.7,
        'chase' => 0.8,
        'action' => 0.75,

        // Peak intensity (0.8-1.0) - CU/XCU shots
        'fear' => 0.85,
        'confrontation' => 0.9,
        'revelation' => 0.95,
        'climax' => 1.0,
        'shock' => 0.9,
        'despair' => 0.85,
        'triumph' => 0.9,
        'resolution' => 0.5, // Resolution drops intensity
    ];

    /**
     * Action verb to emotion mapping.
     * Used to infer emotional state from action words.
     */
    protected const ACTION_EMOTION_MAP = [
        // Movement - entry/observation
        'arrives' => 'arrival',
        'enters' => 'arrival',
        'walks' => 'calm',
        'approaches' => 'anticipation',
        'moves' => 'focus',
        'steps' => 'focus',

        // Observation actions
        'spots' => 'recognition',
        'notices' => 'awareness',
        'sees' => 'recognition',
        'watches' => 'observation',
        'observes' => 'observation',
        'looks' => 'attention',
        'scans' => 'anticipation',
        'searches' => 'urgency',

        // High-energy movement
        'runs' => 'urgency',
        'chases' => 'chase',
        'races' => 'urgency',
        'rushes' => 'urgency',
        'dashes' => 'action',
        'sprints' => 'chase',
        'flees' => 'fear',
        'escapes' => 'fear',
        'pushes' => 'action',

        // Communication
        'speaks' => 'dialogue',
        'says' => 'dialogue',
        'tells' => 'conversation',
        'asks' => 'curiosity',
        'demands' => 'confrontation',
        'shouts' => 'urgency',
        'whispers' => 'tension',
        'confesses' => 'revelation',
        'reveals' => 'revelation',

        // Emotional response
        'realizes' => 'realization',
        'discovers' => 'revelation',
        'understands' => 'realization',
        'fears' => 'fear',
        'worries' => 'concern',
        'hesitates' => 'tension',
        'pauses' => 'tension',

        // Confrontation
        'confronts' => 'confrontation',
        'faces' => 'determination',
        'challenges' => 'confrontation',
        'fights' => 'action',
        'attacks' => 'action',
        'defends' => 'action',
        'stands' => 'determination',

        // Resolution
        'loses' => 'frustration',
        'fails' => 'despair',
        'succeeds' => 'triumph',
        'wins' => 'triumph',
        'accepts' => 'resolution',
        'leaves' => 'resolution',
        'departs' => 'resolution',
    ];

    public function __construct(?GeminiService $geminiService = null)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Decompose narration into distinct micro-moments.
     *
     * Based on Hollywood pattern analysis:
     * - Each moment = unique verb + context
     * - Emotional intensity assigned per moment
     * - Shot type derived from intensity
     *
     * INPUT: "Jack arrives in Shibuya, spots someone, chases them, loses them"
     * OUTPUT: [
     *   {action: "arrives in Shibuya", subject: "Jack", emotion: "anticipation", intensity: 0.3},
     *   {action: "spots someone in crowd", subject: "Jack", emotion: "recognition", intensity: 0.5},
     *   {action: "chases through crowd", subject: "Jack", emotion: "urgency", intensity: 0.8},
     *   {action: "loses them in darkness", subject: "Jack", emotion: "frustration", intensity: 0.7},
     * ]
     *
     * @param string $narration The full scene narration text
     * @param int $targetShotCount Target number of shots/moments to generate
     * @param array $context Additional context (characters, mood, etc.)
     * @return array Array of moment objects with action, subject, emotion, intensity
     */
    public function decomposeNarrationIntoMoments(string $narration, int $targetShotCount, array $context = []): array
    {
        // Try AI decomposition first for complex narratives
        if ($this->geminiService && strlen($narration) > 50 && $targetShotCount >= 3) {
            $aiMoments = $this->aiDecomposeNarration($narration, $targetShotCount, $context);
            if (!empty($aiMoments) && count($aiMoments) >= 2) {
                return $this->interpolateMoments($aiMoments, $targetShotCount);
            }
        }

        // Fall back to rule-based decomposition
        $moments = $this->ruleBasedDecomposition($narration, $context);

        // Interpolate to match target shot count
        $interpolated = $this->interpolateMoments($moments, $targetShotCount);
        return $this->deduplicateActions($interpolated);
    }

    /**
     * AI-powered narration decomposition using Gemini.
     */
    protected function aiDecomposeNarration(string $narration, int $targetShotCount, array $context = []): array
    {
        // Strip speech markers before sending to AI
        $narration = preg_replace('/\[(?:NARRATOR|INTERNAL_THOUGHT|[A-Z][A-Z_\s\']+)\]\s*/i', '', $narration);
        $narration = trim(preg_replace('/\s{2,}/', ' ', $narration));

        $characterNames = $context['characters'] ?? [];
        $mood = $context['mood'] ?? 'neutral';

        $prompt = <<<PROMPT
Decompose this narration into {$targetShotCount} distinct CINEMATIC MOMENTS for a video scene.

NARRATION: "{$narration}"

CHARACTERS: {$this->formatCharacterList($characterNames)}
MOOD: {$mood}

RULES (Hollywood Standard):
1. Each moment must be VISUALLY DISTINCT - different action, different framing opportunity
2. Extract the SUBJECT (who) and ACTION (what they do) for each moment
3. Identify the EMOTION driving each moment
4. Moments should flow as a narrative progression (not random order)
5. Include at least one CLIMAX moment (highest emotion)

OUTPUT FORMAT (JSON array):
[
  {
    "action": "arrives in Shibuya crossing",
    "subject": "Jack",
    "emotion": "anticipation",
    "visualDescription": "Jack steps into the chaotic Shibuya crossing, neon lights reflecting off wet pavement"
  },
  ...
]

Return ONLY the JSON array, no explanation.
PROMPT;

        try {
            $result = $this->geminiService->generateText(
                $prompt,
                2000,
                1,
                'text',
                ['temperature' => 0.7]
            );

            if (!$result['success'] || empty($result['result'])) {
                Log::warning('NarrativeMomentService: AI decomposition returned empty result');
                return [];
            }

            $responseText = is_array($result['result']) ? $result['result'][0] : $result['result'];

            // Extract JSON from response
            if (preg_match('/\[\s*\{.*\}\s*\]/s', $responseText, $matches)) {
                $moments = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($moments)) {
                    // Add intensity values based on emotion
                    foreach ($moments as &$moment) {
                        $emotion = strtolower($moment['emotion'] ?? 'neutral');
                        $moment['intensity'] = $this->emotionToIntensity($emotion);
                    }

                    Log::info('NarrativeMomentService: AI decomposed narration', [
                        'inputLength' => strlen($narration),
                        'momentCount' => count($moments),
                    ]);

                    return $moments;
                }
            }

            Log::warning('NarrativeMomentService: Failed to parse AI response', [
                'response' => substr($responseText, 0, 300),
            ]);

        } catch (\Exception $e) {
            Log::error('NarrativeMomentService: AI decomposition failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Rule-based narration decomposition when AI is unavailable.
     */
    protected function ruleBasedDecomposition(string $narration, array $context = []): array
    {
        // Strip speech markers before splitting into segments
        $narration = preg_replace('/\[(?:NARRATOR|INTERNAL_THOUGHT|[A-Z][A-Z_\s\']+)\]\s*/i', '', $narration);
        $narration = trim(preg_replace('/\s{2,}/', ' ', $narration));

        $moments = [];

        // Extract character name from narration or context
        $characterName = $this->extractCharacterName($narration, $context);

        // Split narration by natural breaks: commas, periods, "and", "then", semicolons
        $segments = preg_split(
            '/[,;.]+|\s+(?:and|then|but|while|as|before|after)\s+/i',
            $narration,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if (strlen($segment) < 5) {
                continue; // Skip very short fragments
            }

            // Extract action verb and context
            $action = $this->extractActionFromSegment($segment);
            if (empty($action)) {
                $action = $segment; // Use full segment if no action found
            }

            // Infer emotion from action verb
            $emotion = $this->inferEmotionFromAction($action);
            $intensity = $this->emotionToIntensity($emotion);

            // Build visual description
            $visualDescription = $this->buildVisualDescription($action, $characterName);

            $moments[] = [
                'action' => $action,
                'subject' => $characterName,
                'emotion' => $emotion,
                'intensity' => $intensity,
                'visualDescription' => $visualDescription,
            ];
        }

        // If no moments extracted, try meaningful extraction from narration
        if (empty($moments)) {
            $moments = $this->generateMeaningfulMomentsFromNarration($narration, 3, $context);

            if (!empty($moments)) {
                Log::info('NarrativeMomentService: Generated meaningful fallback moments', [
                    'count' => count($moments),
                    'actions' => array_column($moments, 'action'),
                ]);
            } else {
                // Last resort: use the narration itself as a single moment
                $moments[] = [
                    'action' => $this->extractFirstActionFromNarration($narration),
                    'subject' => $characterName,
                    'emotion' => 'focus',
                    'intensity' => 0.5,
                    'visualDescription' => $narration,
                    'source' => 'raw_narration',
                ];
            }
        }

        // Ensure intensity curve has variation (Hollywood standard)
        $moments = $this->applyEmotionalArc($moments);

        return $moments;
    }

    /**
     * Extract character name from narration or context.
     */
    protected function extractCharacterName(string $narration, array $context): string
    {
        // Try to find character name from context
        if (!empty($context['characters'])) {
            $chars = $context['characters'];
            if (is_array($chars) && !empty($chars[0])) {
                $char = is_array($chars[0]) ? ($chars[0]['name'] ?? null) : $chars[0];
                if ($char) {
                    return $char;
                }
            }
        }

        // Extract from narration using patterns
        if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)\s+(?:walks|stands|looks|sits|moves|arrives|enters|runs|speaks)/i', $narration, $matches)) {
            return $matches[1];
        }

        // Check for "he/she" to infer single character
        if (preg_match('/\b(he|she)\b/i', $narration)) {
            return 'the protagonist';
        }

        return 'the character';
    }

    /**
     * Extract action verb and context from a text segment.
     */
    protected function extractActionFromSegment(string $segment): ?string
    {
        // Look for action verb patterns
        $patterns = [
            // Subject + verb + object pattern
            '/([A-Z]?\w+)\s+(arrives?|enters?|walks?|runs?|spots?|sees?|chases?|finds?|loses?|meets?|speaks?|confronts?|faces?|flees?|escapes?|watches?|observes?|approaches?|attacks?|defends?|realizes?|discovers?)[^,.]*/i',
            // Verb + object pattern (implied subject)
            '/\b(arriving|entering|walking|running|spotting|seeing|chasing|finding|losing|meeting|speaking|confronting|facing|fleeing|escaping|watching|observing|approaching)[^,.]*/i',
            // Past tense actions
            '/(arrived|entered|walked|ran|spotted|saw|chased|found|lost|met|spoke|confronted|faced|fled|escaped|watched|observed|approached)[^,.]*/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $segment, $matches)) {
                return trim($matches[0]);
            }
        }

        return null;
    }

    /**
     * Infer emotion from action text.
     */
    protected function inferEmotionFromAction(string $action): string
    {
        $actionLower = strtolower($action);

        // Check each action verb mapping
        foreach (self::ACTION_EMOTION_MAP as $verb => $emotion) {
            if (preg_match('/\b' . $verb . '(s|ed|ing)?\b/i', $actionLower)) {
                return $emotion;
            }
        }

        // Default based on keywords
        if (preg_match('/\b(suddenly|quickly|urgently|frantically)\b/i', $actionLower)) {
            return 'urgency';
        }
        if (preg_match('/\b(slowly|carefully|quietly|gently)\b/i', $actionLower)) {
            return 'calm';
        }
        if (preg_match('/\b(alone|empty|silent|dark)\b/i', $actionLower)) {
            return 'tension';
        }

        return 'focus'; // Default neutral emotion
    }

    /**
     * Build visual description for a moment.
     */
    protected function buildVisualDescription(string $action, string $subject): string
    {
        // Clean up the action
        $action = trim($action);

        // If action already mentions the subject, use as-is
        if (stripos($action, $subject) !== false) {
            return ucfirst($action);
        }

        // Otherwise, prepend subject
        return "{$subject} {$action}";
    }

    /**
     * Apply emotional arc to ensure intensity variation (Hollywood standard).
     * Creates a build → peak → resolution pattern.
     *
     * PHASE 5: Updated to use intelligent climax detection instead of fixed 70%.
     */
    protected function applyEmotionalArc(array $moments): array
    {
        $count = count($moments);
        if ($count <= 1) {
            return $moments;
        }

        // Find the moment with highest intensity (natural climax)
        $maxIntensityIndex = 0;
        $maxIntensity = 0;
        foreach ($moments as $i => $moment) {
            if (($moment['intensity'] ?? 0) > $maxIntensity) {
                $maxIntensity = $moment['intensity'];
                $maxIntensityIndex = $i;
            }
        }

        // If all same intensity, create artificial arc
        $allSame = true;
        $firstIntensity = $moments[0]['intensity'] ?? 0.5;
        foreach ($moments as $moment) {
            if (abs(($moment['intensity'] ?? 0.5) - $firstIntensity) > 0.1) {
                $allSame = false;
                break;
            }
        }

        if ($allSame) {
            // PHASE 5: Use intelligent climax detection instead of fixed 70%
            $climaxData = $this->detectClimaxFromContent($moments);
            $climaxIndex = $climaxData['index'];

            // Store climax metadata for downstream use
            $climaxMetadata = [
                'method' => $climaxData['method'],
                'confidence' => $climaxData['confidence'],
                'isMultiClimax' => $climaxData['isMultiClimax'],
            ];

            // Apply intensity curve based on detected climax
            foreach ($moments as $i => &$moment) {
                $position = $i / max(1, $count - 1);

                if ($i === $climaxIndex) {
                    // Peak intensity at climax
                    $moment['intensity'] = max($moment['intensity'] ?? 0.5, 0.85);
                    $moment['isClimax'] = true;
                    $moment['climaxMetadata'] = $climaxMetadata;
                } elseif ($position < ($climaxIndex / max(1, $count - 1))) {
                    // Rising action - build toward climax
                    $targetIntensity = 0.3 + ($position * 0.5);
                    $moment['intensity'] = max($moment['intensity'] ?? 0.5, $targetIntensity);
                } else {
                    // Falling action - decrease from climax
                    $distanceFromClimax = $position - ($climaxIndex / max(1, $count - 1));
                    $targetIntensity = 0.85 - ($distanceFromClimax * 0.6);
                    $moment['intensity'] = max(0.3, min($moment['intensity'] ?? 0.5, $targetIntensity));
                }
            }
        }

        return $moments;
    }

    /**
     * Map emotion keywords to intensity values.
     * Based on Hollywood cinematography analysis:
     * - Frame 285 (Pyramids): intensity 0.2 → Extreme Wide
     * - Frame 25 (Two-shot): intensity 0.45 → Medium
     * - Frame 115 (Reaction): intensity 0.7 → MCU
     * - Frame 45 (XCU profile): intensity 0.9 → XCU
     */
    public function emotionToIntensity(string $emotion): float
    {
        $emotion = strtolower(trim($emotion));
        return self::EMOTION_INTENSITY_MAP[$emotion] ?? 0.5;
    }

    /**
     * PHASE 5: Analyze text for climax indicators.
     *
     * @param string $text Narrative text to analyze
     * @return array Climax analysis with score and triggers
     */
    protected function analyzeClimaxIndicators(string $text): array
    {
        $textLower = strtolower($text);
        $climaxScore = 0;
        $triggers = [];

        // Check for climax keywords
        foreach (self::CLIMAX_KEYWORDS as $keyword) {
            if (stripos($textLower, $keyword) !== false) {
                $climaxScore += 0.15;
                $triggers[] = $keyword;
            }
        }

        // Check for resolution keywords (reduces climax likelihood)
        foreach (self::RESOLUTION_KEYWORDS as $keyword) {
            if (stripos($textLower, $keyword) !== false) {
                $climaxScore -= 0.1;
            }
        }

        // Punctuation analysis
        $exclamationCount = substr_count($text, '!');
        $climaxScore += min($exclamationCount * 0.1, 0.3);

        // All caps words indicate emphasis
        preg_match_all('/\b[A-Z]{2,}\b/', $text, $capsMatches);
        $climaxScore += min(count($capsMatches[0]) * 0.1, 0.2);

        // Cap the score
        $climaxScore = max(0, min(1, $climaxScore));

        return [
            'score' => $climaxScore,
            'triggers' => array_slice($triggers, 0, 5),
            'isLikelyClimax' => $climaxScore >= 0.3,
        ];
    }

    /**
     * PHASE 5: Detect peaks in an intensity array.
     * A peak is a local maximum higher than neighbors by threshold.
     *
     * @param array $intensities Array of intensity values (0-1)
     * @param float $threshold Minimum difference to qualify as peak
     * @return array Peak indices and their intensities
     */
    protected function detectIntensityPeaks(array $intensities, float $threshold = 0.15): array
    {
        $peaks = [];
        $count = count($intensities);

        if ($count < 3) {
            // Not enough data for peak detection
            return $count > 0 ? [['index' => 0, 'intensity' => $intensities[0] ?? 0.5]] : [];
        }

        for ($i = 1; $i < $count - 1; $i++) {
            $current = $intensities[$i];
            $prev = $intensities[$i - 1];
            $next = $intensities[$i + 1];

            // Check if local maximum
            if ($current > $prev && $current > $next) {
                // Check if significant enough
                $diff = min($current - $prev, $current - $next);
                if ($diff >= $threshold) {
                    $peaks[] = [
                        'index' => $i,
                        'intensity' => $current,
                        'prominence' => $diff,
                    ];
                }
            }
        }

        // If no peaks found, use the maximum value position
        if (empty($peaks)) {
            $maxIndex = array_search(max($intensities), $intensities);
            $peaks[] = [
                'index' => $maxIndex,
                'intensity' => $intensities[$maxIndex],
                'prominence' => 0,
            ];
        }

        // Sort by intensity descending
        usort($peaks, fn($a, $b) => $b['intensity'] <=> $a['intensity']);

        return $peaks;
    }

    /**
     * PHASE 5: Identify the primary climax from peaks and content.
     *
     * @param array $moments Narrative moments with text and intensity
     * @return int Index of the primary climax moment
     */
    protected function identifyPrimaryClimax(array $moments): int
    {
        $count = count($moments);
        if ($count === 0) return 0;

        // Extract intensities
        $intensities = array_map(fn($m) => $m['intensity'] ?? 0.5, $moments);

        // Detect peaks
        $peaks = $this->detectIntensityPeaks($intensities);

        // Score each peak based on content analysis
        $scoredPeaks = [];
        foreach ($peaks as $peak) {
            $momentIndex = $peak['index'];
            $moment = $moments[$momentIndex] ?? [];
            $text = $moment['action'] ?? $moment['description'] ?? '';

            $contentAnalysis = $this->analyzeClimaxIndicators($text);

            // Combined score: intensity + content + position bonus
            $positionProgress = $momentIndex / max(1, $count - 1);
            $positionBonus = ($positionProgress >= 0.5 && $positionProgress <= 0.85) ? 0.1 : 0;

            $combinedScore = ($peak['intensity'] * 0.4) +
                             ($contentAnalysis['score'] * 0.4) +
                             ($peak['prominence'] * 0.1) +
                             $positionBonus;

            $scoredPeaks[] = [
                'index' => $momentIndex,
                'combinedScore' => $combinedScore,
                'intensity' => $peak['intensity'],
                'contentScore' => $contentAnalysis['score'],
            ];
        }

        // Return the highest scored peak
        usort($scoredPeaks, fn($a, $b) => $b['combinedScore'] <=> $a['combinedScore']);

        return $scoredPeaks[0]['index'] ?? intval($count * 0.7);
    }

    /**
     * PHASE 5: Detect climax from content analysis.
     * Replaces the fixed 70% rule with intelligent detection.
     *
     * @param array $moments Array of narrative moments
     * @return array Climax data with index, confidence, and metadata
     */
    public function detectClimaxFromContent(array $moments): array
    {
        $count = count($moments);
        if ($count === 0) {
            return [
                'index' => 0,
                'confidence' => 0,
                'method' => 'empty',
                'peaks' => [],
            ];
        }

        // Get primary climax index
        $climaxIndex = $this->identifyPrimaryClimax($moments);

        // Analyze content at climax point
        $climaxMoment = $moments[$climaxIndex] ?? [];
        $climaxText = $climaxMoment['action'] ?? $climaxMoment['description'] ?? '';
        $contentAnalysis = $this->analyzeClimaxIndicators($climaxText);

        // Detect all peaks for multi-climax narratives
        $intensities = array_map(fn($m) => $m['intensity'] ?? 0.5, $moments);
        $allPeaks = $this->detectIntensityPeaks($intensities, 0.1);

        // Calculate confidence
        $climaxIntensity = $moments[$climaxIndex]['intensity'] ?? 0.5;
        $confidence = min(1, ($climaxIntensity * 0.5) + ($contentAnalysis['score'] * 0.5));

        // Determine detection method
        $method = $contentAnalysis['isLikelyClimax'] ? 'content' :
                  (($allPeaks[0]['prominence'] ?? 0) > 0.2 ? 'intensity_peak' : 'position_fallback');

        Log::debug('NarrativeMomentService: Climax detected', [
            'index' => $climaxIndex,
            'total_moments' => $count,
            'position' => round($climaxIndex / max(1, $count - 1) * 100) . '%',
            'method' => $method,
            'confidence' => round($confidence, 2),
            'triggers' => $contentAnalysis['triggers'],
        ]);

        return [
            'index' => $climaxIndex,
            'confidence' => $confidence,
            'method' => $method,
            'position' => $climaxIndex / max(1, $count - 1),
            'intensity' => $climaxIntensity,
            'triggers' => $contentAnalysis['triggers'],
            'peaks' => array_slice($allPeaks, 0, 3), // Top 3 peaks
            'isMultiClimax' => count($allPeaks) > 1,
        ];
    }

    /**
     * PHASE 5: Extract and process emotional arc from moments.
     * Enhanced with smoothing and template blending.
     *
     * Hollywood Pattern: Build toward climax, then resolve
     * [0.3, 0.5, 0.8, 0.7] = arrival → recognition → chase (peak) → loss
     *
     * @param array $moments Array of narrative moments
     * @param string $template Arc template to apply (default: hollywood)
     * @param bool $smooth Whether to smooth the curve
     * @return array Processed emotional arc with raw and smoothed values
     */
    public function extractEmotionalArc(
        array $moments,
        string $template = 'hollywood',
        bool $smooth = true
    ): array {
        if (empty($moments)) {
            return [
                'values' => [],
                'smoothed' => [],
                'template' => $template,
            ];
        }

        // Get processed curve
        $curveData = $this->getProcessedIntensityCurve($moments, $template, $smooth);

        // Log arc extraction
        Log::debug('NarrativeMomentService: Extracted emotional arc', [
            'moment_count' => count($moments),
            'template' => $template,
            'smoothed' => $smooth,
            'stats' => $curveData['stats'],
        ]);

        return [
            'values' => $curveData['raw'],
            'smoothed' => $curveData['processed'],
            'template' => $template,
            'stats' => $curveData['stats'],
        ];
    }

    /**
     * Interpolate moments if target shot count differs from moment count.
     * Ensures we have exactly the right number of moments for the shot count.
     */
    public function interpolateMoments(array $moments, int $targetCount): array
    {
        $currentCount = count($moments);

        if ($currentCount === 0) {
            // PHASE 3: Generate meaningful moments from narration analysis
            // Never return useless placeholders like "continues the scene"
            // Note: At this point we may not have narration context, so use narrative arc fallback
            $arcMoments = $this->generateNarrativeArcMoments($targetCount, []);
            Log::warning('NarrativeMomentService: Using narrative arc fallback for empty moments', [
                'count' => count($arcMoments),
            ]);
            return $arcMoments;
        }

        if ($currentCount === $targetCount) {
            return $moments;
        }

        if ($currentCount > $targetCount) {
            // Need to reduce: select most important moments
            return $this->selectKeyMoments($moments, $targetCount);
        }

        // Need to expand: duplicate/interpolate moments
        return $this->expandMoments($moments, $targetCount);
    }

    /**
     * Select key moments when we have more moments than shots.
     * Keeps first, last, and highest intensity moments.
     */
    protected function selectKeyMoments(array $moments, int $targetCount): array
    {
        if ($targetCount >= count($moments)) {
            return $moments;
        }

        // Always keep first and last
        $selected = [];
        $selected[0] = $moments[0];
        $selected[$targetCount - 1] = $moments[count($moments) - 1];

        // Sort remaining by intensity (descending) to find climax moments
        $middleMoments = [];
        for ($i = 1; $i < count($moments) - 1; $i++) {
            $middleMoments[$i] = $moments[$i];
        }
        uasort($middleMoments, function ($a, $b) {
            return ($b['intensity'] ?? 0.5) <=> ($a['intensity'] ?? 0.5);
        });

        // Fill remaining slots with highest intensity moments
        $slotsToFill = $targetCount - 2;
        $filled = 0;
        foreach ($middleMoments as $index => $moment) {
            if ($filled >= $slotsToFill) {
                break;
            }
            // Find appropriate position
            $position = 1 + $filled;
            $selected[$position] = $moment;
            $filled++;
        }

        // Sort by position and re-index
        ksort($selected);
        return array_values($selected);
    }

    /**
     * Expand moments when we have fewer moments than shots.
     * Interpolates between existing moments.
     */
    protected function expandMoments(array $moments, int $targetCount): array
    {
        $currentCount = count($moments);
        $expanded = [];

        for ($i = 0; $i < $targetCount; $i++) {
            // Calculate which original moment this maps to (with interpolation)
            $progress = $i / max(1, $targetCount - 1);
            $sourceIndex = $progress * ($currentCount - 1);

            $lowerIndex = (int) floor($sourceIndex);
            $upperIndex = min($currentCount - 1, $lowerIndex + 1);
            $interpolation = $sourceIndex - $lowerIndex;

            if ($lowerIndex === $upperIndex || $interpolation < 0.3) {
                // Use lower moment
                $expanded[] = $moments[$lowerIndex];
            } elseif ($interpolation > 0.7) {
                // Use upper moment
                $expanded[] = $moments[$upperIndex];
            } else {
                // Create interpolated moment
                $lower = $moments[$lowerIndex];
                $upper = $moments[$upperIndex];

                $expanded[] = [
                    'action' => $lower['action'], // Use lower action
                    'subject' => $lower['subject'] ?? $upper['subject'] ?? 'the character',
                    'emotion' => $lower['emotion'], // Use lower emotion
                    'intensity' => $lower['intensity'] + ($interpolation * (($upper['intensity'] ?? 0.5) - ($lower['intensity'] ?? 0.5))),
                    'visualDescription' => $lower['visualDescription'] ?? $lower['action'],
                    'interpolated' => true,
                ];
            }
        }

        return $expanded;
    }

    /**
     * Format character list for AI prompt.
     */
    protected function formatCharacterList(array $characters): string
    {
        if (empty($characters)) {
            return 'Not specified';
        }

        $names = [];
        foreach ($characters as $char) {
            if (is_array($char)) {
                $names[] = $char['name'] ?? 'Unknown';
            } else {
                $names[] = (string) $char;
            }
        }

        return implode(', ', $names);
    }

    /**
     * Deduplicate actions across consecutive moments.
     * Hollywood standard: Each shot must have a UNIQUE action.
     *
     * @param array $moments Array of moment objects
     * @return array Moments with deduplicated actions
     */
    public function deduplicateActions(array $moments): array
    {
        if (count($moments) <= 1) {
            return $moments;
        }

        $usedVerbs = [];
        $progressionMarkers = [
            'begins to', 'starts to', 'continues to', 'proceeds to',
            'then', 'now', 'suddenly', 'finally', 'eventually'
        ];

        foreach ($moments as $index => &$moment) {
            $action = $moment['action'] ?? '';
            $verb = $this->extractPrimaryVerb($action);

            // Check if verb was used in previous moments
            if (in_array(strtolower($verb), $usedVerbs)) {
                // Add progression marker to make unique
                $marker = $progressionMarkers[$index % count($progressionMarkers)];
                $moment['action'] = $marker . ' ' . $action;
                $moment['deduplicated'] = true;

                Log::debug('NarrativeMomentService: Deduplicated action', [
                    'original' => $action,
                    'modified' => $moment['action'],
                    'index' => $index,
                ]);
            }

            // Track this verb (allow same verb with 2+ gap)
            $usedVerbs[] = strtolower($verb);
            if (count($usedVerbs) > 2) {
                array_shift($usedVerbs); // Keep only last 2 verbs
            }
        }

        return $moments;
    }

    /**
     * Extract the primary action verb from an action string.
     *
     * @param string $action Action description
     * @return string Primary verb or full action if no verb found
     */
    protected function extractPrimaryVerb(string $action): string
    {
        // Common action verbs to detect
        $verbPattern = '/\b(arrives?|enters?|walks?|runs?|spots?|sees?|looks?|watches?|notices?|chases?|finds?|loses?|meets?|speaks?|confronts?|faces?|flees?|escapes?|approaches?|attacks?|defends?|realizes?|discovers?|stands?|sits?|moves?|steps?|turns?|reaches?|grabs?|holds?|drops?|throws?|pushes?|pulls?|opens?|closes?|starts?|stops?|begins?|ends?|continues?)\b/i';

        if (preg_match($verbPattern, $action, $matches)) {
            return $matches[1];
        }

        // Fallback: first word
        $words = explode(' ', trim($action));
        return $words[0] ?? $action;
    }

    /**
     * Check if two actions are similar enough to be considered duplicates.
     *
     * @param string $action1 First action
     * @param string $action2 Second action
     * @return bool True if actions are too similar
     */
    public function areActionsSimilar(string $action1, string $action2): bool
    {
        $verb1 = strtolower($this->extractPrimaryVerb($action1));
        $verb2 = strtolower($this->extractPrimaryVerb($action2));

        // Same verb = similar
        if ($verb1 === $verb2) {
            return true;
        }

        // Check for verb variations (look/looks/looking)
        $stem1 = rtrim($verb1, 'seding');
        $stem2 = rtrim($verb2, 'seding');
        if ($stem1 === $stem2 && strlen($stem1) > 3) {
            return true;
        }

        // Synonym groups that should be considered similar
        $synonymGroups = [
            ['look', 'watch', 'observe', 'gaze', 'stare', 'glance'],
            ['run', 'sprint', 'dash', 'race', 'rush'],
            ['walk', 'step', 'pace', 'stride'],
            ['speak', 'say', 'tell', 'talk'],
            ['stand', 'rise', 'get up'],
        ];

        foreach ($synonymGroups as $group) {
            $in1 = in_array($stem1, $group) || in_array($verb1, $group);
            $in2 = in_array($stem2, $group) || in_array($verb2, $group);
            if ($in1 && $in2) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the recommended shot type for an emotional intensity value.
     * Based on Hollywood cinematography analysis.
     *
     * @param float $intensity Emotional intensity (0-1)
     * @param int $index Shot index in sequence
     * @param int $total Total shots in sequence
     * @return string Shot type name
     */
    public function getShotTypeForIntensity(float $intensity, int $index, int $total): string
    {
        // First shot always establishing (unless very short scene)
        if ($index === 0 && $total > 2) {
            return 'establishing';
        }

        // Last shot should be character-centric for animation
        if ($index === $total - 1) {
            return $intensity > 0.6 ? 'close-up' : 'medium';
        }

        // Intensity-based selection (from Hollywood analysis)
        if ($intensity >= 0.85) {
            return 'extreme-close-up';  // XCU for peak moments (Frame 45)
        }
        if ($intensity >= 0.7) {
            return 'close-up';  // CU for high emotion (Frame 115)
        }
        if ($intensity >= 0.55) {
            return 'medium-close';  // MCU for engagement
        }
        if ($intensity >= 0.4) {
            return 'medium';  // Standard dialogue (Frame 25)
        }
        if ($intensity >= 0.25) {
            return 'wide';  // Context shots
        }

        return 'establishing';  // Location/scale (Frame 285)
    }

    /**
     * Generate meaningful moments by analyzing narration text.
     * Uses keyword extraction and ACTION_EMOTION_MAP for valid actions.
     *
     * @param string $narration Raw narration text
     * @param int $targetCount Number of moments to generate
     * @param array $context Scene context
     * @return array Array of moment objects with unique actions
     */
    protected function generateMeaningfulMomentsFromNarration(string $narration, int $targetCount, array $context = []): array
    {
        $moments = [];
        $usedActions = [];

        // Split narration into sentences/chunks
        $chunks = preg_split('/[.!?]+/', $narration, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = array_filter(array_map('trim', $chunks));

        if (empty($chunks)) {
            return [];
        }

        // Extract actions from each chunk
        foreach ($chunks as $index => $chunk) {
            if (count($moments) >= $targetCount) {
                break;
            }

            // Try to find an action verb in this chunk
            $action = $this->extractActionFromText($chunk, $usedActions);

            if ($action) {
                $emotion = self::ACTION_EMOTION_MAP[$action] ?? 'focus';
                $progress = count($moments) / max(1, $targetCount - 1);

                $moments[] = [
                    'action' => $action,
                    'subject' => $this->extractSubjectFromChunk($chunk, $context),
                    'emotion' => $emotion,
                    'intensity' => $this->calculateIntensityFromEmotion($emotion, $progress),
                    'visualDescription' => $this->summarizeChunk($chunk),
                    'source' => 'narration_analysis',
                ];

                $usedActions[] = $action;
            }
        }

        // If we don't have enough moments, interpolate
        if (count($moments) < $targetCount && count($moments) > 0) {
            $moments = $this->interpolateMoments($moments, $targetCount);
            $moments = $this->deduplicateActions($moments);
        }

        return $moments;
    }

    /**
     * Extract an action verb from text chunk.
     *
     * @param string $text Text to analyze
     * @param array $usedActions Actions already used (to avoid duplicates)
     * @return string|null Action found or null
     */
    protected function extractActionFromText(string $text, array $usedActions = []): ?string
    {
        $text = strtolower($text);

        // Priority actions from ACTION_EMOTION_MAP
        $actionPriority = [
            // High-value actions (dramatic)
            'discovers', 'realizes', 'confronts', 'reveals', 'escapes', 'attacks',
            'defends', 'surrenders', 'sacrifices', 'transforms',
            // Movement actions
            'enters', 'arrives', 'approaches', 'retreats', 'chases', 'flees',
            'walks', 'runs', 'stops',
            // Perception actions
            'notices', 'spots', 'watches', 'observes', 'sees', 'looks',
            // Interaction actions
            'speaks', 'meets', 'greets', 'argues', 'agrees', 'refuses',
            // State actions
            'waits', 'stands', 'sits', 'hides', 'searches',
        ];

        foreach ($actionPriority as $action) {
            // Check if action appears in text and hasn't been used
            $patterns = [$action, rtrim($action, 's'), $action . 's', $action . 'ing', $action . 'ed'];
            foreach ($patterns as $pattern) {
                if (strpos($text, $pattern) !== false && !in_array($action, $usedActions)) {
                    return $action;
                }
            }
        }

        return null;
    }

    /**
     * Extract subject from text chunk.
     *
     * @param string $text Text to analyze
     * @param array $context Scene context with characters
     * @return string Subject description
     */
    protected function extractSubjectFromChunk(string $text, array $context = []): string
    {
        // Check for known characters
        $characters = $context['characters'] ?? [];
        foreach ($characters as $character) {
            $name = is_array($character) ? ($character['name'] ?? '') : (string) $character;
            if (!empty($name) && stripos($text, $name) !== false) {
                return $name;
            }
        }

        // Check for common subject words
        $subjectPatterns = [
            '/\b(he|him|his)\b/i' => 'the protagonist',
            '/\b(she|her|hers)\b/i' => 'the protagonist',
            '/\b(they|them|their)\b/i' => 'the characters',
            '/\bthe\s+(man|woman|person|figure|character)\b/i' => 'the $1',
        ];

        foreach ($subjectPatterns as $pattern => $replacement) {
            if (preg_match($pattern, $text, $matches)) {
                if (strpos($replacement, '$1') !== false && isset($matches[1])) {
                    return 'the ' . strtolower($matches[1]);
                }
                return $replacement;
            }
        }

        return 'the character';
    }

    /**
     * Summarize a text chunk for visual description.
     *
     * @param string $chunk Text chunk
     * @return string Summarized visual description
     */
    protected function summarizeChunk(string $chunk): string
    {
        // Clean up the chunk
        $cleaned = trim($chunk);

        // Truncate to reasonable length
        if (strlen($cleaned) > 100) {
            $cleaned = substr($cleaned, 0, 97) . '...';
        }

        return ucfirst($cleaned) ?: 'Scene moment';
    }

    /**
     * Extract the first meaningful action from narration text.
     * Used as last resort when no structured moments can be extracted.
     *
     * @param string $narration Full narration text
     * @return string First action found or summarized narration
     */
    protected function extractFirstActionFromNarration(string $narration): string
    {
        // Try to find an action verb
        $action = $this->extractActionFromText($narration, []);
        if ($action) {
            return $action;
        }

        // Summarize the narration
        $summary = $this->summarizeChunk($narration);
        if (strlen($summary) > 50) {
            $summary = substr($summary, 0, 47) . '...';
        }

        return $summary;
    }

    /**
     * Calculate intensity based on emotion and progress.
     *
     * @param string $emotion Emotion name
     * @param float $progress Progress through scene (0-1)
     * @return float Intensity (0-1)
     */
    protected function calculateIntensityFromEmotion(string $emotion, float $progress): float
    {
        $baseIntensity = self::EMOTION_INTENSITY_MAP[$emotion] ?? 0.5;

        // Apply slight variation based on progress (build toward middle)
        $arcModifier = sin($progress * M_PI) * 0.1;

        return min(1.0, max(0.1, $baseIntensity + $arcModifier));
    }

    /**
     * Generate moments based on standard narrative arc structure.
     * Used as last resort when text analysis fails.
     *
     * @param int $targetCount Number of moments
     * @param array $context Scene context
     * @return array Narrative arc moments
     */
    protected function generateNarrativeArcMoments(int $targetCount, array $context = []): array
    {
        // Standard narrative arc actions that tell a story
        $arcActions = [
            // Setup (first 20%)
            ['action' => 'observes', 'emotion' => 'curiosity', 'phase' => 'setup'],
            ['action' => 'approaches', 'emotion' => 'anticipation', 'phase' => 'setup'],
            // Rising action (20-50%)
            ['action' => 'discovers', 'emotion' => 'surprise', 'phase' => 'rising'],
            ['action' => 'confronts', 'emotion' => 'determination', 'phase' => 'rising'],
            ['action' => 'struggles', 'emotion' => 'tension', 'phase' => 'rising'],
            // Climax (50-70%)
            ['action' => 'faces', 'emotion' => 'tension', 'phase' => 'climax'],
            ['action' => 'overcomes', 'emotion' => 'triumph', 'phase' => 'climax'],
            // Falling action (70-90%)
            ['action' => 'reflects', 'emotion' => 'contemplation', 'phase' => 'falling'],
            // Resolution (90-100%)
            ['action' => 'resolves', 'emotion' => 'resolution', 'phase' => 'resolution'],
        ];

        $moments = [];
        $subject = $context['mainCharacter'] ?? 'the protagonist';

        // Select appropriate arc moments based on target count
        if ($targetCount <= 3) {
            // Short: setup -> climax -> resolution
            $selectedIndices = [0, 5, 8];
        } elseif ($targetCount <= 5) {
            // Medium: setup -> rising -> climax -> falling -> resolution
            $selectedIndices = [0, 2, 5, 7, 8];
        } else {
            // Long: use all arc points and interpolate
            $selectedIndices = range(0, min(count($arcActions) - 1, $targetCount - 1));
        }

        foreach ($selectedIndices as $i => $arcIndex) {
            if ($arcIndex >= count($arcActions)) {
                $arcIndex = count($arcActions) - 1;
            }

            $arc = $arcActions[$arcIndex];
            $progress = $i / max(1, count($selectedIndices) - 1);

            $moments[] = [
                'action' => $arc['action'],
                'subject' => $subject,
                'emotion' => $arc['emotion'],
                'intensity' => $this->calculateArcIntensity($arc['phase'], $progress),
                'visualDescription' => sprintf('%s %s the situation', ucfirst($subject), $arc['action']),
                'source' => 'narrative_arc',
                'arcPhase' => $arc['phase'],
            ];
        }

        // Interpolate if needed
        if (count($moments) < $targetCount) {
            $moments = $this->expandMoments($moments, $targetCount);
            $moments = $this->deduplicateActions($moments);
        }

        return $moments;
    }

    /**
     * Calculate intensity based on narrative arc phase.
     *
     * @param string $phase Arc phase
     * @param float $progress Overall progress (0-1)
     * @return float Intensity (0-1)
     */
    protected function calculateArcIntensity(string $phase, float $progress): float
    {
        // Intensity curves by phase
        $phaseIntensity = [
            'setup' => 0.3,
            'rising' => 0.5 + ($progress * 0.2),
            'climax' => 0.85,
            'falling' => 0.6,
            'resolution' => 0.4,
        ];

        return $phaseIntensity[$phase] ?? 0.5;
    }

    /**
     * PHASE 5: Smooth an intensity curve using weighted moving average.
     * Prevents jarring transitions between consecutive moments.
     *
     * @param array $intensities Raw intensity values
     * @param int $windowSize Number of neighbors to consider (odd number)
     * @return array Smoothed intensity values
     */
    protected function smoothIntensityCurve(array $intensities, int $windowSize = 3): array
    {
        $count = count($intensities);
        if ($count <= 2) {
            return $intensities;
        }

        // Ensure odd window size
        $windowSize = max(3, $windowSize | 1);
        $halfWindow = intdiv($windowSize, 2);

        $smoothed = [];

        foreach ($intensities as $i => $value) {
            $sum = 0;
            $weightSum = 0;

            for ($j = -$halfWindow; $j <= $halfWindow; $j++) {
                $idx = $i + $j;
                if ($idx >= 0 && $idx < $count) {
                    // Weight decreases with distance from center
                    $weight = 1 / (1 + abs($j));
                    $sum += $intensities[$idx] * $weight;
                    $weightSum += $weight;
                }
            }

            $smoothed[$i] = $weightSum > 0 ? $sum / $weightSum : $value;
        }

        // Preserve climax peaks - don't smooth them down
        foreach ($intensities as $i => $original) {
            if ($original >= 0.8 && $smoothed[$i] < $original - 0.1) {
                // Restore peak that was smoothed too much
                $smoothed[$i] = max($smoothed[$i], $original - 0.05);
            }
        }

        return $smoothed;
    }

    /**
     * PHASE 5: Apply exponential smoothing for more gradual transitions.
     *
     * @param array $intensities Raw intensity values
     * @param float $alpha Smoothing factor (0-1, lower = smoother)
     * @return array Exponentially smoothed values
     */
    protected function exponentialSmooth(array $intensities, float $alpha = 0.3): array
    {
        $count = count($intensities);
        if ($count <= 1) {
            return $intensities;
        }

        $smoothed = [$intensities[0]];

        for ($i = 1; $i < $count; $i++) {
            $smoothed[$i] = $alpha * $intensities[$i] + (1 - $alpha) * $smoothed[$i - 1];
        }

        return $smoothed;
    }

    /**
     * PHASE 5: Get target intensity at a given position from arc template.
     *
     * @param string $template Arc template name
     * @param float $position Position in narrative (0-1)
     * @return float Target intensity at position
     */
    protected function getArcTargetIntensity(string $template, float $position): float
    {
        $arcPoints = self::ARC_TEMPLATES[$template] ?? self::ARC_TEMPLATES['hollywood'];

        $positions = array_keys($arcPoints);
        $intensities = array_values($arcPoints);

        // Find surrounding points for interpolation
        $prevPos = 0.0;
        $prevInt = $intensities[0];
        $nextPos = 1.0;
        $nextInt = end($intensities);

        foreach ($positions as $i => $pos) {
            if ($pos <= $position) {
                $prevPos = $pos;
                $prevInt = $intensities[$i];
            }
            if ($pos >= $position) {
                $nextPos = $pos;
                $nextInt = $intensities[$i];
                break;
            }
        }

        // Linear interpolation between points
        if ($nextPos === $prevPos) {
            return $prevInt;
        }

        $t = ($position - $prevPos) / ($nextPos - $prevPos);
        return $prevInt + $t * ($nextInt - $prevInt);
    }

    /**
     * PHASE 5: Blend actual intensities with arc template.
     * Combines content-derived intensity with template shape.
     *
     * @param array $intensities Actual intensity values
     * @param string $template Arc template name
     * @param float $blendWeight How much template influences (0=none, 1=full)
     * @return array Blended intensity values
     */
    public function blendWithArcTemplate(
        array $intensities,
        string $template = 'hollywood',
        float $blendWeight = 0.5
    ): array {
        $count = count($intensities);
        if ($count === 0) {
            return [];
        }

        $blended = [];

        foreach ($intensities as $i => $actual) {
            $position = $i / max(1, $count - 1);
            $target = $this->getArcTargetIntensity($template, $position);

            // Blend actual with template
            $blended[$i] = ($actual * (1 - $blendWeight)) + ($target * $blendWeight);
        }

        return $blended;
    }

    /**
     * PHASE 5: Get smoothed and shaped intensity curve.
     * Main entry point for intensity curve processing.
     *
     * @param array $moments Narrative moments with intensity
     * @param string $template Arc template to apply
     * @param bool $smooth Whether to apply smoothing
     * @return array Processed intensity values with metadata
     */
    public function getProcessedIntensityCurve(
        array $moments,
        string $template = 'hollywood',
        bool $smooth = true
    ): array {
        // Extract raw intensities
        $raw = array_map(fn($m) => $m['intensity'] ?? 0.5, $moments);

        // Blend with template
        $blended = $this->blendWithArcTemplate($raw, $template, 0.4);

        // Apply smoothing
        $processed = $smooth ? $this->smoothIntensityCurve($blended) : $blended;

        // Calculate curve statistics
        $stats = [
            'min' => min($processed) ?: 0,
            'max' => max($processed) ?: 1,
            'average' => count($processed) > 0 ? array_sum($processed) / count($processed) : 0.5,
            'range' => (max($processed) ?: 1) - (min($processed) ?: 0),
            'template' => $template,
            'smoothed' => $smooth,
        ];

        return [
            'raw' => $raw,
            'processed' => $processed,
            'stats' => $stats,
        ];
    }
}
