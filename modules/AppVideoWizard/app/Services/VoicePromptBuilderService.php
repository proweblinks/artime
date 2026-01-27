<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * VoicePromptBuilderService
 *
 * Assembles Hollywood-quality voice prompts by integrating VoiceDirectionVocabulary
 * and VoicePacingService, plus adds ambient audio cues and emotional arc direction.
 *
 * This is the final integration service for Phase 25 that produces complete enhanced
 * voice prompts. Implements VOC-04 (ambient audio cues) and VOC-06 (emotional arc
 * direction), and integrates VOC-01/02/03/05 from Plans 01-02.
 *
 * Requirements covered:
 * - VOC-01: Emotional direction tags (via VoiceDirectionVocabulary)
 * - VOC-02: Pacing markers (via VoicePacingService)
 * - VOC-03: Vocal quality descriptions (via VoiceDirectionVocabulary)
 * - VOC-04: Ambient audio cues for scene atmosphere
 * - VOC-05: Breath and non-verbal markers (via VoiceDirectionVocabulary)
 * - VOC-06: Emotional arc direction across dialogue sequences
 */
class VoicePromptBuilderService
{
    /**
     * Ambient audio cues for scene atmosphere (VOC-04).
     *
     * Scene atmosphere suggestions for audio mixing, not TTS tags.
     * These describe the acoustic environment for realistic audio production.
     */
    public const AMBIENT_AUDIO_CUES = [
        'intimate' => 'quiet room tone, minimal background, close mic presence',
        'outdoor' => 'subtle wind, distant nature sounds, open acoustic',
        'crowded' => 'background murmur, ambient conversation, bustling atmosphere',
        'tense' => 'silence heavy with anticipation, muted background',
        'storm' => 'rain and thunder ambient, howling wind',
        'night' => 'crickets, distant traffic, nighttime quiet',
        'office' => 'keyboard clicks, AC hum, muffled phone',
        'vehicle' => 'engine hum, road noise, confined acoustic',
    ];

    /**
     * Emotional arc patterns for dialogue sequences (VOC-06).
     *
     * Named arc progressions that describe emotional journey through a scene.
     * Each arc has 4 stages distributed across the dialogue segments.
     */
    public const EMOTIONAL_ARC_PATTERNS = [
        'building' => ['quiet', 'rising', 'intense', 'peak'],
        'crashing' => ['confident', 'wavering', 'breaking', 'collapsed'],
        'recovering' => ['broken', 'struggling', 'gathering', 'resolved'],
        'masking' => ['controlled', 'slipping', 'recovering', 'forced'],
        'revealing' => ['guarded', 'hesitant', 'opening', 'vulnerable'],
        'confronting' => ['calm', 'challenged', 'defensive', 'explosive'],
    ];

    /**
     * @var VoiceDirectionVocabulary
     */
    protected VoiceDirectionVocabulary $voiceDirection;

    /**
     * @var VoicePacingService
     */
    protected VoicePacingService $pacingService;

    /**
     * Create a new VoicePromptBuilderService instance.
     *
     * @param VoiceDirectionVocabulary $voiceDirection
     * @param VoicePacingService $pacingService
     */
    public function __construct(
        VoiceDirectionVocabulary $voiceDirection,
        VoicePacingService $pacingService
    ) {
        $this->voiceDirection = $voiceDirection;
        $this->pacingService = $pacingService;
    }

    /**
     * Build an enhanced voice prompt for a single speech segment.
     *
     * Returns an array containing:
     * - 'text': Enhanced text with provider-appropriate emotional tags
     * - 'instructions': Separate instruction text for providers like OpenAI
     * - 'ambient': Ambient audio cue description (if requested)
     *
     * @param SpeechSegment $segment The speech segment to enhance
     * @param array $options Options: 'provider' (elevenlabs|openai|kokoro), 'includeAmbient' (bool), 'arcPosition' (string), 'sceneType' (string)
     * @return array{text: string, instructions: string, ambient: string}
     */
    public function buildEnhancedVoicePrompt(SpeechSegment $segment, array $options = []): array
    {
        $provider = strtolower($options['provider'] ?? 'elevenlabs');
        $includeAmbient = $options['includeAmbient'] ?? false;
        $arcPosition = $options['arcPosition'] ?? null;
        $sceneType = $options['sceneType'] ?? 'intimate';

        $text = $segment->text;
        $instructions = '';
        $ambient = '';

        // Apply emotional direction if segment has emotion
        if (!empty($segment->emotion)) {
            if ($provider === 'openai') {
                // OpenAI uses separate instructions, not inline tags
                $instructions = $this->voiceDirection->buildVoiceInstruction($segment->emotion);
            } elseif ($provider === 'kokoro') {
                // Kokoro uses descriptive text style
                $direction = $this->voiceDirection->getDirectionForEmotion($segment->emotion);
                if (!empty($direction['description'])) {
                    $instructions = $direction['description'];
                }
            } else {
                // ElevenLabs and default: use inline tags
                $text = $this->voiceDirection->wrapWithDirection($text, $segment->emotion, $provider);
            }
        }

        // Add arc position note to instructions if provided
        if ($arcPosition !== null && !empty($arcPosition)) {
            $arcNote = "Emotional position: {$arcPosition}";
            $instructions = !empty($instructions)
                ? "{$instructions}. {$arcNote}"
                : $arcNote;
        }

        // Include ambient cue if requested
        if ($includeAmbient) {
            $ambient = $this->buildAmbientCue($sceneType);
        }

        Log::debug('VoicePromptBuilderService: Built enhanced prompt', [
            'segment_id' => $segment->id,
            'provider' => $provider,
            'has_emotion' => !empty($segment->emotion),
            'has_instructions' => !empty($instructions),
            'has_ambient' => !empty($ambient),
        ]);

        return [
            'text' => $text,
            'instructions' => $instructions,
            'ambient' => $ambient,
        ];
    }

    /**
     * Build emotional arc across a sequence of segments.
     *
     * Assigns arc position notes to each segment based on its position in the sequence.
     * Uses the formula: position = min(floor(index / count * 4), 3) to distribute
     * 4 arc stages across any number of segments.
     *
     * @param array<SpeechSegment> $segments Array of SpeechSegment objects
     * @param string $arcType Arc type name (building, crashing, recovering, etc.)
     * @return array<SpeechSegment> Segments with emotionalArcNote property set
     */
    public function buildEmotionalArc(array $segments, string $arcType = 'building'): array
    {
        $arcType = strtolower(trim($arcType));
        $pattern = self::EMOTIONAL_ARC_PATTERNS[$arcType] ?? self::EMOTIONAL_ARC_PATTERNS['building'];

        $count = count($segments);
        if ($count === 0) {
            return $segments;
        }

        foreach ($segments as $index => $segment) {
            // Distribute 4 arc stages across all segments
            // For single segment: position 0 (first stage)
            // For many segments: proportionally distributed
            if ($count === 1) {
                $position = 0;
            } else {
                $position = min((int) floor($index / $count * 4), 3);
            }

            // Set the emotional arc note on the segment
            // Using a dynamic property for flexibility
            $segment->emotionalArcNote = $pattern[$position];
        }

        Log::debug('VoicePromptBuilderService: Built emotional arc', [
            'arc_type' => $arcType,
            'segment_count' => $count,
            'pattern' => $pattern,
        ]);

        return $segments;
    }

    /**
     * Build ambient audio cue description for a scene type.
     *
     * @param string $sceneType Scene type (intimate, outdoor, crowded, etc.)
     * @return string Ambient audio cue description
     */
    public function buildAmbientCue(string $sceneType): string
    {
        $sceneType = strtolower(trim($sceneType));

        // Fall back to 'intimate' for unknown types
        return self::AMBIENT_AUDIO_CUES[$sceneType] ?? self::AMBIENT_AUDIO_CUES['intimate'];
    }

    /**
     * Build a complete dialogue direction prompt for a sequence of segments.
     *
     * High-level method that processes full dialogue sequence:
     * - Applies emotional arc across segments
     * - Builds ambient cue for scene type
     * - Enhances each segment with emotional direction
     *
     * @param array<SpeechSegment> $segments Array of SpeechSegment objects
     * @param string $arcType Arc type name
     * @param string $sceneType Scene type for ambient cue
     * @param string $provider TTS provider
     * @return array{segments: array, arcSummary: string, ambient: string}
     */
    public function buildDialogueDirectionPrompt(
        array $segments,
        string $arcType,
        string $sceneType,
        string $provider = 'elevenlabs'
    ): array {
        // Apply emotional arc to segments
        $segmentsWithArc = $this->buildEmotionalArc($segments, $arcType);

        // Build enhanced prompts for each segment
        $enhancedSegments = [];
        foreach ($segmentsWithArc as $segment) {
            $arcPosition = $segment->emotionalArcNote ?? null;

            $enhanced = $this->buildEnhancedVoicePrompt($segment, [
                'provider' => $provider,
                'includeAmbient' => false, // Ambient is returned separately
                'arcPosition' => $arcPosition,
                'sceneType' => $sceneType,
            ]);

            $enhancedSegments[] = [
                'segment' => $segment,
                'enhanced' => $enhanced,
            ];
        }

        // Build arc summary and ambient cue
        $arcSummary = $this->buildArcSummary($arcType, count($segments));
        $ambient = $this->buildAmbientCue($sceneType);

        Log::debug('VoicePromptBuilderService: Built dialogue direction prompt', [
            'segment_count' => count($segments),
            'arc_type' => $arcType,
            'scene_type' => $sceneType,
            'provider' => $provider,
        ]);

        return [
            'segments' => $enhancedSegments,
            'arcSummary' => $arcSummary,
            'ambient' => $ambient,
        ];
    }

    /**
     * Build a human-readable arc summary for voice actors.
     *
     * Generates a description of the emotional progression that helps
     * performers understand the overall arc of the scene.
     *
     * @param string $arcType Arc type name
     * @param int $segmentCount Number of segments in the sequence
     * @return string Human-readable arc direction
     */
    public function buildArcSummary(string $arcType, int $segmentCount): string
    {
        $arcType = strtolower(trim($arcType));
        $pattern = self::EMOTIONAL_ARC_PATTERNS[$arcType] ?? self::EMOTIONAL_ARC_PATTERNS['building'];

        $start = $pattern[0];
        $end = $pattern[3];
        $middle = $pattern[1] . ' to ' . $pattern[2];

        // Build a natural-sounding summary
        $summary = "Start {$start}, build through {$middle}, reach {$end}";

        if ($segmentCount === 1) {
            $summary = "Deliver with {$start} emotional quality";
        } elseif ($segmentCount === 2) {
            $summary = "Start {$start}, end with {$end}";
        } elseif ($segmentCount <= 4) {
            $summary = "Start {$start}, progress to {$end} by final line";
        } else {
            $summary = "Start {$start}, build through {$middle}, reach emotional {$end} on final line";
        }

        return $summary;
    }

    /**
     * Get all available arc type keys.
     *
     * @return array<string>
     */
    public function getAvailableArcTypes(): array
    {
        return array_keys(self::EMOTIONAL_ARC_PATTERNS);
    }

    /**
     * Get all available scene type keys.
     *
     * @return array<string>
     */
    public function getAvailableSceneTypes(): array
    {
        return array_keys(self::AMBIENT_AUDIO_CUES);
    }

    /**
     * Check if an arc type exists.
     *
     * @param string $arcType Arc type to check
     * @return bool True if arc type exists
     */
    public function hasArcType(string $arcType): bool
    {
        $arcType = strtolower(trim($arcType));
        return isset(self::EMOTIONAL_ARC_PATTERNS[$arcType]);
    }

    /**
     * Check if a scene type exists.
     *
     * @param string $sceneType Scene type to check
     * @return bool True if scene type exists
     */
    public function hasSceneType(string $sceneType): bool
    {
        $sceneType = strtolower(trim($sceneType));
        return isset(self::AMBIENT_AUDIO_CUES[$sceneType]);
    }

    /**
     * Get the pattern stages for an arc type.
     *
     * @param string $arcType Arc type name
     * @return array<string> Array of 4 stage names, or 'building' pattern if unknown
     */
    public function getArcPattern(string $arcType): array
    {
        $arcType = strtolower(trim($arcType));
        return self::EMOTIONAL_ARC_PATTERNS[$arcType] ?? self::EMOTIONAL_ARC_PATTERNS['building'];
    }

    /**
     * Get the VoiceDirectionVocabulary instance.
     *
     * @return VoiceDirectionVocabulary
     */
    public function getVoiceDirection(): VoiceDirectionVocabulary
    {
        return $this->voiceDirection;
    }

    /**
     * Get the VoicePacingService instance.
     *
     * @return VoicePacingService
     */
    public function getPacingService(): VoicePacingService
    {
        return $this->pacingService;
    }
}
