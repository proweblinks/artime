---
phase: 04-dialogue-scene-excellence
plan: 03
subsystem: reaction-shots
tags: [reaction-shots, emotion-analysis, dialogue, strategic-placement, hollywood]

dependency-graph:
  requires:
    - 04-01 (Spatial continuity tracking)
    - 04-02 (OTS shot depth and framing)
  provides:
    - Listener emotion analysis based on dialogue content
    - Strategic reaction shot placement at dramatic beats
    - Detailed reaction shot builder with emotion data
    - Contextual reaction descriptions
  affects:
    - 04-04 (Coverage completeness validation)
    - Future dialogue scene enhancements

tech-stack:
  added: []
  patterns:
    - Dialogue content analysis for emotion detection
    - Narrative beat placement (midpoint, climax, rhythm)
    - Duration variation by emotional intensity
    - Spatial reversal for visual continuity

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php

decisions:
  - area: Listener emotion detection
    choice: Pattern-based analysis of dialogue keywords
    rationale: Fast, deterministic, covers common dramatic patterns
  - area: Reaction shot placement
    choice: Dramatic beats (midpoint, revelations, rhythm) not random
    rationale: Hollywood storytelling uses reaction shots purposefully
  - area: Duration scaling
    choice: 3.5s for high intensity, 2s for normal
    rationale: Big reactions need time to land; quick reactions maintain pace

metrics:
  duration: 12 minutes
  completed: 2026-01-23
---

# Phase 04 Plan 03: Intelligent Reaction Shot Generation Summary

**One-liner:** Reaction shots now analyze listener emotion from dialogue context and place shots at dramatic beats with specific emotions (shocked, moved, defensive, etc.).

## What Was Built

### Task 1: Listener Emotion Analysis
- Added `analyzeListenerEmotion()` method
- Analyzes dialogue content to determine listener's emotional response:
  - Questions -> contemplative (thoughtful expression, head tilt)
  - Accusations/blame -> defensive (tense posture, guarded expression)
  - Love declarations -> moved (softening expression, emotional eyes)
  - Bad news (death, divorce, etc.) -> shocked (widening eyes, stunned silence)
  - Good news (pregnant, promoted, etc.) -> overjoyed (breaking into smile)
  - Threats -> fearful (nervous swallow, fear in eyes)
  - Apologies -> considering (weighing response, guarded but listening)
  - Angry speaker with neutral listener -> wary (cautious expression)
- Returns emotion, intensity (0-1), visualCues array, and silentBeat flag

### Task 2: Strategic Reaction Shot Placement
- Added `shouldInsertReaction()` method
- Replaces random 30% placement with dramatic beat logic:
  - Always add for high-intensity moments (silentBeat = intensity >= 0.7)
  - At narrative midpoint (35-50% through) with intensity >= 0.6
  - After major revelations (intensity >= 0.75)
  - Every 3rd exchange for natural rhythm
  - After questions with intensity >= 0.5

### Task 3: Detailed Reaction Shot Builder
- Added `buildReactionShot()` method with complete shot data:
  - Duration varies by intensity (3.5s for >= 0.7, 2s otherwise)
  - Shot type based on intensity (close-up for >= 0.75, medium-close otherwise)
  - Visual description includes:
    - Character appearance
    - Emotion expression
    - Visual cues (e.g., "widening eyes, stunned silence")
    - Spatial positioning (reverse of speaker)
    - Contextual description (e.g., "processing devastating news")
    - "silent moment, cinematic lighting"
  - Spatial data reverses speaker position for visual continuity
  - reactionData block with complete emotion information

- Added `getReactionContext()` helper:
  - Death/loss -> "processing devastating news"
  - Love/marriage -> "absorbing heartfelt words"
  - Apology -> "considering the apology"
  - Question -> "formulating response"
  - Exclamation -> "reacting to emphatic statement"
  - Default -> "taking in the words"

### Task 4: Integration into Main Decomposition
- Added `getOtherCharacter()` helper method
- Updated main dialogue loop to:
  1. Get listener character (other than speaker)
  2. Call `analyzeListenerEmotion()` with dialogue and speaker emotion
  3. Call `shouldInsertReaction()` for strategic placement
  4. Call `buildReactionShot()` to create detailed reaction
  5. Log reaction shot details for debugging

## Technical Details

### Emotion Detection Patterns
```php
// Bad news triggers shocked emotion
if (preg_match('/\b(dead|died|cancer|leaving|divorce|fired|over)\b/', $text)) {
    $emotion = 'shocked';
    $intensity = 0.85;
    $visualCues = ['widening eyes', 'stunned silence'];
}
```

### Strategic Placement Logic
```
1. High intensity (silentBeat) -> Always add
2. Midpoint climax (35-50%) + moderate intensity -> Add
3. Major revelation (intensity >= 0.75) -> Add
4. Rhythm (every 3rd exchange) -> Add
5. Question with consideration -> Add
6. Otherwise -> Skip
```

### Reaction Shot Structure
```php
[
    'type' => 'close-up' | 'medium-close',
    'purpose' => 'reaction',
    'reactionCharacter' => 'listener name',
    'expression' => 'shocked' | 'moved' | etc.,
    'duration' => 2.0 | 3.5,
    'visualDescription' => 'detailed prompt with emotion...',
    'reactionData' => [
        'reactingTo' => 'dialogue text',
        'reactingToSpeaker' => 'speaker name',
        'emotion' => 'emotion type',
        'visualCues' => ['visual cue 1', 'visual cue 2'],
        'silentBeat' => true | false,
    ],
]
```

## Deviations from Plan

None - plan executed exactly as written.

## Files Changed

| File | Changes |
|------|---------|
| DialogueSceneDecomposerService.php | +analyzeListenerEmotion(), +shouldInsertReaction(), +buildReactionShot(), +getReactionContext(), +getOtherCharacter(), updated main loop integration |

## Success Criteria Verification

- [x] Listener emotion is analyzed based on dialogue content
- [x] Reaction shots placed at dramatic beats, not randomly
- [x] Reaction prompts include specific emotions (shocked, moved, defensive, etc.)
- [x] Reaction duration varies by intensity (3.5s for high, 2s for normal)
- [x] Reaction positioning is opposite of speaker
- [x] PHP syntax valid (methods integrate properly)

## Commits

| Hash | Message |
|------|---------|
| c344d4c | feat(04-03): add intelligent reaction shot generation |

## Next Phase Readiness

Intelligent reaction shots are complete. The system now generates:
- Context-aware listener emotions from dialogue analysis
- Strategically placed reactions at dramatic moments
- Detailed visual prompts with specific emotions and cues
- Proper spatial positioning for visual continuity

Ready for Plan 04-04: Coverage Completeness Validation.
