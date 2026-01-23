# Scene Text Inspector - Implementation Plan

> **Created**: 2026-01-23
> **Status**: PLANNING
> **Priority**: HIGH

---

## Problem Statement

### Current Issues

1. **Wrong Label Display**: The storyboard shows "Dialogue" label for ALL text, even when it's narrator text
2. **Missing Type Indicators**: Speech segment types (NARRATOR, DIALOGUE, INTERNAL, MONOLOGUE) are not displayed
3. **Truncated Content**: Text is limited to 80 chars and only 2 segments shown - no full visibility
4. **No Transparency Modal**: Users cannot see all prompts, all text, all details for a scene
5. **Manual Speech Type**: Speech type should be auto-detected from story structure, not manually set

### User Requirements

- **Full Transparency**: See ALL text content for each scene (not truncated)
- **Correct Labels**: Each segment must show its TRUE type (NARRATOR vs DIALOGUE vs INTERNAL vs MONOLOGUE)
- **Speaker Names**: Show which character is speaking for each segment
- **All Prompts Visible**: Image prompts, video prompts, shot details
- **Auto-Detection**: Speech types determined from Character Intelligence + story structure

---

## Architecture Design

### Phase 1: Fix Speech Segment Type Detection (Backend)

**Goal**: Ensure each speech segment has the correct `type` field based on content analysis.

#### 1.1 Enhance SpeechSegmentParser

Location: `modules/AppVideoWizard/app/Services/SpeechSegmentParser.php`

Current behavior:
- Parser looks for `[NARRATOR]`, `[INTERNAL: CHAR]`, `[MONOLOGUE: CHAR]`, `CHARACTER:` patterns
- If no pattern found, defaults to narrator

Problem:
- AI-generated scripts may not use these exact markers
- Plain descriptive text is being treated incorrectly

Fix:
```php
// Add intelligent detection when no markers present
public function detectSegmentType(string $text, array $context = []): string
{
    // Check for explicit markers first (existing logic)

    // If no markers, use content analysis:
    // 1. Check if text is scene-setting (locations, atmosphere, actions)
    // 2. Check if text contains quoted speech
    // 3. Check if text mentions character by name doing something
    // 4. Use Character Intelligence to identify known speakers

    // Patterns for NARRATOR:
    // - Third-person descriptions ("He walks", "The city glows")
    // - Scene-setting ("In a rain-slicked megacity...")
    // - Action descriptions ("Marc dodges", "Bones crack")

    // Patterns for DIALOGUE:
    // - Quoted text with speaker attribution
    // - "CHARACTER said/says/yells"
    // - Direct speech patterns

    // Patterns for INTERNAL:
    // - "CHARACTER thought/thinks/wonders"
    // - Italic or parenthetical thoughts
    // - First-person in third-person narrative
}
```

#### 1.2 Integrate Character Intelligence

Location: `modules/AppVideoWizard/app/Livewire/VideoWizard.php`

After script generation, use Character Intelligence to:
1. Extract all character names from the story
2. When parsing speech segments, check if a name matches a known character
3. Auto-assign speaker and determine if dialogue/monologue/internal

```php
public function enrichSpeechSegmentsWithCharacterIntelligence(array $segments, array $characters): array
{
    foreach ($segments as &$segment) {
        // Match speaker to Character Bible
        if (!empty($segment['speaker'])) {
            $matched = $this->findCharacterByName($segment['speaker'], $characters);
            if ($matched) {
                $segment['characterId'] = $matched['id'];
                $segment['voiceId'] = $matched['voiceId'] ?? null;
            }
        }

        // If no speaker but text mentions character action, it's narrator
        // If text has quoted speech, extract and create dialogue segment
    }
    return $segments;
}
```

### Phase 2: Scene Text Inspector Modal (Frontend)

**Goal**: Create a comprehensive modal showing ALL scene text and prompts.

#### 2.1 Create Modal Component

Location: `modules/AppVideoWizard/resources/views/livewire/modals/scene-text-inspector.blade.php`

```
┌─────────────────────────────────────────────────────────────────────────┐
│ 🔍 SCENE TEXT INSPECTOR - Scene 1: "Neon City Pursuit"          [✕]    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ ═══════════════════════════════════════════════════════════════════════ │
│ 📜 SPEECH SEGMENTS (3 total)                                            │
│ ═══════════════════════════════════════════════════════════════════════ │
│                                                                         │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │ 🎙️ NARRATOR                                              Segment 1 │ │
│ │ ─────────────────────────────────────────────────────────────────── │ │
│ │ In a rain-slicked megacity, Kai Voss dodges corporate drones,      │ │
│ │ his cybernetic eye scanning for threats as neon signs reflect      │ │
│ │ off the wet pavement. The air crackles with tension.               │ │
│ │                                                                     │ │
│ │ 🔊 Lip-Sync: NO (Voiceover only)                                   │ │
│ │ ⏱️ Est. Duration: ~8 seconds                                       │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │ 💬 DIALOGUE                                              Segment 2 │ │
│ │ ─────────────────────────────────────────────────────────────────── │ │
│ │ 👤 Speaker: MARC SPECTOR                                           │ │
│ │ 🎭 Character: Marc Spector (matched from Character Bible)          │ │
│ │                                                                     │ │
│ │ "They found me. I need to move."                                   │ │
│ │                                                                     │ │
│ │ 🔊 Lip-Sync: YES (requires Multitalk)                              │ │
│ │ ⏱️ Est. Duration: ~2 seconds                                       │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │ 💭 INTERNAL THOUGHT                                      Segment 3 │ │
│ │ ─────────────────────────────────────────────────────────────────── │ │
│ │ 👤 Speaker: STEVEN GRANT                                           │ │
│ │ 🎭 Character: Steven Grant (matched from Character Bible)          │ │
│ │                                                                     │ │
│ │ "We shouldn't be here, Marc. This is wrong. We should never        │ │
│ │ have come to this place."                                          │ │
│ │                                                                     │ │
│ │ 🔊 Lip-Sync: NO (Internal V.O. - lips don't move)                  │ │
│ │ ⏱️ Est. Duration: ~4 seconds                                       │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│ ═══════════════════════════════════════════════════════════════════════ │
│ 🎬 GENERATION PROMPTS                                                   │
│ ═══════════════════════════════════════════════════════════════════════ │
│                                                                         │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │ 🖼️ IMAGE PROMPT                                                     │ │
│ │ ─────────────────────────────────────────────────────────────────── │ │
│ │ CLOSE-UP: Character's FACE fills the frame. Head and shoulders     │ │
│ │ only. Eyes at top third of frame (rule of thirds). Background      │ │
│ │ completely blurred. 100mm lens perspective. Emotional detail       │ │
│ │ visible - subtle facial expressions.                               │ │
│ │                                                                     │ │
│ │ CINEMATIC FILM STILL - freeze-frame from a movie in progress,      │ │
│ │ NOT a posed photograph. INDOOR/INTERIOR scene. LOCATION:           │ │
│ │ Abandoned Warehouse. STORY MOMENT: Marc crouches in shadows,       │ │
│ │ scanning for enemies...                                            │ │
│ │                                                    [Copy Prompt 📋] │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │ 🎥 VIDEO PROMPT                                                     │ │
│ │ ─────────────────────────────────────────────────────────────────── │ │
│ │ Camera slowly pushes in on Marc's face as his eyes dart left       │ │
│ │ and right, scanning. Subtle head turn. Tension in jaw muscles.     │ │
│ │ Breathing visible. Cinematic slow push-in, 2 seconds.              │ │
│ │                                                    [Copy Prompt 📋] │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│ ═══════════════════════════════════════════════════════════════════════ │
│ 📊 SCENE METADATA                                                       │
│ ═══════════════════════════════════════════════════════════════════════ │
│                                                                         │
│ Duration: 15 seconds          Shot Type: close-up                      │
│ Transition: cut               Camera: slow push-in                      │
│ Mood: tense                   Location: Abandoned Warehouse             │
│ Characters: Marc Spector, Steven Grant (internal)                       │
│                                                                         │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │                    [Close] [Edit Scene] [Regenerate]                │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

#### 2.2 Add Button to Scene Cards

Location: `modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php`

Add a "🔍 Inspect" button to each scene card that opens the modal:

```blade
<button wire:click="openSceneTextInspector({{ $index }})"
        class="vw-inspect-btn"
        title="View all scene text and prompts">
    🔍 Inspect
</button>
```

#### 2.3 Fix Current Display

Update the existing dialogue display section to:
1. Show correct type label based on segment types present
2. Use type-specific icons and colors
3. Keep truncated preview but indicate "click to see all"

```blade
@php
    $hasNarrator = collect($speechSegments)->contains('type', 'narrator');
    $hasDialogue = collect($speechSegments)->contains('type', 'dialogue');
    $hasInternal = collect($speechSegments)->contains('type', 'internal');
    $hasMonologue = collect($speechSegments)->contains('type', 'monologue');

    // Dynamic label based on what's present
    $label = $hasDialogue ? 'Dialogue' : ($hasMonologue ? 'Monologue' : ($hasInternal ? 'Internal' : 'Narration'));
@endphp
```

### Phase 3: Auto-Detection Integration (Smart Flow)

**Goal**: Automatically set correct speech types during script generation.

#### 3.1 Enhance Script Generation Prompt

Location: `modules/AppVideoWizard/app/Services/ScriptGenerationService.php`

Update the AI prompt to REQUIRE explicit segment markers:

```
CRITICAL: Every scene MUST have speech segments with explicit type markers:

[NARRATOR] Use for scene-setting, descriptions, action narration
[DIALOGUE: CHARACTER_NAME] Use for spoken words (lips move)
[INTERNAL: CHARACTER_NAME] Use for thoughts/inner voice (lips don't move)
[MONOLOGUE: CHARACTER_NAME] Use for character speaking to camera/alone

Example scene narration:
[NARRATOR] The warehouse looms in darkness. Marc presses against the cold steel wall.
[INTERNAL: STEVEN] We shouldn't be here. This feels wrong.
[DIALOGUE: MARC] "Shut up, Steven. I need to focus."
[NARRATOR] Footsteps echo from the shadows. The cult has found him.
[DIALOGUE: HARROW] "Mr. Spector. We've been expecting you."
```

#### 3.2 Post-Generation Validation

After script generation, validate and fix segments:

```php
public function validateAndFixSpeechSegments(array $scene): array
{
    $parser = new SpeechSegmentParser();
    $segments = $scene['speechSegments'] ?? [];

    // If no segments, parse from narration text
    if (empty($segments) && !empty($scene['narration'])) {
        $segments = $parser->parse($scene['narration'], $this->characterBible);
    }

    // Validate each segment
    foreach ($segments as &$segment) {
        // Ensure type is set
        if (empty($segment['type'])) {
            $segment['type'] = $parser->detectSpeechType($segment['text']);
        }

        // Ensure needsLipSync is correct
        $segment['needsLipSync'] = in_array($segment['type'], ['dialogue', 'monologue']);

        // Match speaker to Character Bible
        if (!empty($segment['speaker'])) {
            $this->enrichSegmentWithCharacter($segment);
        }
    }

    $scene['speechSegments'] = $segments;
    return $scene;
}
```

#### 3.3 Character Intelligence Integration

After Character Intelligence extracts characters:
1. Store character names and roles
2. Use this data when parsing speech segments
3. Auto-match speakers to characters
4. Assign voiceId from Character Bible

---

## Implementation Tasks

### Phase 1: Backend Fixes (Priority: CRITICAL)

| Task | File | Description |
|------|------|-------------|
| 1.1 | `SpeechSegmentParser.php` | Add `detectSegmentTypeFromContent()` method |
| 1.2 | `SpeechSegmentParser.php` | Add patterns for narrator vs dialogue detection |
| 1.3 | `ScriptGenerationService.php` | Update AI prompt to require explicit markers |
| 1.4 | `ScriptGenerationService.php` | Add `validateAndFixSpeechSegments()` method |
| 1.5 | `VideoWizard.php` | Add `enrichSpeechSegmentsWithCharacterIntelligence()` |
| 1.6 | `VideoWizard.php` | Call validation after script generation |

### Phase 2: Frontend - Scene Card Fix (Priority: HIGH)

| Task | File | Description |
|------|------|-------------|
| 2.1 | `storyboard.blade.php` | Fix "Dialogue" label to be dynamic |
| 2.2 | `storyboard.blade.php` | Add type-specific icons (🎙️💬💭🗣️) |
| 2.3 | `storyboard.blade.php` | Add "🔍 Inspect" button to scene cards |
| 2.4 | `storyboard.blade.php` | Add CSS for type-specific colors |

### Phase 3: Scene Text Inspector Modal (Priority: HIGH)

| Task | File | Description |
|------|------|-------------|
| 3.1 | `modals/scene-text-inspector.blade.php` | Create new modal component |
| 3.2 | `VideoWizard.php` | Add `$inspectingSceneIndex` property |
| 3.3 | `VideoWizard.php` | Add `openSceneTextInspector($index)` method |
| 3.4 | `VideoWizard.php` | Add `closeSceneTextInspector()` method |
| 3.5 | `VideoWizard.php` | Add `getSceneInspectorData($index)` method |
| 3.6 | `video-wizard.blade.php` | Include the modal component |

### Phase 4: Polish & Testing (Priority: MEDIUM)

| Task | File | Description |
|------|------|-------------|
| 4.1 | Multiple | Test with Moon Knight script |
| 4.2 | Multiple | Verify all segment types display correctly |
| 4.3 | Multiple | Verify Character Bible matching works |
| 4.4 | Multiple | Test modal on mobile/responsive |

---

## File Modifications Summary

| File | Changes |
|------|---------|
| `SpeechSegmentParser.php` | Add intelligent type detection |
| `ScriptGenerationService.php` | Enhanced prompts + validation |
| `VideoWizard.php` | New methods + properties for inspector |
| `storyboard.blade.php` | Fix labels + add inspect button |
| `scene-text-inspector.blade.php` | NEW FILE - modal component |
| `video-wizard.blade.php` | Include modal |

---

## Success Criteria

1. **Correct Labels**: Narrator text shows "Narrator" label, not "Dialogue"
2. **Type Icons**: Each segment shows appropriate icon (🎙️💬💭🗣️)
3. **Full Text**: Inspector modal shows complete text, not truncated
4. **All Prompts**: Image prompt, video prompt visible in modal
5. **Character Match**: Speakers matched to Character Bible with voiceId
6. **Lip-Sync Accuracy**: needsLipSync only true for dialogue/monologue
7. **Auto-Detection**: New scripts use proper markers automatically

---

## Estimated Effort

| Phase | Tasks | Complexity |
|-------|-------|------------|
| Phase 1 | 6 tasks | Medium |
| Phase 2 | 4 tasks | Low |
| Phase 3 | 6 tasks | Medium |
| Phase 4 | 4 tasks | Low |

---

## Next Steps

1. **Approve this plan**
2. Start with Phase 1.1 - Fix SpeechSegmentParser detection
3. Continue through phases in order
4. Test with Moon Knight script at each phase

---

*Plan created: 2026-01-23*
*Status: Awaiting approval*
