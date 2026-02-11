<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * SpeechSegmentParser - Parses raw text into structured speech segments.
 *
 * Supports multiple input formats:
 * - [NARRATOR] text              → Narrator segment
 * - [INTERNAL: CHARACTER] text   → Internal thought segment
 * - [MONOLOGUE: CHARACTER] text  → Monologue segment
 * - [DIALOGUE: CHARACTER] text   → Explicit dialogue segment
 * - [CHARACTER] text             → Shorthand dialogue (e.g. [ELENA] Hello.)
 * - CHARACTER: text              → Dialogue segment
 * - "Quoted text"                → Dialogue (attributed to last speaker)
 * - Plain text                   → Defaults to narrator
 *
 * This enables Hollywood-style mixed narration where a single scene can contain:
 * narrator setting context, characters talking, internal thoughts, all flowing naturally.
 */
class SpeechSegmentParser
{
    /**
     * Character Bible for speaker validation and characterId lookup.
     */
    protected array $characterBible = [];

    /**
     * Default speaker for unattributed dialogue.
     */
    protected string $defaultSpeaker = 'NARRATOR';

    /**
     * Parse raw text into an array of SpeechSegment objects.
     *
     * @param string $text The raw text to parse
     * @param array $characterBible Optional Character Bible for speaker validation
     * @return SpeechSegment[]
     */
    public function parse(string $text, array $characterBible = []): array
    {
        $this->characterBible = $characterBible;
        $segments = [];
        $lines = preg_split('/\n+/', trim($text));

        $currentSegment = null;
        $order = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Skip parenthetical directions like (sighing) or (whispering)
            if (preg_match('/^\([^)]+\)$/', $line)) {
                // But capture emotion if we have a current segment
                if ($currentSegment && preg_match('/^\((\w+)\)$/', $line, $emotionMatch)) {
                    $currentSegment->emotion = strtolower($emotionMatch[1]);
                }
                continue;
            }

            // Check for explicit narrator tag: [NARRATOR] text
            if (preg_match('/^\[NARRATOR\]\s*(.*)$/i', $line, $matches)) {
                $currentSegment = $this->saveAndCreateSegment($segments, $currentSegment, $order++);
                $currentSegment = SpeechSegment::narrator($matches[1]);
                continue;
            }

            // Check for internal thought tag: [INTERNAL: CHARACTER] text or [INTERNAL:CHARACTER] text
            if (preg_match('/^\[INTERNAL:\s*([^\]]+)\]\s*(.*)$/i', $line, $matches)) {
                $currentSegment = $this->saveAndCreateSegment($segments, $currentSegment, $order++);
                $speaker = trim($matches[1]);
                $currentSegment = SpeechSegment::internal($speaker, $matches[2]);
                $this->enrichWithCharacterBible($currentSegment, $speaker);
                continue;
            }

            // Check for monologue tag: [MONOLOGUE: CHARACTER] text
            if (preg_match('/^\[MONOLOGUE:\s*([^\]]+)\]\s*(.*)$/i', $line, $matches)) {
                $currentSegment = $this->saveAndCreateSegment($segments, $currentSegment, $order++);
                $speaker = trim($matches[1]);
                $currentSegment = SpeechSegment::monologue($speaker, $matches[2]);
                $this->enrichWithCharacterBible($currentSegment, $speaker);
                continue;
            }

            // Check for dialogue tag: [DIALOGUE: CHARACTER] text (explicit)
            if (preg_match('/^\[DIALOGUE:\s*([^\]]+)\]\s*(.*)$/i', $line, $matches)) {
                $currentSegment = $this->saveAndCreateSegment($segments, $currentSegment, $order++);
                $speaker = trim($matches[1]);
                $currentSegment = SpeechSegment::dialogue($speaker, $matches[2]);
                $this->enrichWithCharacterBible($currentSegment, $speaker);
                continue;
            }

            // Check for shorthand character bracket format: [CHARACTER] text
            // Handles AI output like [ELENA], [VICTOR KANE] instead of ELENA: or [DIALOGUE: ELENA]
            if (preg_match('/^\[([A-Z][A-Za-z0-9\s\-\'\.#]+)\]\s*(.*)$/u', $line, $matches)) {
                $currentSegment = $this->saveAndCreateSegment($segments, $currentSegment, $order++);
                $speaker = trim($matches[1]);

                // If it says NARRATOR in brackets, treat as narrator (already caught above, but safety check)
                if (strtoupper($speaker) === 'NARRATOR') {
                    $currentSegment = SpeechSegment::narrator($matches[2]);
                } else {
                    $currentSegment = SpeechSegment::dialogue($speaker, $matches[2]);
                    $this->enrichWithCharacterBible($currentSegment, $speaker);
                }
                continue;
            }

            // Check for character dialogue format: CHARACTER: text
            // Matches: "KAI:", "THUG #1:", "Dr. Smith:", "Mary Jane:"
            if (preg_match('/^([A-Z][A-Za-z0-9\s\-\'\.#]+):\s*(.+)$/u', $line, $matches)) {
                $currentSegment = $this->saveAndCreateSegment($segments, $currentSegment, $order++);
                $speaker = trim($matches[1]);

                // Check if it's actually NARRATOR
                if (strtoupper($speaker) === 'NARRATOR') {
                    $currentSegment = SpeechSegment::narrator($matches[2]);
                } else {
                    $currentSegment = SpeechSegment::dialogue($speaker, $matches[2]);
                    $this->enrichWithCharacterBible($currentSegment, $speaker);
                }
                continue;
            }

            // Check for quoted dialogue (attribute to last speaker or default)
            if (preg_match('/^"([^"]+)"$/', $line, $matches)) {
                // If we have a current segment with a speaker, treat as continuation
                if ($currentSegment && $currentSegment->hasSpeaker()) {
                    $currentSegment->text .= ' ' . $matches[1];
                } else {
                    // Create new dialogue segment
                    $currentSegment = $this->saveAndCreateSegment($segments, $currentSegment, $order++);
                    $currentSegment = SpeechSegment::dialogue($this->defaultSpeaker, $matches[1]);
                }
                continue;
            }

            // Plain text - continuation or new narrator segment
            if ($currentSegment) {
                // Continue current segment
                $currentSegment->text .= ' ' . $line;
            } else {
                // Start new narrator segment
                $currentSegment = SpeechSegment::narrator($line);
            }
        }

        // Save final segment
        if ($currentSegment && !empty(trim($currentSegment->text))) {
            $currentSegment->order = $order;
            $segments[] = $currentSegment;
        }

        return $segments;
    }

    /**
     * Save current segment and prepare for a new one.
     */
    protected function saveAndCreateSegment(array &$segments, ?SpeechSegment $current, int $order): ?SpeechSegment
    {
        if ($current && !empty(trim($current->text))) {
            $current->order = $order;
            $current->text = trim($current->text);
            $segments[] = $current;
        }
        return null;
    }

    /**
     * Enrich segment with Character Bible data if available.
     */
    protected function enrichWithCharacterBible(SpeechSegment $segment, string $speaker): void
    {
        if (empty($this->characterBible['characters'])) {
            return;
        }

        $speakerUpper = strtoupper($speaker);

        foreach ($this->characterBible['characters'] as $character) {
            $charName = strtoupper($character['name'] ?? '');

            // Match by name (exact or partial)
            if ($charName === $speakerUpper || str_contains($charName, $speakerUpper) || str_contains($speakerUpper, $charName)) {
                $segment->characterId = $character['id'] ?? null;
                $segment->voiceId = $character['voiceId'] ?? null;
                break;
            }
        }
    }

    /**
     * Convert an array of segments back to displayable/editable text.
     *
     * @param SpeechSegment[] $segments
     * @return string
     */
    public function toDisplayText(array $segments): string
    {
        $lines = [];

        foreach ($segments as $segment) {
            if ($segment instanceof SpeechSegment) {
                $lines[] = $this->segmentToText($segment);
            } elseif (is_array($segment)) {
                $lines[] = $this->segmentToText(SpeechSegment::fromArray($segment));
            }
        }

        return implode("\n\n", $lines);
    }

    /**
     * Convert a single segment to text format.
     */
    protected function segmentToText(SpeechSegment $segment): string
    {
        return match ($segment->type) {
            SpeechSegment::TYPE_NARRATOR => "[NARRATOR] {$segment->text}",
            SpeechSegment::TYPE_INTERNAL => "[INTERNAL: {$segment->speaker}] {$segment->text}",
            SpeechSegment::TYPE_MONOLOGUE => "[MONOLOGUE: {$segment->speaker}] {$segment->text}",
            SpeechSegment::TYPE_DIALOGUE => "{$segment->speaker}: {$segment->text}",
            default => $segment->text,
        };
    }

    /**
     * Convert segments to array format for storage.
     *
     * @param SpeechSegment[] $segments
     * @return array
     */
    public function toArray(array $segments): array
    {
        return array_map(function ($segment) {
            if ($segment instanceof SpeechSegment) {
                return $segment->toArray();
            }
            return $segment;
        }, $segments);
    }

    /**
     * Validate segments against Character Bible.
     * Returns array of warnings for unknown speakers.
     *
     * @param SpeechSegment[] $segments
     * @param array $characterBible
     * @return array ['valid' => bool, 'warnings' => string[]]
     */
    public function validateSpeakers(array $segments, array $characterBible): array
    {
        $warnings = [];
        $knownCharacters = [];

        // Build list of known character names
        foreach ($characterBible['characters'] ?? [] as $char) {
            $knownCharacters[] = strtoupper($char['name'] ?? '');
        }

        // Check each segment's speaker
        foreach ($segments as $segment) {
            $seg = $segment instanceof SpeechSegment ? $segment : SpeechSegment::fromArray($segment);

            if ($seg->hasSpeaker()) {
                $speakerUpper = strtoupper($seg->speaker);

                // Check if speaker exists in Character Bible
                $found = false;
                foreach ($knownCharacters as $charName) {
                    if ($charName === $speakerUpper || str_contains($charName, $speakerUpper)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $warnings[] = "Speaker '{$seg->speaker}' not found in Character Bible";
                }
            }
        }

        return [
            'valid' => empty($warnings),
            'warnings' => array_unique($warnings),
        ];
    }

    /**
     * Auto-detect the dominant speech type from plain text.
     * Used when AI doesn't provide explicit segment markers.
     */
    public function detectSpeechType(string $text): string
    {
        // Check for dialogue indicators
        $hasDialogueFormat = preg_match('/^[A-Z][A-Za-z\s\-\']+:\s*.+$/m', $text);
        $hasQuotes = preg_match('/"[^"]+"/', $text);

        if ($hasDialogueFormat) {
            return SpeechSegment::TYPE_DIALOGUE;
        }

        // Check for internal thought indicators
        $hasInternalMarkers = preg_match('/\b(thought|thinking|wondered|felt|realized)\b/i', $text);
        if ($hasInternalMarkers && preg_match('/\b(I|he|she)\s+(thought|wondered|realized)/i', $text)) {
            return SpeechSegment::TYPE_INTERNAL;
        }

        // Default to narrator
        return SpeechSegment::TYPE_NARRATOR;
    }

    /**
     * Merge consecutive segments of the same type and speaker.
     *
     * @param SpeechSegment[] $segments
     * @return SpeechSegment[]
     */
    public function mergeConsecutive(array $segments): array
    {
        if (count($segments) <= 1) {
            return $segments;
        }

        $merged = [];
        $current = null;

        foreach ($segments as $segment) {
            $seg = $segment instanceof SpeechSegment ? $segment : SpeechSegment::fromArray($segment);

            if ($current === null) {
                $current = clone $seg;
                continue;
            }

            // Check if can merge (same type and speaker)
            if ($current->type === $seg->type && $current->speaker === $seg->speaker) {
                $current->text .= ' ' . $seg->text;
            } else {
                $merged[] = $current;
                $current = clone $seg;
            }
        }

        if ($current) {
            $merged[] = $current;
        }

        // Re-number orders
        foreach ($merged as $i => $segment) {
            $segment->order = $i;
        }

        return $merged;
    }

    /**
     * Split a long segment into multiple segments at sentence boundaries.
     */
    public function splitAtSentences(SpeechSegment $segment, int $maxWords = 50): array
    {
        $words = str_word_count($segment->text);

        if ($words <= $maxWords) {
            return [$segment];
        }

        // Split at sentence boundaries
        $sentences = preg_split('/(?<=[.!?])\s+/', $segment->text, -1, PREG_SPLIT_NO_EMPTY);
        $segments = [];
        $currentText = '';
        $order = $segment->order;

        foreach ($sentences as $sentence) {
            $potentialText = trim($currentText . ' ' . $sentence);

            if (str_word_count($potentialText) > $maxWords && !empty($currentText)) {
                // Save current and start new
                $newSegment = clone $segment;
                $newSegment->id = 'seg-' . Str::random(8);
                $newSegment->text = trim($currentText);
                $newSegment->order = $order++;
                $segments[] = $newSegment;
                $currentText = $sentence;
            } else {
                $currentText = $potentialText;
            }
        }

        // Save remaining
        if (!empty(trim($currentText))) {
            $newSegment = clone $segment;
            $newSegment->id = 'seg-' . Str::random(8);
            $newSegment->text = trim($currentText);
            $newSegment->order = $order;
            $segments[] = $newSegment;
        }

        return $segments;
    }

    /**
     * Calculate total estimated duration for all segments.
     *
     * @param SpeechSegment[] $segments
     * @param int $wordsPerMinute
     * @return float Total duration in seconds
     */
    public function calculateTotalDuration(array $segments, int $wordsPerMinute = 150): float
    {
        $total = 0;

        foreach ($segments as $segment) {
            $seg = $segment instanceof SpeechSegment ? $segment : SpeechSegment::fromArray($segment);
            $total += $seg->estimateDuration($wordsPerMinute);
        }

        return round($total, 2);
    }

    /**
     * Get summary statistics for segments.
     *
     * @param SpeechSegment[] $segments
     * @return array
     */
    public function getStatistics(array $segments): array
    {
        $stats = [
            'total' => count($segments),
            'byType' => [
                SpeechSegment::TYPE_NARRATOR => 0,
                SpeechSegment::TYPE_DIALOGUE => 0,
                SpeechSegment::TYPE_INTERNAL => 0,
                SpeechSegment::TYPE_MONOLOGUE => 0,
            ],
            'speakers' => [],
            'needsLipSync' => 0,
            'voiceoverOnly' => 0,
            'estimatedDuration' => 0,
        ];

        foreach ($segments as $segment) {
            $seg = $segment instanceof SpeechSegment ? $segment : SpeechSegment::fromArray($segment);

            $stats['byType'][$seg->type] = ($stats['byType'][$seg->type] ?? 0) + 1;

            if ($seg->hasSpeaker() && !in_array($seg->speaker, $stats['speakers'])) {
                $stats['speakers'][] = $seg->speaker;
            }

            if ($seg->needsLipSync) {
                $stats['needsLipSync']++;
            } else {
                $stats['voiceoverOnly']++;
            }

            $stats['estimatedDuration'] += $seg->estimateDuration();
        }

        $stats['estimatedDuration'] = round($stats['estimatedDuration'], 2);

        return $stats;
    }

    /**
     * Create segments from legacy single-text voiceover format.
     * Used for backwards compatibility migration.
     */
    public function migrateFromLegacy(array $scene): array
    {
        $text = $scene['voiceover']['text'] ?? $scene['narration'] ?? '';
        $speechType = $scene['voiceover']['speechType'] ?? $scene['speechType'] ?? 'narrator';
        $speakingCharacter = $scene['voiceover']['speakingCharacter'] ?? null;

        if (empty($text)) {
            return [];
        }

        // If it's already in segment format, try to parse it
        if (preg_match('/\[(NARRATOR|INTERNAL|MONOLOGUE|DIALOGUE)/', $text)) {
            return $this->parse($text, $scene['characterBible'] ?? []);
        }

        // Check if it looks like dialogue (has CHARACTER: format)
        if (preg_match('/^[A-Z][A-Za-z\s\-\']+:\s*.+$/m', $text)) {
            return $this->parse($text, $scene['characterBible'] ?? []);
        }

        // Create single segment based on legacy speechType
        $segment = match ($speechType) {
            'dialogue' => SpeechSegment::dialogue($speakingCharacter ?? 'CHARACTER', $text),
            'internal' => SpeechSegment::internal($speakingCharacter ?? 'CHARACTER', $text),
            'monologue' => SpeechSegment::monologue($speakingCharacter ?? 'CHARACTER', $text),
            default => SpeechSegment::narrator($text),
        };

        return [$segment];
    }

    /**
     * Comprehensive validation for an array of segments.
     *
     * Checks:
     * - Individual segment validity
     * - Total segment count limits
     * - Speaker validation against Character Bible
     * - Duplicate IDs
     * - Order consistency
     *
     * @param SpeechSegment[]|array[] $segments Segments to validate
     * @param array $characterBible Optional Character Bible for speaker validation
     * @return array ['valid' => bool, 'errors' => string[], 'warnings' => string[]]
     */
    public function validateSegments(array $segments, array $characterBible = []): array
    {
        $errors = [];
        $warnings = [];
        $seenIds = [];

        // Check segment count limit
        if (count($segments) > SpeechSegment::MAX_SEGMENTS_PER_SCENE) {
            $errors[] = sprintf(
                'Too many segments (%d). Maximum is %d per scene for performance.',
                count($segments),
                SpeechSegment::MAX_SEGMENTS_PER_SCENE
            );
        }

        // Validate each segment
        foreach ($segments as $index => $segment) {
            $seg = $segment instanceof SpeechSegment ? $segment : SpeechSegment::fromArray($segment);

            // Check individual segment validity
            $segmentErrors = $seg->validate();
            foreach ($segmentErrors as $error) {
                $errors[] = "Segment {$index}: {$error}";
            }

            // Check for duplicate IDs
            if (in_array($seg->id, $seenIds, true)) {
                $errors[] = "Segment {$index}: Duplicate segment ID '{$seg->id}'";
            }
            $seenIds[] = $seg->id;

            // Check order consistency
            if ($seg->order !== $index) {
                $warnings[] = "Segment {$index}: Order mismatch (expected {$index}, got {$seg->order})";
            }
        }

        // Validate speakers against Character Bible
        if (!empty($characterBible)) {
            $speakerValidation = $this->validateSpeakers($segments, $characterBible);
            foreach ($speakerValidation['warnings'] as $warning) {
                $warnings[] = $warning;
            }
        }

        // Check for empty segments array
        if (empty($segments)) {
            $warnings[] = 'No segments defined. Scene will have no voiceover.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Safely parse text with error handling.
     *
     * @param string $text The raw text to parse
     * @param array $characterBible Optional Character Bible
     * @return array ['success' => bool, 'segments' => SpeechSegment[], 'error' => string|null]
     */
    public function safeParse(string $text, array $characterBible = []): array
    {
        try {
            // Check for empty input
            if (empty(trim($text))) {
                return [
                    'success' => true,
                    'segments' => [],
                    'error' => null,
                ];
            }

            // Check for excessively long input
            if (strlen($text) > 50000) {
                return [
                    'success' => false,
                    'segments' => [],
                    'error' => 'Input text is too long (max 50,000 characters). Please split into multiple scenes.',
                ];
            }

            $segments = $this->parse($text, $characterBible);

            // Validate parsed segments
            $validation = $this->validateSegments($segments, $characterBible);

            if (!$validation['valid']) {
                Log::warning('SpeechSegmentParser: Validation errors after parsing', [
                    'errors' => $validation['errors'],
                ]);
            }

            return [
                'success' => true,
                'segments' => $segments,
                'error' => null,
                'validation' => $validation,
            ];

        } catch (\Throwable $e) {
            Log::error('SpeechSegmentParser: Parse failed', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);

            return [
                'success' => false,
                'segments' => [],
                'error' => 'Failed to parse speech segments: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize and clean segment data for storage.
     *
     * - Trims whitespace
     * - Ensures IDs are set
     * - Recalculates needsLipSync flags
     * - Fixes order values
     *
     * @param array $segments Segments to normalize
     * @return SpeechSegment[]
     */
    public function normalizeSegments(array $segments): array
    {
        $normalized = [];

        foreach ($segments as $index => $segment) {
            $seg = $segment instanceof SpeechSegment ? $segment : SpeechSegment::fromArray($segment);

            // Ensure ID
            if (empty($seg->id)) {
                $seg->id = 'seg-' . Str::random(8);
            }

            // Trim text
            $seg->text = trim($seg->text);

            // Trim speaker name
            if ($seg->speaker) {
                $seg->speaker = trim($seg->speaker);
            }

            // Fix order
            $seg->order = $index;

            // Recalculate lip-sync flag
            $seg->refreshLipSyncFlag();

            // Skip empty segments
            if (!empty($seg->text)) {
                $normalized[] = $seg;
            }
        }

        return $normalized;
    }
}
