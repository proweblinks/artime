# Phase 16: Consistency Layer - Research

**Researched:** 2026-01-25
**Domain:** Voice distribution algorithms, voice continuity validation
**Confidence:** HIGH

## Summary

This research analyzes the current narrator and internal thought distribution implementations to identify the asymmetry flagged in the TTS/Lip-Sync audit. The narrator overlay uses a word-split algorithm that evenly distributes text across all shots, while the internal thought overlay uses a segment-split approach that distributes whole segments across shots. This creates inconsistent behavior.

The codebase already has a well-established non-blocking validation pattern from M8 Phase 14 (`validateAndFixTransitions`) that logs warnings without halting generation. Voice continuity validation should follow this exact pattern.

**Primary recommendation:** Refactor `markInternalThoughtAsVoiceover()` to use the same word-split algorithm as `overlayNarratorSegments()`, then add a new `validateVoiceContinuity()` method following the M8 non-blocking validation pattern.

## Current Implementation Analysis

### Narrator Distribution (Word-Split) - Lines 23899-24018

Location: `VideoWizard.php` method `overlayNarratorSegments()`

```php
// Current narrator word-split algorithm (lines 23921-23932)
$allNarratorText = implode(' ', array_column($narratorSegments, 'text'));
$words = preg_split('/\s+/', trim($allNarratorText));
$totalWords = count($words);
$wordsPerShot = max(1, ceil($totalWords / $shotCount));

$wordIndex = 0;
foreach ($shots as $shotIdx => $shot) {
    $shotWords = array_slice($words, $wordIndex, $wordsPerShot);
    $wordIndex += $wordsPerShot;

    // Last shot gets remaining words
    if ($shotIdx === $shotCount - 1 && $wordIndex < $totalWords) {
        $shotWords = array_merge($shotWords, array_slice($words, $wordIndex));
    }

    $shotNarratorText = implode(' ', $shotWords);
    // ... create segment and assign to shot
}
```

**Key characteristics:**
1. Combines ALL narrator segments into single text string
2. Splits by whitespace into individual words
3. Calculates `wordsPerShot = ceil(totalWords / shotCount)`
4. Each shot gets approximately equal word portions
5. Last shot absorbs remainder
6. Creates NEW segment objects with distributed text
7. Sets `narratorVoiceId` via `$this->getNarratorVoice()`
8. Sets multiple shot properties: `narratorOverlay`, `hasNarratorVoiceover`, `narratorText`, `narration`

### Internal Thought Distribution (Segment-Split) - Lines 24115-24176

Location: `VideoWizard.php` method `markInternalThoughtAsVoiceover()`

```php
// Current internal thought segment-split algorithm (lines 24131-24165)
$shotCount = count($shots);
$internalCount = count($internalSegments);
$internalIndex = 0;
$internalsPerShot = max(1, ceil($internalCount / $shotCount));

foreach ($shots as $shotIdx => $shot) {
    $shotInternals = [];

    for ($i = 0; $i < $internalsPerShot && $internalIndex < $internalCount; $i++, $internalIndex++) {
        $shotInternals[] = $internalSegments[$internalIndex];  // Whole segments!
    }

    if (!empty($shotInternals)) {
        $shots[$shotIdx]['internalThoughtOverlay'] = $shotInternals;
        $shots[$shotIdx]['hasInternalVoiceover'] = true;
        $internalText = implode(' ', array_column($shotInternals, 'text'));
        $shots[$shotIdx]['internalThoughtText'] = $internalText;
        // ...
    }
}
```

**Key characteristics:**
1. Distributes WHOLE segments, not words
2. Calculates `internalsPerShot = ceil(segmentCount / shotCount)`
3. Some shots may get 0 segments if segmentCount < shotCount
4. No text splitting within segments
5. Sets `internalVoiceId` via `$this->getVoiceForCharacterName($speaker)`
6. Properties set: `internalThoughtOverlay`, `hasInternalVoiceover`, `internalThoughtText`, `internalThoughtSpeaker`

### The Asymmetry Problem

| Aspect | Narrator | Internal Thought |
|--------|----------|------------------|
| **Distribution unit** | Words | Whole segments |
| **Granularity** | Fine (every shot gets text) | Coarse (shots may be empty) |
| **Empty shots** | Never (words distributed evenly) | Possible (if segments < shots) |
| **Text continuity** | Flows naturally across shots | Segment boundaries intact |
| **Edge case handling** | Last shot absorbs remainder | Later shots may be empty |

**Impact:** For 3 internal thought segments across 5 shots:
- Narrator approach: Each shot gets ~20% of words
- Current approach: Shots 1-3 get segments, shots 4-5 get nothing

## Voice Assignment Patterns

### getNarratorVoice() - Line 8594

```php
public function getNarratorVoice(): string
{
    // Check for designated narrator character
    $narrator = $this->getNarratorCharacter();
    if ($narrator && !empty($narrator['voice']['id'])) {
        return $narrator['voice']['id'];
    }
    // Fall back to animation narrator setting
    return $this->animation['narrator']['voice']
        ?? $this->animation['voiceover']['voice']
        ?? 'nova';
}
```

**Fallback chain:**
1. Character Bible narrator character voice
2. `animation.narrator.voice`
3. `animation.voiceover.voice`
4. `'nova'` default

### getVoiceForCharacterName() - Line 23187

```php
public function getVoiceForCharacterName(string $characterName): string
{
    $charBible = $this->sceneMemory['characterBible']['characters'] ?? [];
    $nameUpper = strtoupper(trim($characterName));

    // Special case: narrator uses narrator voice
    if ($nameUpper === 'NARRATOR') {
        return $this->animation['narrator']['voice'] ?? 'fable';
    }

    foreach ($charBible as $char) {
        $charNameUpper = strtoupper(trim($char['name'] ?? ''));
        if ($charNameUpper === $nameUpper) {
            // New voice array structure
            if (is_array($char['voice'] ?? null) && !empty($char['voice']['id'])) {
                return $char['voice']['id'];
            }
            // Legacy string
            if (is_string($char['voice'] ?? null) && !empty($char['voice'])) {
                return $char['voice'];
            }
            // Gender-based fallback
            $gender = strtolower($char['gender'] ?? $char['voice']['gender'] ?? '');
            if (str_contains($gender, 'female')) return 'nova';
            if (str_contains($gender, 'male')) return 'onyx';
        }
    }

    // Consistent fallback based on name hash
    $hash = crc32($nameUpper);
    $voices = ['echo', 'onyx', 'nova', 'shimmer', 'alloy'];
    return $voices[$hash % count($voices)];
}
```

**Fallback chain:**
1. Character Bible voice.id (array structure)
2. Character Bible voice (legacy string)
3. Gender-based default (female=nova, male=onyx)
4. Name hash for consistency (deterministic)

### VoiceoverService.getVoiceForSpeaker() - Line 431

Similar logic but with Kokoro TTS provider support:
- Maps OpenAI voices to Kokoro equivalents when needed
- Same Character Bible lookup pattern
- Same hash-based fallback for consistency

## Character Bible Voice Storage

Voice data is stored in `$this->sceneMemory['characterBible']['characters'][]`:

```php
// Voice structure (new format)
$character['voice'] = [
    'id' => 'nova',           // Voice ID
    'gender' => 'female',     // Voice gender
    'style' => 'friendly',    // Voice style
    'speed' => 1.0,           // Playback speed
    'pitch' => 'medium',      // Pitch setting
];

// Voice structure (legacy format)
$character['voice'] = 'nova';  // Just the ID string
```

**Voice assignment points:**
1. `addCharacterFromParser()` - Initial character creation
2. `applyCharacterVoicePreset()` - Preset application
3. `determineVoiceForSpeaker()` - Auto-assignment by gender/role
4. Direct property setting via `$character['voice']['id'] = $voiceId`

## M8 Non-Blocking Validation Pattern

From Phase 14's `validateAndFixTransitions()` (DialogueSceneDecomposerService.php lines 2260-2334):

```php
public function validateAndFixTransitions(array $shots): array
{
    if (count($shots) < 2) {
        return $shots;
    }

    $jumpCutsDetected = 0;
    $scaleAdjustments = 0;

    for ($i = 1; $i < count($shots); $i++) {
        // Check condition
        if ($prevType === $currType) {
            $jumpCutsDetected++;
            Log::warning('DialogueSceneDecomposer: Jump cut detected - same shot type', [
                'shot_index' => $i,
                'prev_shot' => $i - 1,
                'type' => $currType,
                // ... context
            ]);

            // Optional fix (non-blocking)
            $shots[$i]['type'] = $this->getWiderShotType($currType);
            $shots[$i]['scaleAdjusted'] = true;
            $scaleAdjustments++;
        }
    }

    // Log summary
    Log::info('DialogueSceneDecomposer: Transition validation complete (FLOW-03)', [
        'total_shots' => count($shots),
        'jump_cuts_detected' => $jumpCutsDetected,
        'scale_adjustments' => $scaleAdjustments,
    ]);

    return $shots;
}
```

**Key pattern elements:**
1. Early return for edge cases (< 2 items)
2. Counter variables for statistics
3. Iterate through items checking condition
4. `Log::warning()` for issues detected
5. Optional fix/adjustment (non-blocking)
6. Flag set on modified items (`scaleAdjusted`)
7. `Log::info()` summary at end with statistics
8. Return modified array (generation continues)

## Recommended Approach

### VOC-03: Unified Distribution Strategy

**Goal:** Make `markInternalThoughtAsVoiceover()` use the same word-split algorithm as `overlayNarratorSegments()`.

**Implementation:**

```php
protected function markInternalThoughtAsVoiceover(int $sceneIndex, array $shots, array $speechSegments): array
{
    if (empty($shots) || empty($speechSegments)) {
        return $shots;
    }

    // Extract internal thought segments
    $internalSegments = array_values(array_filter($speechSegments, function($seg) {
        $type = $seg['type'] ?? null;
        return $type !== null && strtolower($type) === SpeechSegment::TYPE_INTERNAL;
    }));

    if (empty($internalSegments)) {
        return $shots;
    }

    // VOC-03: Use same word-split algorithm as narrator
    $shotCount = count($shots);

    // Combine all internal thought text for distribution
    $allInternalText = implode(' ', array_column($internalSegments, 'text'));
    $words = preg_split('/\s+/', trim($allInternalText));
    $totalWords = count($words);
    $wordsPerShot = max(1, ceil($totalWords / $shotCount));

    // Get speaker from first segment (internal thoughts typically have one speaker)
    $speaker = $internalSegments[0]['speaker'] ?? null;
    $voiceId = $speaker ? $this->getVoiceForCharacterName($speaker) : $this->getNarratorVoice();

    Log::info('Distributing internal thought text across all shots (VOC-03)', [
        'sceneIndex' => $sceneIndex,
        'totalWords' => $totalWords,
        'shotCount' => $shotCount,
        'wordsPerShot' => $wordsPerShot,
        'speaker' => $speaker,
    ]);

    $wordIndex = 0;
    foreach ($shots as $shotIdx => $shot) {
        $shotWords = array_slice($words, $wordIndex, $wordsPerShot);
        $wordIndex += $wordsPerShot;

        // Last shot gets remaining words
        if ($shotIdx === $shotCount - 1 && $wordIndex < $totalWords) {
            $shotWords = array_merge($shotWords, array_slice($words, $wordIndex));
        }

        $shotInternalText = implode(' ', $shotWords);

        if (!empty($shotInternalText)) {
            // Create internal thought segment for this shot
            $shotInternals = [[
                'id' => 'seg-internal-shot-' . $shotIdx . '-' . uniqid(),
                'type' => SpeechSegment::TYPE_INTERNAL,
                'text' => $shotInternalText,
                'speaker' => $speaker,
                'needsLipSync' => false,
                'order' => $shotIdx,
            ]];

            $shots[$shotIdx]['internalThoughtOverlay'] = $shotInternals;
            $shots[$shotIdx]['hasInternalVoiceover'] = true;
            $shots[$shotIdx]['internalThoughtText'] = $shotInternalText;
            $shots[$shotIdx]['internalVoiceId'] = $voiceId;

            if ($speaker) {
                $shots[$shotIdx]['internalThoughtSpeaker'] = $speaker;
            }
        }
    }

    return $shots;
}
```

**Key changes:**
1. Combine all internal text into single string
2. Split by whitespace into words
3. Calculate `wordsPerShot` like narrator
4. Distribute words evenly across all shots
5. Last shot gets remainder
6. Create NEW segment objects (not reuse originals)
7. Voice ID assigned consistently

### VOC-04: Voice Continuity Validation

**Goal:** Add `validateVoiceContinuity()` method following M8 pattern.

**Implementation:**

```php
/**
 * VOC-04: Validate voice continuity across scenes.
 * Same character must maintain same voice throughout the video.
 * Non-blocking validation: logs warnings but doesn't halt generation.
 *
 * @param array $scenes All decomposed scenes
 * @return array Validation result with warnings
 */
protected function validateVoiceContinuity(array $scenes): array
{
    $characterVoices = [];  // Track: character => first assigned voice
    $mismatches = [];
    $validated = 0;

    foreach ($scenes as $sceneIndex => $scene) {
        $shots = $scene['shots'] ?? [];

        foreach ($shots as $shotIndex => $shot) {
            // Check dialogue/monologue speaker
            $speaker = $shot['speakingCharacter'] ?? $shot['character'] ?? null;
            $voiceId = $shot['voiceId'] ?? null;

            if ($speaker && $voiceId) {
                $speakerKey = strtoupper(trim($speaker));

                if (!isset($characterVoices[$speakerKey])) {
                    // First occurrence - register this voice
                    $characterVoices[$speakerKey] = [
                        'voiceId' => $voiceId,
                        'firstScene' => $sceneIndex,
                        'firstShot' => $shotIndex,
                    ];
                } else {
                    // Check for mismatch
                    $expected = $characterVoices[$speakerKey]['voiceId'];
                    if ($voiceId !== $expected) {
                        $mismatches[] = [
                            'character' => $speaker,
                            'sceneIndex' => $sceneIndex,
                            'shotIndex' => $shotIndex,
                            'expected' => $expected,
                            'actual' => $voiceId,
                            'firstAssigned' => $characterVoices[$speakerKey],
                        ];

                        Log::warning('Voice continuity mismatch detected (VOC-04)', [
                            'character' => $speaker,
                            'scene' => $sceneIndex,
                            'shot' => $shotIndex,
                            'expected' => $expected,
                            'actual' => $voiceId,
                        ]);
                    }
                }
                $validated++;
            }

            // Check internal thought speaker
            $internalSpeaker = $shot['internalThoughtSpeaker'] ?? null;
            $internalVoice = $shot['internalVoiceId'] ?? null;

            if ($internalSpeaker && $internalVoice) {
                $speakerKey = strtoupper(trim($internalSpeaker));

                if (!isset($characterVoices[$speakerKey])) {
                    $characterVoices[$speakerKey] = [
                        'voiceId' => $internalVoice,
                        'firstScene' => $sceneIndex,
                        'firstShot' => $shotIndex,
                        'type' => 'internal',
                    ];
                } else {
                    $expected = $characterVoices[$speakerKey]['voiceId'];
                    if ($internalVoice !== $expected) {
                        $mismatches[] = [
                            'character' => $internalSpeaker,
                            'sceneIndex' => $sceneIndex,
                            'shotIndex' => $shotIndex,
                            'expected' => $expected,
                            'actual' => $internalVoice,
                            'type' => 'internal',
                        ];

                        Log::warning('Voice continuity mismatch in internal thought (VOC-04)', [
                            'character' => $internalSpeaker,
                            'scene' => $sceneIndex,
                            'shot' => $shotIndex,
                            'expected' => $expected,
                            'actual' => $internalVoice,
                        ]);
                    }
                }
                $validated++;
            }
        }
    }

    // Log summary (same as M8 pattern)
    Log::info('Voice continuity validation complete (VOC-04)', [
        'charactersTracked' => count($characterVoices),
        'assignmentsValidated' => $validated,
        'mismatchesFound' => count($mismatches),
    ]);

    return [
        'valid' => empty($mismatches),
        'characterVoices' => $characterVoices,
        'mismatches' => $mismatches,
        'statistics' => [
            'characters' => count($characterVoices),
            'validated' => $validated,
            'mismatches' => count($mismatches),
        ],
    ];
}
```

**Integration point:** Call after shot decomposition completes, before TTS generation.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Voice lookup by character | Custom lookup | `getVoiceForCharacterName()` | Handles all fallback cases, name normalization |
| Narrator voice selection | Direct property access | `getNarratorVoice()` | Full fallback chain with Character Bible |
| TTS voice mapping | Manual mapping | `VoiceoverService.getVoiceForSpeaker()` | Handles Kokoro/OpenAI provider switching |
| Text validation | Inline checks | Existing Phase 15 guards | `empty(trim($text))` pattern established |

## Common Pitfalls

### Pitfall 1: Inconsistent Name Normalization
**What goes wrong:** Character "JOHN" not matching "John" or " john "
**Why it happens:** Case sensitivity and whitespace in comparisons
**How to avoid:** Always use `strtoupper(trim($name))` for comparisons
**Warning signs:** Voice lookup failing for characters that exist in Bible

### Pitfall 2: Voice ID vs Voice Object
**What goes wrong:** Trying to use voice array as string, or string where array expected
**Why it happens:** Two data formats exist (legacy string, new array)
**How to avoid:** Check both formats like existing code does:
```php
if (is_array($char['voice'] ?? null) && !empty($char['voice']['id'])) {
    return $char['voice']['id'];
}
if (is_string($char['voice'] ?? null) && !empty($char['voice'])) {
    return $char['voice'];
}
```
**Warning signs:** TTS receiving array or null instead of string

### Pitfall 3: Empty Text After Word Split
**What goes wrong:** Shot gets empty string after distribution
**Why it happens:** Very few words distributed across many shots
**How to avoid:** Use `max(1, ceil($totalWords / $shotCount))` and check before assignment
**Warning signs:** Shots with `hasInternalVoiceover: true` but empty `internalThoughtText`

### Pitfall 4: Blocking Validation
**What goes wrong:** Validation throws exception, halts video generation
**Why it happens:** Using throw instead of log-and-continue
**How to avoid:** Follow M8 pattern: Log::warning(), set flags, return array
**Warning signs:** Generation stops on first validation failure

## Code Examples

### Word-Split Distribution (from overlayNarratorSegments)
```php
// Source: VideoWizard.php lines 23921-23945
$allNarratorText = implode(' ', array_column($narratorSegments, 'text'));
$words = preg_split('/\s+/', trim($allNarratorText));
$totalWords = count($words);
$wordsPerShot = max(1, ceil($totalWords / $shotCount));

$wordIndex = 0;
foreach ($shots as $shotIdx => $shot) {
    $shotWords = array_slice($words, $wordIndex, $wordsPerShot);
    $wordIndex += $wordsPerShot;

    if ($shotIdx === $shotCount - 1 && $wordIndex < $totalWords) {
        $shotWords = array_merge($shotWords, array_slice($words, $wordIndex));
    }

    $shotNarratorText = implode(' ', $shotWords);
    // ... use $shotNarratorText
}
```

### M8 Non-Blocking Validation Pattern
```php
// Source: DialogueSceneDecomposerService.php lines 2260-2334
public function validateAndFixTransitions(array $shots): array
{
    if (count($shots) < 2) {
        return $shots;
    }

    $issuesDetected = 0;

    for ($i = 1; $i < count($shots); $i++) {
        if ($condition) {
            $issuesDetected++;
            Log::warning('Issue detected', ['context' => $context]);
            // Optional non-blocking fix
            $shots[$i]['issueFlag'] = true;
        }
    }

    Log::info('Validation complete', [
        'total' => count($shots),
        'issues' => $issuesDetected,
    ]);

    return $shots;
}
```

### Character Voice Lookup
```php
// Source: VideoWizard.php lines 23197-23218
foreach ($charBible as $char) {
    $charNameUpper = strtoupper(trim($char['name'] ?? ''));
    if ($charNameUpper === $nameUpper) {
        if (is_array($char['voice'] ?? null) && !empty($char['voice']['id'])) {
            return $char['voice']['id'];
        }
        if (is_string($char['voice'] ?? null) && !empty($char['voice'])) {
            return $char['voice'];
        }
        // Gender fallback
        $gender = strtolower($char['gender'] ?? '');
        if (str_contains($gender, 'female')) return 'nova';
        if (str_contains($gender, 'male')) return 'onyx';
    }
}
// Hash-based fallback for consistency
$hash = crc32($nameUpper);
$voices = ['echo', 'onyx', 'nova', 'shimmer', 'alloy'];
return $voices[$hash % count($voices)];
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Segment-split internal thoughts | Should be word-split | Phase 16 | Even distribution |
| No voice continuity check | validateVoiceContinuity() | Phase 16 | Consistent characters |
| Blocking validation | Non-blocking (M8 pattern) | M8 Phase 14 | Generation continues |
| Silent type coercion | Log::error before default | Phase 15 | Data integrity visibility |

## Open Questions

None - all aspects have clear implementation paths based on existing codebase patterns.

## Sources

### Primary (HIGH confidence)
- VideoWizard.php lines 23899-24176 - overlayNarratorSegments and markInternalThoughtAsVoiceover implementations
- VideoWizard.php lines 8594-8604 - getNarratorVoice implementation
- VideoWizard.php lines 23187-23219 - getVoiceForCharacterName implementation
- DialogueSceneDecomposerService.php lines 2260-2334 - M8 validateAndFixTransitions pattern
- VoiceoverService.php lines 431-485 - getVoiceForSpeaker implementation

### Secondary (MEDIUM confidence)
- Phase 14-01-PLAN.md - M8 validation pattern requirements
- Phase 15-01-PLAN.md - Non-blocking validation decisions

## Metadata

**Confidence breakdown:**
- Distribution algorithm: HIGH - Direct code analysis of existing implementations
- Voice continuity validation: HIGH - Clear pattern from M8 to follow
- Voice assignment: HIGH - Multiple implementations analyzed in detail
- Integration points: HIGH - Existing call sites identified

**Research date:** 2026-01-25
**Valid until:** 2026-02-25 (30 days - stable internal codebase patterns)
