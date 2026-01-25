# Phase 18: Multi-Speaker Support - Research

**Researched:** 2026-01-25
**Domain:** Multi-speaker dialogue TTS, shot/reverse-shot cinematography, voice tracking
**Confidence:** HIGH

## Summary

Multi-speaker support extends the existing dialogue decomposition system to track multiple speakers per shot rather than only the first speaker. The codebase already has robust infrastructure: `VoiceRegistryService` (Phase 17), `DialogueSceneDecomposerService`, `SpeechSegment`/`SpeechSegmentParser`, and `VoiceoverService` with multi-voice capabilities.

The current limitation is a specific pattern in `VideoWizard.php` (lines 23335 and 23865) that extracts only the first speaker when multiple speakers exist in a shot:
```php
$firstSpeaker = array_keys($speakers)[0] ?? null;
```

The solution involves expanding the shot data structure to support a `speakers` array instead of single speaker fields, then updating downstream TTS processing to generate audio for each speaker in sequence.

**Primary recommendation:** Extend shot structure with `speakers` array containing `[{name, voiceId, text}]` entries while maintaining backward compatibility with existing single-speaker fields.

## Standard Stack

The established libraries/tools for this domain:

### Core (Existing in Codebase)
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| VoiceRegistryService | Phase 17 | Voice assignment tracking | Already centralizes voice ID lookups |
| DialogueSceneDecomposerService | Current | Scene decomposition to shots | Already parses multi-speaker dialogue |
| VoiceoverService | Current | TTS generation | Already supports multi-voice generation |
| SpeechSegment | Current | Speech segment data model | Already tracks speaker, voiceId, type |
| SpeechSegmentParser | Current | Parse dialogue text | Already extracts multiple speakers |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Kokoro TTS | 0.9+ | Local multi-voice TTS | When self-hosted TTS preferred |
| OpenAI TTS | API | Cloud multi-voice TTS | Default provider |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Sequential TTS calls | Google Multi-Speaker API | Google limited to 2 speakers max |
| Per-shot speakers array | Single combined audio | Combined requires complex mixing/timing |

**Installation:**
No new packages required. Uses existing services.

## Architecture Patterns

### Recommended Data Structure

The proposed shot structure expands the existing pattern:

```php
// Current shot structure (single speaker)
$shot = [
    'speakingCharacter' => 'HERO',
    'voiceId' => 'nova',
    'dialogue' => 'Hello world',
    'monologue' => 'Hello world',
    // ... other fields
];

// Proposed multi-speaker structure (VOC-06)
$shot = [
    // Backward-compatible single speaker (for simple shots)
    'speakingCharacter' => 'HERO',  // Primary/first speaker
    'voiceId' => 'nova',            // Primary voice

    // NEW: Multi-speaker array (for dialogue scenes)
    'speakers' => [
        [
            'name' => 'HERO',
            'voiceId' => 'nova',
            'text' => 'Hello villain!',
            'order' => 0,
            'duration' => null,  // Set after TTS
            'audioUrl' => null,  // Set after TTS
        ],
        [
            'name' => 'VILLAIN',
            'voiceId' => 'onyx',
            'text' => 'We meet again...',
            'order' => 1,
            'duration' => null,
            'audioUrl' => null,
        ],
    ],

    // Combined dialogue for legacy compatibility
    'dialogue' => 'HERO: Hello villain! VILLAIN: We meet again...',
    'monologue' => 'HERO: Hello villain! VILLAIN: We meet again...',

    // Derived metadata
    'speakingCharacters' => ['HERO', 'VILLAIN'],
    'speakerCount' => 2,
    'isMultiSpeaker' => true,
];
```

### Pattern 1: Speaker Entry Builder

**What:** Helper method to create speaker entries with voice registry integration

**When to use:** Building `speakers` array entries

**Example:**
```php
// Source: VoiceRegistryService integration pattern
protected function buildSpeakerEntry(
    string $characterName,
    string $text,
    int $order,
    VoiceRegistryService $voiceRegistry,
    callable $voiceFallback
): array {
    return [
        'name' => $characterName,
        'voiceId' => $voiceRegistry->getVoiceForCharacter($characterName, $voiceFallback),
        'text' => $text,
        'order' => $order,
        'duration' => null,
        'audioUrl' => null,
    ];
}
```

### Pattern 2: Multi-Speaker TTS Generation

**What:** Generate audio for each speaker sequentially with timing data

**When to use:** Processing shots with `speakers` array

**Example:**
```php
// Source: VoiceoverService pattern
public function generateMultiSpeakerAudio(
    WizardProject $project,
    array $shot,
    array $options = []
): array {
    $speakers = $shot['speakers'] ?? [];
    $results = [];
    $totalDuration = 0;

    foreach ($speakers as $index => $speaker) {
        // Skip empty text
        if (empty(trim($speaker['text'] ?? ''))) {
            continue;
        }

        // Generate TTS for this speaker
        $audioResult = $this->generateVoiceAudio(
            $project,
            $speaker['text'],
            $speaker['voiceId'],
            $options
        );

        if ($audioResult['success']) {
            $results[] = [
                'name' => $speaker['name'],
                'voiceId' => $speaker['voiceId'],
                'text' => $speaker['text'],
                'order' => $index,
                'startTime' => $totalDuration,
                'duration' => $audioResult['duration'],
                'audioUrl' => $audioResult['audioUrl'],
            ];
            $totalDuration += $audioResult['duration'];
        }
    }

    return [
        'success' => true,
        'speakers' => $results,
        'totalDuration' => $totalDuration,
        'combinedAudioUrl' => $this->combineAudioSegments($results),
    ];
}
```

### Pattern 3: Backward-Compatible Shot Processing

**What:** Handle both single and multi-speaker shots in downstream code

**When to use:** Anywhere shots are processed for TTS/video generation

**Example:**
```php
// Source: Codebase pattern for gradual migration
protected function getSpeakersFromShot(array $shot): array {
    // Prefer new multi-speaker array
    if (!empty($shot['speakers'])) {
        return $shot['speakers'];
    }

    // Fall back to single speaker (backward compatibility)
    if (!empty($shot['speakingCharacter'])) {
        return [[
            'name' => $shot['speakingCharacter'],
            'voiceId' => $shot['voiceId'] ?? null,
            'text' => $shot['dialogue'] ?? $shot['monologue'] ?? '',
            'order' => 0,
        ]];
    }

    return [];
}
```

### Anti-Patterns to Avoid

- **Overwriting single-speaker fields:** Keep `speakingCharacter` populated with first speaker for legacy code
- **Mixing `speakingCharacters` array with `speakers` array:** These serve different purposes - `speakingCharacters` is just names, `speakers` has full data
- **Generating combined audio before individual:** Always generate individual speaker audio first, then combine if needed
- **Ignoring VoiceRegistryService:** Always use registry for voice lookups to maintain consistency

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Voice ID lookup | Custom lookup per call | VoiceRegistryService | First-occurrence-wins, mismatch detection |
| Dialogue parsing | Regex per use case | SpeechSegmentParser | Handles all formats, validated |
| Speaker detection | Manual string parsing | `detectSpeakers()` in VoiceoverService | Already exists |
| Audio combination | FFmpeg calls | VoiceoverService audio concat | Already handles timing |
| Character name matching | Case-sensitive compare | `strtoupper(trim())` pattern | Codebase standard |

**Key insight:** The codebase already has all the building blocks. Multi-speaker support is primarily a data structure expansion and refactoring existing `$firstSpeaker` patterns, not new functionality.

## Common Pitfalls

### Pitfall 1: Losing First Speaker During Migration

**What goes wrong:** Removing `speakingCharacter` field breaks existing UI and video generation code

**Why it happens:** Overeager cleanup during refactoring

**How to avoid:** Always populate both `speakingCharacter` (first speaker) AND `speakers` array

**Warning signs:** Null pointer errors in shot rendering, missing voice assignments

### Pitfall 2: Voice ID Inconsistency Across Shots

**What goes wrong:** Same character gets different voices in different shots

**Why it happens:** Looking up voice ID at shot creation instead of using registry

**How to avoid:** Always use `VoiceRegistryService::getVoiceForCharacter()` with fallback callback

**Warning signs:** VoiceRegistry mismatch warnings in logs

### Pitfall 3: Empty Speaker Text in Array

**What goes wrong:** TTS API errors from empty strings

**Why it happens:** Copying speaker entries without checking for text content

**How to avoid:** Filter speakers array: `array_filter($speakers, fn($s) => !empty(trim($s['text'])))`

**Warning signs:** OpenAI/Kokoro API errors about empty input

### Pitfall 4: Audio Timing Assumptions

**What goes wrong:** Combined audio has wrong speaker ordering or overlapping

**Why it happens:** Assuming fixed duration per word count

**How to avoid:** Calculate `startTime` from actual generated `duration` values, not estimates

**Warning signs:** Audio segments out of order, dialogue sounds rushed

### Pitfall 5: Shot/Reverse-Shot Still Showing Single Character

**What goes wrong:** Multi-speaker shot assigned but video shows only one character

**Why it happens:** Confusion between "multiple speakers in narration" vs "multiple visible characters"

**How to avoid:** Multi-speaker is for AUDIO tracks. Visual shot still follows single-character constraint (FLOW-02)

**Warning signs:** Prompts trying to render two speaking characters simultaneously

## Code Examples

### Example 1: Refactored firstSpeaker Pattern

```php
// Source: VideoWizard.php lines 23335 and 23865 refactoring
// BEFORE (current):
$firstSpeaker = array_keys($shotSpeakers)[0] ?? null;
if ($firstSpeaker) {
    $voice = $this->getVoiceForCharacterName($firstSpeaker);
    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['voiceId'] = $voice;
}

// AFTER (multi-speaker):
$speakerNames = array_keys($shotSpeakers);
$speakersArray = [];

foreach ($speakerNames as $order => $speakerName) {
    $speakerText = $shotSpeakers[$speakerName] ?? '';
    if (empty(trim($speakerText))) continue;

    $speakersArray[] = [
        'name' => $speakerName,
        'voiceId' => $this->voiceRegistry->getVoiceForCharacter(
            $speakerName,
            fn($n) => $this->getVoiceForCharacterName($n)
        ),
        'text' => $speakerText,
        'order' => $order,
    ];
}

// Update shot with multi-speaker data
$shot = &$this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex];
$shot['speakers'] = $speakersArray;
$shot['speakerCount'] = count($speakersArray);
$shot['isMultiSpeaker'] = count($speakersArray) > 1;

// Maintain backward compatibility
if (!empty($speakersArray)) {
    $shot['speakingCharacter'] = $speakersArray[0]['name'];
    $shot['voiceId'] = $speakersArray[0]['voiceId'];
    $shot['speakingCharacters'] = array_column($speakersArray, 'name');
}
```

### Example 2: DialogueSceneDecomposerService Integration

```php
// Source: DialogueSceneDecomposerService::createDialogueShot enhancement
protected function createDialogueShot(/* existing params */): array {
    // ... existing shot creation code ...

    // NEW: Initialize speakers array with single speaker
    $shot['speakers'] = [[
        'name' => $speaker,
        'voiceId' => $charData['voiceId'] ?? 'echo',
        'text' => $exchange['text'],
        'order' => 0,
    ]];
    $shot['speakerCount'] = 1;
    $shot['isMultiSpeaker'] = false;

    return $shot;
}

// NEW: Method to merge speakers when shots cover overlapping dialogue
public function mergeOverlappingSpeakers(array $shot, array $additionalSpeaker): array {
    $shot['speakers'][] = [
        'name' => $additionalSpeaker['name'],
        'voiceId' => $additionalSpeaker['voiceId'],
        'text' => $additionalSpeaker['text'],
        'order' => count($shot['speakers']),
    ];

    $shot['speakerCount'] = count($shot['speakers']);
    $shot['isMultiSpeaker'] = $shot['speakerCount'] > 1;
    $shot['speakingCharacters'] = array_column($shot['speakers'], 'name');

    return $shot;
}
```

### Example 3: TTS Processing for Multi-Speaker Shots

```php
// Source: VoiceoverService pattern extension
public function processMultiSpeakerShot(WizardProject $project, array $shot): array {
    $speakers = $shot['speakers'] ?? [];

    if (empty($speakers)) {
        // Fall back to single speaker processing
        return $this->processSingleSpeakerShot($project, $shot);
    }

    $audioSegments = [];
    $currentTime = 0;

    foreach ($speakers as $speaker) {
        if (empty(trim($speaker['text']))) {
            continue;
        }

        // Generate TTS for this speaker
        $result = $this->generateSceneVoiceover($project, [
            'id' => $shot['id'] ?? 'shot',
            'narration' => $speaker['text'],
        ], [
            'voice' => $speaker['voiceId'],
        ]);

        if ($result['success']) {
            $speaker['audioUrl'] = $result['audioUrl'];
            $speaker['duration'] = $result['duration'];
            $speaker['startTime'] = $currentTime;
            $currentTime += $result['duration'];

            $audioSegments[] = $speaker;
        }
    }

    return [
        'success' => true,
        'speakers' => $audioSegments,
        'totalDuration' => $currentTime,
        'isMultiSpeaker' => count($audioSegments) > 1,
    ];
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Single voiceId per shot | speakers array with per-speaker voiceId | VOC-06 (this phase) | Supports dialogue scenes |
| firstSpeaker extraction | Full speaker list processing | VOC-06 (this phase) | All speakers tracked |
| Ad-hoc voice lookup | VoiceRegistryService | Phase 17 (VOC-05) | Consistent voice assignment |
| SpeechType enum | SpeechSegment class | Previous phase | Rich segment model |

**Industry standards (2025-2026):**
- Google Multi-Speaker API: Up to 2 speakers in single API call
- Microsoft VibeVoice: 4 distinct speakers, 90 min generation
- Gemini TTS: Multi-speaker with natural language control
- ElevenLabs: Multi-voice podcasts, voice cloning

**Deprecated/outdated:**
- Single `$voiceId` field without `speakers` array: Works but loses multi-speaker data
- `$firstSpeaker = array_keys($speakers)[0]`: Must be replaced with full iteration

## Open Questions

Things that couldn't be fully resolved:

1. **Maximum speakers per shot?**
   - What we know: Industry supports 2-4 speakers per audio segment
   - What's unclear: Should there be a hard limit in our system?
   - Recommendation: Set soft limit of 4 speakers per shot, log warning if exceeded

2. **Audio combination strategy?**
   - What we know: Can concatenate or keep separate
   - What's unclear: Does Multitalk need combined or separate audio tracks?
   - Recommendation: Generate both - separate for flexibility, combined for legacy

3. **Shot/reverse-shot visual vs audio distinction?**
   - What we know: Visual shows single character (FLOW-02), audio can have multiple
   - What's unclear: UI presentation of multi-speaker shots
   - Recommendation: UI shows primary speaker visually, lists all speakers in metadata

## Sources

### Primary (HIGH confidence)
- Codebase: VoiceRegistryService.php - Voice management patterns
- Codebase: DialogueSceneDecomposerService.php - Shot creation patterns
- Codebase: VoiceoverService.php - Multi-voice TTS generation
- Codebase: SpeechSegment.php / SpeechSegmentParser.php - Segment data model
- Codebase: VideoWizard.php lines 23335, 23865 - Current firstSpeaker pattern

### Secondary (MEDIUM confidence)
- [Google Cloud Multi-Speaker TTS](https://cloud.google.com/text-to-speech/docs/create-dialogue-with-multispeakers) - Multi-speaker API pattern
- [Gemini TTS Speech Generation](https://ai.google.dev/gemini-api/docs/speech-generation) - Multi-speaker with style control
- [ShoulderShot Research](https://arxiv.org/html/2508.07597) - Over-the-shoulder dialogue patterns

### Tertiary (LOW confidence)
- [HoloCine AI Film](https://studio.aifilms.ai/blog/holocine-ai-film-multishot-narratives) - Multi-shot narrative coherence
- [WAN 2.6 Guide](https://apatero.com/blog/wan-2-6-complete-guide-multi-shot-video-generation-2025) - Two-person reference generation

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All components exist in codebase, just need integration
- Architecture: HIGH - Clear pattern from existing code, minimal new design needed
- Pitfalls: HIGH - Based on actual codebase patterns and common PHP/array issues

**Research date:** 2026-01-25
**Valid until:** 2026-02-25 (stable domain, existing codebase)

---

## Key Implementation Notes

### Files Requiring Changes

1. **VideoWizard.php** (lines 23335, 23865)
   - Replace `$firstSpeaker = array_keys($speakers)[0]` with full speaker iteration
   - Build `speakers` array with all speaker entries
   - Maintain backward compatibility fields

2. **DialogueSceneDecomposerService.php**
   - Initialize `speakers` array in `createDialogueShot()`
   - Add method for merging overlapping speakers

3. **VoiceoverService.php**
   - Add `processMultiSpeakerShot()` method
   - Extend `generateSegmentedAudio()` to handle `speakers` array

### Backward Compatibility Requirements

- `speakingCharacter` field MUST remain populated (first speaker)
- `voiceId` field MUST remain populated (first speaker's voice)
- `dialogue`/`monologue` fields MUST remain (combined text)
- New `speakers` array is ADDITIVE, not replacement

### VoiceRegistryService Integration

All voice lookups MUST use VoiceRegistryService:
```php
$voiceId = $this->voiceRegistry->getVoiceForCharacter(
    $speakerName,
    fn($name) => $this->fallbackVoiceLookup($name)
);
```

This ensures Phase 17's first-occurrence-wins behavior is maintained.
