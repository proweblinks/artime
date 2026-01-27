# Phase 25: Voice Prompt Enhancement - Research

**Researched:** 2026-01-27
**Domain:** TTS Voice Direction, Emotional Tags, Performance Cues
**Confidence:** HIGH (based on official documentation + codebase analysis)

## Summary

Voice Prompt Enhancement transforms basic narration text into Hollywood-quality voice direction prompts with emotional tags, pacing markers, vocal quality descriptions, breath sounds, and emotional arc direction. Modern TTS providers (ElevenLabs v3, OpenAI GPT-4o-mini-TTS, PlayHT) support various forms of emotional direction -- from audio tags `[whispers]`, `[crying]` to natural language instructions and SSML markup.

The existing codebase has strong foundations: `SpeechSegment` and `SpeechSegmentParser` handle dialogue parsing, `VoiceoverService` manages TTS generation, `VoiceRegistryService` tracks voice assignments, and `CharacterPsychologyService` maps emotions to physical manifestations. Phase 25 follows the same vocabulary service pattern established in Phase 22-24 (`CinematographyVocabulary`, `TransitionVocabulary`).

**Primary recommendation:** Create a `VoiceDirectionVocabulary` service that maps emotional states to TTS-appropriate direction tags, with provider-specific adapters that convert generic tags into ElevenLabs audio tags, SSML markup, or OpenAI instruction prompts depending on the active TTS provider.

## Standard Stack

The established libraries/tools for this domain:

### Core (Already in Codebase)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| VoiceoverService | existing | TTS generation routing | Unified interface for OpenAI, Kokoro providers |
| VoiceRegistryService | existing | Voice assignment tracking | Phase 17 implementation, character-voice mapping |
| SpeechSegment | existing | Segment data model | Type (narrator/dialogue/internal), emotion, speaker |
| SpeechSegmentParser | existing | Text parsing to segments | Handles `[NARRATOR]`, `CHARACTER:` formats |
| CharacterPsychologyService | existing | Emotion-to-physical mapping | Phase 23, provides emotion vocabulary |

### Supporting (To Be Created)

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| VoiceDirectionVocabulary | new | Emotional direction mappings | All voice prompt building |
| VoicePromptBuilderService | new | Build enhanced voice prompts | Final prompt assembly |
| VoicePacingService | new | Timing/pacing markers | Pause and rhythm direction |

### No External Libraries Needed

Phase 25 requires no new Composer packages. All functionality builds on existing Laravel services pattern established in Phase 22-24.

**Installation:** N/A (internal services only)

## Architecture Patterns

### Recommended Project Structure

```
modules/AppVideoWizard/app/Services/
├── VoiceDirectionVocabulary.php     # Emotion → direction tag mappings
├── VoicePacingService.php           # Pacing markers and timing
├── VoicePromptBuilderService.php    # Assembles final voice prompts
├── VoiceoverService.php             # [existing] TTS generation
├── VoiceRegistryService.php         # [existing] Voice assignment
├── SpeechSegment.php                # [existing] Segment model
└── SpeechSegmentParser.php          # [existing] Text parsing
```

### Pattern 1: Vocabulary Service (Established Pattern)

**What:** Static constant maps + helper methods for domain vocabulary
**When to use:** Any domain-specific terminology that needs standardization
**Example:**

```php
// Source: Follows CinematographyVocabulary pattern from Phase 22
class VoiceDirectionVocabulary
{
    /**
     * Emotional direction tags mapped to TTS-friendly descriptions.
     * Each emotion maps to bracketed tags, description, and intensity variants.
     */
    public const EMOTIONAL_DIRECTION = [
        'trembling' => [
            'tag' => '[trembling]',
            'description' => 'voice shaking with suppressed emotion',
            'elevenlabs_tag' => '[nervous]',
            'ssml' => '<prosody rate="95%" pitch="-5%">',
        ],
        'whisper' => [
            'tag' => '[whisper]',
            'description' => 'hushed intimate tone, barely audible',
            'elevenlabs_tag' => '[whispers]',
            'ssml' => '<amazon:effect name="whispered">',
        ],
        'cracking' => [
            'tag' => '[voice cracks]',
            'description' => 'emotional break mid-word',
            'elevenlabs_tag' => '[crying]',
            'ssml' => null, // Not supported in standard SSML
        ],
    ];
}
```

### Pattern 2: Provider-Specific Adapters

**What:** Transform generic direction into provider-specific markup
**When to use:** When building prompts for different TTS providers
**Example:**

```php
// Source: Follows ModelPromptAdapterService pattern from Phase 22
class VoiceDirectionAdapter
{
    public function adaptForProvider(array $directions, string $provider): string
    {
        return match ($provider) {
            'elevenlabs' => $this->toElevenLabsTags($directions),
            'openai' => $this->toOpenAIInstructions($directions),
            'kokoro' => $this->toKokoroPrompt($directions),
            default => $this->toGenericMarkup($directions),
        };
    }

    protected function toElevenLabsTags(array $directions): string
    {
        // ElevenLabs v3 uses bracketed audio tags inline
        // [whispers], [sighs], [crying], [excited]
        return implode(' ', array_map(fn($d) => $d['elevenlabs_tag'], $directions));
    }

    protected function toOpenAIInstructions(array $directions): string
    {
        // OpenAI GPT-4o-mini-TTS uses instruction prompts
        // "speak in a trembling voice with emotional breaks"
        return implode(', ', array_map(fn($d) => $d['description'], $directions));
    }
}
```

### Pattern 3: Pacing Markers with Timing

**What:** Explicit pause and timing notation
**When to use:** VOC-02 requires timing markers like `[PAUSE 2.5s]`
**Example:**

```php
// Source: Industry standard screenplay notation
class VoicePacingService
{
    public const PAUSE_TYPES = [
        'beat' => ['duration' => 0.5, 'notation' => '[beat]', 'ssml' => '<break time="500ms"/>'],
        'short' => ['duration' => 1.0, 'notation' => '[short pause]', 'ssml' => '<break time="1s"/>'],
        'medium' => ['duration' => 2.0, 'notation' => '[pause]', 'ssml' => '<break time="2s"/>'],
        'long' => ['duration' => 3.0, 'notation' => '[long pause]', 'ssml' => '<break time="3s"/>'],
    ];

    public const PACING_MODIFIERS = [
        'slow' => ['rate_modifier' => 0.85, 'notation' => '[SLOW]'],
        'measured' => ['rate_modifier' => 0.9, 'notation' => '[measured]'],
        'normal' => ['rate_modifier' => 1.0, 'notation' => ''],
        'urgent' => ['rate_modifier' => 1.1, 'notation' => '[urgent]'],
        'rushed' => ['rate_modifier' => 1.2, 'notation' => '[rushed]'],
    ];

    public function insertPauseMarker(float $seconds): string
    {
        return sprintf('[PAUSE %.1fs]', $seconds);
    }
}
```

### Pattern 4: Emotional Arc Direction

**What:** Multi-line arc descriptions for dialogue sequences
**When to use:** VOC-06 requires emotional progression across lines
**Example:**

```php
// Source: Professional voice direction best practices
public function buildEmotionalArc(array $segments, string $arcType): array
{
    $arcPatterns = [
        'building' => ['quiet', 'rising', 'intense', 'peak'],
        'crashing' => ['confident', 'wavering', 'breaking', 'collapsed'],
        'recovering' => ['broken', 'struggling', 'gathering', 'resolved'],
        'masking' => ['controlled', 'slipping', 'recovering', 'forced'],
    ];

    $pattern = $arcPatterns[$arcType] ?? $arcPatterns['building'];
    $segmentCount = count($segments);

    foreach ($segments as $index => $segment) {
        $position = min(floor($index / $segmentCount * 4), 3);
        $segment->emotionalArcNote = $pattern[$position];
    }

    return $segments;
}
```

### Anti-Patterns to Avoid

- **Direct FACS codes in voice prompts:** Research confirmed image models need physical descriptions, not AU codes. Same applies to voice -- use descriptive direction, not technical notation unknown to TTS models.
- **Overloading with tags:** ElevenLabs documentation warns: "Match tags to your voice's character. Don't expect contradictory delivery."
- **Mixing provider-specific syntax:** Generic prompts should be provider-agnostic; adaptation happens at generation time.
- **Hard-coding pause durations:** Use named pause types (beat, short, medium, long) that adapt to speech rate settings.

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Emotion vocabulary | Custom emotion lists | CharacterPsychologyService | Already maps emotions to physical manifestations |
| Segment parsing | Custom regex | SpeechSegmentParser | Handles all formats, validated in production |
| Voice assignment | Manual lookups | VoiceRegistryService | First-occurrence-wins, mismatch detection built in |
| TTS routing | Direct API calls | VoiceoverService | Unified interface, provider switching |
| SSML generation | String concatenation | Dedicated SSML builder class | SSML has strict nesting rules, easy to break |

**Key insight:** The existing SpeechSegment already has an `emotion` property that is parsed from parentheticals like `(sighing)`. Phase 25 enhances this by expanding simple emotion names into full direction tags.

## Common Pitfalls

### Pitfall 1: Provider-Specific Tag Incompatibility

**What goes wrong:** Using ElevenLabs `[whispers]` tag with OpenAI TTS, resulting in the tag being spoken aloud
**Why it happens:** Each TTS provider has different tag/instruction syntax
**How to avoid:** Always adapt through provider-specific adapter layer; never embed raw tags in stored data
**Warning signs:** Brackets appearing in audio output, tags spoken as text

### Pitfall 2: Emotional Tag Overload

**What goes wrong:** Adding `[nervous] [trembling] [hesitant] [scared]` produces unstable output
**Why it happens:** TTS models can't stack conflicting or redundant emotional states
**How to avoid:** Limit to 1-2 emotional tags per segment; choose dominant emotion
**Warning signs:** Audio hallucinations, inconsistent delivery, model ignoring tags

### Pitfall 3: SSML Break Tag Limits

**What goes wrong:** Using many `<break>` tags causes audio instability
**Why it happens:** ElevenLabs documentation: "Using too many break tags in a single generation can cause instability"
**How to avoid:** Use punctuation and ellipses for natural pauses; reserve explicit breaks for dramatic effect
**Warning signs:** Audio stuttering, unnatural gaps, generation failures

### Pitfall 4: Voice-Tag Mismatch

**What goes wrong:** Applying `[giggles]` to a serious professional voice produces poor results
**Why it happens:** TTS voices are trained on specific emotional ranges
**How to avoid:** Match tags to voice character; store voice "capabilities" in VoiceRegistry
**Warning signs:** Tags being ignored, inconsistent emotional delivery

### Pitfall 5: Ignoring Existing Emotion Data

**What goes wrong:** Building new emotion detection when SpeechSegment.emotion already exists
**Why it happens:** Not reading existing codebase thoroughly
**How to avoid:** Extend existing emotion property, don't replace; integrate with CharacterPsychologyService mappings
**Warning signs:** Duplicate emotion tracking, inconsistent emotion names

## Code Examples

Verified patterns from official sources:

### ElevenLabs v3 Audio Tags (Official Documentation)

```php
// Source: https://elevenlabs.io/docs/overview/capabilities/text-to-speech/best-practices

// Emotional states
$text = '[nervous] I... I don\'t know if I can do this.';
$text = '[excited] We did it! We actually did it!';
$text = '[crying] She\'s gone... forever.';

// Human reactions
$text = '[sighs] Fine. I\'ll do it myself.';
$text = '[laughs] You actually believed that?';
$text = '[whispers] Don\'t let them hear us.';

// Cognitive beats
$text = 'I think... [pauses] ...we should leave now.';
$text = '[hesitates] Maybe we could... no, never mind.';
$text = '[stammers] I-I didn\'t mean to...';

// Combined tags for complex delivery
$text = '[whispers] [nervous] Did you hear that?';
```

### SSML Prosody for Pause Control

```php
// Source: https://learn.microsoft.com/en-us/azure/ai-services/speech-service/speech-synthesis-markup-voice

// Pause insertion
$ssml = '<speak><break time="500ms"/>The revelation... <break time="2s"/>changed everything.</speak>';

// Rate/pitch modification for emotional delivery
$ssml = '<speak><prosody rate="-10%" pitch="+5%">I can\'t believe this is happening.</prosody></speak>';

// Amazon emotional tags (for Azure, use mstts:express-as)
$ssml = '<speak><amazon:emotion name="excited" intensity="medium">This is amazing!</amazon:emotion></speak>';
```

### OpenAI GPT-4o-mini-TTS Instructions (March 2025+)

```php
// Source: https://platform.openai.com/docs/guides/text-to-speech

// OpenAI uses instruction prompts, not inline tags
$instructions = 'Speak in a trembling voice, as if holding back tears. Pause briefly before emotional moments.';

$result = $openai->audio->speech->create([
    'model' => 'gpt-4o-mini-tts',
    'voice' => 'nova',
    'input' => 'I thought you were gone... forever.',
    'instructions' => $instructions,
]);
```

### Integrating with Existing SpeechSegment

```php
// Source: Codebase analysis - modules/AppVideoWizard/app/Services/SpeechSegment.php

// Current SpeechSegment already has emotion property
$segment = SpeechSegment::dialogue('ALICE', 'I thought you were gone forever.');
$segment->emotion = 'grief'; // Set from parenthetical parsing

// Enhancement: Expand emotion to full direction using VoiceDirectionVocabulary
$vocabulary = new VoiceDirectionVocabulary();
$direction = $vocabulary->getDirectionForEmotion($segment->emotion);
// Returns: ['tag' => '[voice cracks]', 'description' => 'emotional break...', ...]

// Build enhanced text with direction
$enhancedText = $vocabulary->wrapWithDirection(
    $segment->text,
    $segment->emotion,
    $targetProvider
);
// For ElevenLabs: '[crying] I thought you were gone [voice cracks] forever.'
// For OpenAI: text unchanged, instructions passed separately
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| SSML-only | Audio tags + natural language | ElevenLabs v3 (2025) | More expressive, less rigid syntax |
| Fixed voice parameters | Instruction-based steering | OpenAI GPT-4o-mini-TTS (March 2025) | Dynamic emotional control per generation |
| Emotion labels ("angry") | Physical manifestations | Phase 23 research | Better model comprehension |
| Single provider approach | Provider-agnostic vocabulary | Industry standard | Flexibility, future-proofing |

**Deprecated/outdated:**
- **FACS AU codes for TTS:** Never worked; TTS models don't understand Action Unit notation
- **Pre-v3 ElevenLabs SSML breaks:** Limited to 3 seconds, caused instability; v3 prefers punctuation/ellipses
- **Hard-coded emotional tag strings:** Provider APIs evolve; use abstraction layer

## Open Questions

Things that couldn't be fully resolved:

1. **ElevenLabs v3 API Availability**
   - What we know: v3 is in public alpha as of 2025, web interface only, API not publicly available yet
   - What's unclear: Timeline for API release, whether current API supports any v3 features
   - Recommendation: Design for v3 tags now; fall back to v2 SSML + stability slider for production

2. **Kokoro TTS Emotional Support**
   - What we know: Kokoro is a local/RunPod TTS, no documented audio tag system
   - What's unclear: Whether prompt text affects delivery emotionally
   - Recommendation: Use descriptive text style ("she whispered") rather than tags for Kokoro

3. **Multi-provider consistency**
   - What we know: Each provider responds differently to emotional direction
   - What's unclear: Exact mapping quality between providers
   - Recommendation: Test extensively; document voice capabilities per provider

## Sources

### Primary (HIGH confidence)

- ElevenLabs Documentation - Text-to-Speech Best Practices: https://elevenlabs.io/docs/overview/capabilities/text-to-speech/best-practices
- ElevenLabs Blog - v3 Audio Tags: https://elevenlabs.io/blog/eleven-v3-audio-tags-expressing-emotional-context-in-speech
- ElevenLabs Blog - Audio Tags Control: https://elevenlabs.io/blog/v3-audiotags
- OpenAI Documentation - Text to Speech: https://platform.openai.com/docs/guides/text-to-speech
- Microsoft Azure - SSML Voice Markup: https://learn.microsoft.com/en-us/azure/ai-services/speech-service/speech-synthesis-markup-voice
- Google Cloud - SSML Reference: https://docs.cloud.google.com/text-to-speech/docs/ssml

### Secondary (MEDIUM confidence)

- OpenAI Blog - Next-Gen Audio Models (March 2025): https://openai.com/index/introducing-our-next-generation-audio-models/
- Deepgram - TTS Prompting: https://developers.deepgram.com/docs/text-to-speech-prompting
- Resemble AI - TTS Best Practices: https://knowledge.resemble.ai/what-are-best-practices-for-text-to-speech

### Tertiary (LOW confidence - for validation)

- Voice123 - Script Format Tips: https://voice123.com/blog/voice-over-scripts/script-format/
- Academy Voices - Writing Voice Scripts: https://www.academyvoices.com/blog/how-to-write-a-voice-over-script
- SoCreate - Dialogue Direction in Screenplays: https://www.socreate.it/en/blogs/screenwriting/how-and-when-to-add-dialogue-direction-in-a-screenplay

### Codebase Analysis (HIGH confidence)

- `VoiceoverService.php` - Current TTS generation with OpenAI/Kokoro routing
- `VoiceRegistryService.php` - Voice assignment tracking (Phase 17)
- `SpeechSegment.php` - Segment model with emotion property
- `SpeechSegmentParser.php` - Dialogue parsing with parenthetical emotion extraction
- `CharacterPsychologyService.php` - Emotion-to-physical manifestation mapping
- `CinematographyVocabulary.php` - Vocabulary service pattern to follow
- `TransitionVocabulary.php` - Vocabulary service pattern to follow

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Based on existing codebase, no new dependencies
- Architecture: HIGH - Follows established Phase 22-24 vocabulary service pattern
- Provider tags: HIGH - Based on official ElevenLabs and OpenAI documentation
- Pitfalls: MEDIUM - Based on documentation warnings + community patterns

**Research date:** 2026-01-27
**Valid until:** 60 days (TTS APIs evolve, ElevenLabs v3 API release may change recommendations)
