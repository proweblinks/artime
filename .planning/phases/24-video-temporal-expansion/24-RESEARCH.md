# Phase 24: Video Temporal Expansion - Research

**Researched:** 2026-01-27
**Domain:** AI video generation temporal prompting, camera movement choreography, multi-character dynamics, film editing vocabulary
**Confidence:** MEDIUM (AI video prompting is rapidly evolving; guidance based on Runway Gen-4/4.5, Veo 3, Kling research)

## Summary

This research investigates how to extend image prompts into video prompts with temporal structure, camera movement duration/psychology, multi-character dynamics, micro-movements, and transition vocabulary.

Key findings:

1. **Temporal beat syntax is supported but imprecise** - Runway Gen-4/4.5 and Veo 3.1 support timestamp-based prompts (e.g., `[00:00-00:02] action A, [00:02-00:04] action B`) but timing is approximate, not frame-accurate. Best results come from realistic action durations (3-5 seconds per significant action).

2. **Camera movement should include duration and psychological purpose** - AI video models respond well to "dolly in over 4 seconds creating intimacy" better than just "dolly in". The existing `CameraMovementService` already has `typical_duration_min/max` and `best_for_emotions` which can be leveraged.

3. **Multi-character dynamics require explicit spatial vocabulary** - Proxemics (intimate/personal/social/public distances) and power positioning (dominant=higher/centered, subordinate=lower/edge) must be explicitly described. AI models struggle with implicit relationships.

4. **Micro-movements/breathing are achievable with subtle prompts** - Phrases like "subtle breathing movement, natural eye motion" and "gentle micro-jitter, breathy sway" work for AI video models but should be paired with close-up shots.

5. **Transition vocabulary connects shots conceptually** - Match cuts, L-cuts, J-cuts, and whip pans are editing concepts. For video prompts, focus on "ending state" of current shot that motivates transition to next shot.

**Primary recommendation:** Create a `VideoTemporalService` that builds on existing `VideoPromptBuilderService` and `CameraMovementService`, adding temporal beat structure with `[TIMING:]` semantic markers, camera movement with duration/psychology, character path descriptions, and inter-character dynamics vocabulary.

## Standard Stack

### Core (Existing - Extend)
| Component | Location | Purpose | Extension Needed |
|-----------|----------|---------|------------------|
| VideoPromptBuilderService | `Services/VideoPromptBuilderService.php` | Video prompt assembly | Add temporal beat layer |
| CameraMovementService | `Services/CameraMovementService.php` | Movement selection | Add duration-in-prompt, psychology |
| CharacterPsychologyService | `Services/CharacterPsychologyService.php` | Physical manifestations | Extend to movement patterns |
| VwCameraMovement (seeder) | `database/seeders/VwCameraMovementSeeder.php` | Movement vocabulary | Already has `typical_duration_*` and `best_for_emotions` |

### New Services
| Service | Purpose | Confidence |
|---------|---------|------------|
| VideoTemporalService | Temporal beat structuring with timing markers | HIGH |
| CharacterDynamicsService | Multi-character spatial relationships and power dynamics | MEDIUM |
| MicroMovementService | Breathing, blinking, subtle motion vocabulary | MEDIUM |
| TransitionVocabulary | Shot ending states and transition suggestions | HIGH |

### No External Libraries Required
This phase extends existing PHP services with vocabulary constants and prompt-building logic.

## Architecture Patterns

### Recommended Extension Structure
```
modules/AppVideoWizard/app/Services/
├── VideoTemporalService.php        # NEW: Temporal beat structuring
├── CharacterDynamicsService.php    # NEW: Multi-character spatial dynamics
├── MicroMovementService.php        # NEW: Breathing, micro-expressions for video
├── TransitionVocabulary.php        # NEW: Shot transition vocabulary
├── VideoPromptBuilderService.php   # EXTEND: Integrate temporal layer
└── CameraMovementService.php       # EXTEND: Duration + psychology in prompt
```

### Pattern 1: Temporal Beat Structure
**What:** Structure motion prompts with time-based action sequences
**When to use:** Every video prompt for shots >5 seconds
**Example:**
```php
// Source: Runway Gen-4/4.5 timestamp prompting + Veo 3.1 temporal patterns
const TEMPORAL_BEAT_TEMPLATE = [
    'format' => '[{start_time}-{end_time}] {action}',
    'guidelines' => [
        'simple_action' => '2-3 seconds minimum',
        'complex_motion' => '4-5 seconds minimum',
        'emotional_beat' => '3-4 seconds for viewer processing',
        'camera_movement' => 'match action duration',
    ],
];

// Example output:
// [00:00-00:02] Medium shot, woman turns head left noticing something off-screen
// [00:02-00:04] Eyes widen in recognition, eyebrows rise slightly
// [00:04-00:06] Slow smile spreads across face, shoulders relax
// Camera slowly pushes in over 6 seconds creating growing intimacy
```

### Pattern 2: Camera Movement with Duration and Psychology
**What:** Extend camera movement prompts to include duration and emotional purpose
**When to use:** All camera movements except static
**Example:**
```php
// Source: Existing VwCameraMovementSeeder + cinematography research
public function buildTemporalMovementPrompt(
    string $movementSlug,
    int $durationSeconds,
    string $emotionalPurpose
): string {
    $movement = $this->getMovement($movementSlug);
    $basePrompt = $movement['prompt_syntax'];

    // Add duration
    $prompt = str_replace('smoothly', "over {$durationSeconds} seconds", $basePrompt);

    // Add psychological purpose from vocabulary
    $psychologyPhrases = [
        'intimacy' => 'closing distance as emotional connection deepens',
        'tension' => 'building suspense through deliberate approach',
        'reveal' => 'gradually exposing the full scope',
        'isolation' => 'emphasizing loneliness through widening space',
        'power' => 'asserting dominance through elevated perspective',
    ];

    if (isset($psychologyPhrases[$emotionalPurpose])) {
        $prompt .= ", {$psychologyPhrases[$emotionalPurpose]}";
    }

    return $prompt;
}

// Example: "camera dollies in over 4 seconds, closing distance as emotional connection deepens"
```

### Pattern 3: Character Movement Paths
**What:** Describe character motion trajectories within the frame
**When to use:** Medium and wide shots with subject movement
**Example:**
```php
// Source: AI video prompt research + film blocking vocabulary
const CHARACTER_PATH_VOCABULARY = [
    'approach' => [
        'toward_camera' => 'walks directly toward camera, growing larger in frame',
        'diagonal_entry' => 'enters frame from lower left, moving diagonally toward upper right',
        'lateral_cross' => 'crosses frame from left to right, maintaining consistent distance',
    ],
    'retreat' => [
        'away_from_camera' => 'backs away from camera, shrinking in frame',
        'exit_frame' => 'exits frame left, gaze lingering before departure',
    ],
    'stationary_motion' => [
        'turn' => 'turns 45 degrees to face [direction]',
        'rise' => 'rises from seated position to standing',
        'settle' => 'settles into chair, weight shifting back',
    ],
];

// Example output: "Character walks directly toward camera over 3 seconds, growing larger in frame,
// then stops at medium-close distance and turns 45 degrees to face left"
```

### Pattern 4: Proxemics and Power Dynamics (Multi-Character)
**What:** Map character relationships to spatial positioning
**When to use:** All two-shot and multi-character scenes
**Example:**
```php
// Source: Edward Hall's proxemics + film blocking research
const PROXEMIC_ZONES = [
    'intimate' => [
        'distance' => '0-18 inches',
        'prompt' => 'close enough to feel breath, faces nearly touching',
        'use_for' => ['love', 'comfort', 'confrontation', 'secrets'],
    ],
    'personal' => [
        'distance' => '18 inches - 4 feet',
        'prompt' => 'at arm\'s length distance, personal space shared',
        'use_for' => ['friends', 'close conversation', 'collaboration'],
    ],
    'social' => [
        'distance' => '4-12 feet',
        'prompt' => 'at conversational distance, professional spacing',
        'use_for' => ['business', 'formal', 'acquaintances'],
    ],
    'public' => [
        'distance' => '12+ feet',
        'prompt' => 'distant separation, vast space between them',
        'use_for' => ['strangers', 'formal address', 'isolation'],
    ],
];

const POWER_POSITIONING = [
    'dominant_over_subordinate' => [
        'dominant' => 'positioned higher in frame, chin raised, occupying more frame space',
        'subordinate' => 'positioned lower, eyeline directed upward, compressed into corner of frame',
    ],
    'equals' => [
        'description' => 'positioned at same height, equal frame space, facing each other directly',
    ],
    'conflict' => [
        'description' => 'bodies angled away but heads turned toward each other, physical barrier between them',
    ],
    'alliance' => [
        'description' => 'mirroring each other\'s posture, bodies angled same direction, shoulders aligned',
    ],
];
```

### Pattern 5: Micro-Movements and Breathing
**What:** Add subtle life motion to prevent "statue" effect in video
**When to use:** Close-up and medium-close shots, especially dialogue
**Example:**
```php
// Source: AI video generation research + animation best practices
const MICRO_MOVEMENT_LIBRARY = [
    'breathing' => [
        'subtle' => 'gentle chest rise and fall with natural breathing rhythm',
        'heavy' => 'deep visible breaths, shoulders rising and falling',
        'held' => 'breath held, chest still, tension visible in shoulders',
    ],
    'eyes' => [
        'natural' => 'natural blink pattern, eyes alive with micro-movements',
        'focused' => 'unblinking focused gaze, slight eye narrowing',
        'shifting' => 'eyes dart briefly to sides, returning to center',
    ],
    'head' => [
        'settle' => 'subtle head settling, micro-adjustments of position',
        'tilt' => 'slight head tilt indicating thought or curiosity',
        'nod' => 'almost imperceptible nod of acknowledgment',
    ],
    'hands' => [
        'fidget' => 'fingers tap lightly, hands shift position',
        'grip' => 'fingers tighten slightly on held object',
        'release' => 'hands gradually relax, fingers uncurl',
    ],
];

// Prompt addition for close-up:
// "subtle breathing movement with gentle chest rise, natural blink pattern,
// eyes alive with micro-movements between thoughts"
```

### Pattern 6: Transition Vocabulary
**What:** Ending states that motivate cuts and transitions to next shot
**When to use:** Every shot's ending, informing editorial flow
**Example:**
```php
// Source: Film editing vocabulary research
const TRANSITION_MOTIVATIONS = [
    'match_cut_setup' => [
        'shape' => 'ends on circular object/shape that can match to next shot',
        'motion' => 'ends mid-movement that continues in next shot',
        'color' => 'ends with dominant color that carries to next shot',
    ],
    'hard_cut_setup' => [
        'action_peak' => 'ends at peak of action for impactful cut',
        'look_off' => 'character looks off-frame, motivating cut to what they see',
        'reaction_held' => 'holds on reaction beat, allowing clean cut away',
    ],
    'soft_transition_setup' => [
        'settle' => 'action settles to stillness, allowing dissolve',
        'fade_worthy' => 'ends on contemplative beat, supporting fade transition',
    ],
];

const SHOT_ENDING_STATES = [
    'look_direction' => 'exits looking [left/right/up/down], motivating cut to subject of gaze',
    'mid_motion' => 'ends mid-gesture/movement, next shot completes action',
    'emotional_peak' => 'holds on emotional high point for 1-2 seconds before cut',
    'environmental_pan' => 'camera movement ends revealing new element, cut motivated',
];
```

### Anti-Patterns to Avoid
- **Frame-by-frame timing:** AI video models don't support frame-accurate timing. Use beat ranges (00:00-00:03), not frame numbers.
- **Too many actions per clip:** Runway recommends treating each 5-10 second generation as a single scene. Don't pack 5+ distinct actions.
- **Implicit spatial relationships:** "Two people talking" fails. Must describe "two people at arm's length, facing each other, positioned in left and right thirds."
- **Static micro-movements:** Don't apply breathing/blinking to wide shots where it's invisible. Match to shot type.
- **Transition effects in video prompts:** Transitions (dissolve, wipe) are editorial. Video prompts describe content, not post-production effects.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Camera movement selection | New movement logic | Extend `CameraMovementService.buildMovementPrompt()` | Already has movement vocabulary, stacking, emotion matching |
| Movement durations | Hard-coded values | Use `VwCameraMovement.typical_duration_*` | Already seeded with min/max durations per movement type |
| Emotion-to-movement mapping | New mapping table | Use `VwCameraMovement.best_for_emotions` | Already maps emotions to compatible movements |
| Shot type word budgets | New budget system | `PromptTemplateLibrary.SHOT_TEMPLATES` | Already has word_budget and priority_order per shot type |

**Key insight:** Phase 24 should extend the excellent foundation from VideoPromptBuilderService and CameraMovementService, not rebuild camera/movement logic.

## Common Pitfalls

### Pitfall 1: Over-Precise Temporal Timing
**What goes wrong:** Prompts specify "at exactly 2.5 seconds" and model doesn't hit the timing
**Why it happens:** AI video models use approximate timing, not frame-accurate keyframes
**How to avoid:** Use time ranges (00:00-00:03) with buffer; accept ~1 second variance
**Warning signs:** Actions feel rushed or incomplete; timing feels "off"

### Pitfall 2: Conflicting Actions in Short Clips
**What goes wrong:** 5-second clip requested to show "walks across room, sits down, picks up book, opens it"
**Why it happens:** Each meaningful action needs 2-3+ seconds
**How to avoid:** Rule of thumb: max 2 significant actions per 5-second clip, 3-4 for 10-second
**Warning signs:** Actions blur together; model prioritizes unpredictably

### Pitfall 3: Vague Multi-Character Positioning
**What goes wrong:** "Two people in conversation" yields unpredictable spatial arrangement
**Why it happens:** AI models need explicit spatial vocabulary, can't infer relationship
**How to avoid:** Always specify proxemic zone AND frame positioning ("at personal distance, positioned in left and right thirds of frame")
**Warning signs:** Characters too close/far; power dynamics don't match narrative intent

### Pitfall 4: Invisible Micro-Movements
**What goes wrong:** Wide shot includes "subtle breathing, micro-expressions" that aren't visible
**Why it happens:** Applying close-up vocabulary to wide shots
**How to avoid:** Match micro-movement detail level to shot type; wide shots = body language, close-ups = breathing/blink
**Warning signs:** Wasted token budget on invisible details; model attempts visible breathing in wide shot

### Pitfall 5: Camera Psychology Without Duration
**What goes wrong:** "dolly in creating intimacy" but 10-second shot has 2-second dolly followed by 8 seconds static
**Why it happens:** Duration not specified; model decides
**How to avoid:** Always include duration: "dolly in over 6 seconds creating intimacy"
**Warning signs:** Movement too fast/slow for emotional intent; awkward static hold

### Pitfall 6: Transition Confusion
**What goes wrong:** Video prompt includes "dissolve to next scene"
**Why it happens:** Mixing editorial transitions with shot content
**How to avoid:** Video prompts describe shot content. Transitions are metadata for the editor, not AI prompt content.
**Warning signs:** Model attempts to render dissolve effect within shot

## Code Examples

Verified patterns from existing codebase and research:

### Existing Camera Movement with Duration Data (Source: VwCameraMovementSeeder)
```php
// Source: modules/AppVideoWizard/database/seeders/VwCameraMovementSeeder.php
[
    'slug' => 'dolly-in',
    'name' => 'Dolly In',
    'description' => 'Camera physically moves toward subject on dolly/track. Creates intimacy.',
    'prompt_syntax' => 'camera smoothly dollies in toward the subject',
    'intensity' => 'moderate',
    'typical_duration_min' => 3,  // Already has duration guidance
    'typical_duration_max' => 10,
    'best_for_emotions' => ['intimacy', 'tension', 'focus', 'connection'], // Already has psychology
    'natural_continuation' => 'dolly-out',
    'ending_state' => 'closer to subject with depth',
]
```

### Existing Motion Intensity Modifiers (Source: VideoPromptBuilderService)
```php
// Source: modules/AppVideoWizard/app/Services/VideoPromptBuilderService.php
public const MOTION_INTENSITY = [
    'subtle' => ['slow', 'gentle', 'smooth', 'gradual', 'soft', 'imperceptible', 'whisper'],
    'moderate' => ['steady', 'controlled', 'balanced', 'fluid', 'natural', 'measured', 'deliberate'],
    'dynamic' => ['energetic', 'active', 'lively', 'engaging', 'swift', 'purposeful', 'driven'],
    'intense' => ['dramatic', 'powerful', 'rapid', 'impactful', 'bold', 'explosive', 'visceral'],
];
```

### Existing Body Language for Emotion (Source: VideoPromptBuilderService)
```php
// Source: modules/AppVideoWizard/app/Services/VideoPromptBuilderService.php
protected function getBodyLanguageForEmotion(string $emotion): string
{
    $bodyLanguage = [
        'happy' => 'open posture, relaxed shoulders',
        'sad' => 'shoulders slightly hunched, gaze downward',
        'angry' => 'tense muscles, squared shoulders',
        'fearful' => 'body slightly contracted, alert stance',
        'surprised' => 'body pulling back slightly, widened stance',
        'tense' => 'coiled tension in every muscle',
        'romantic' => 'body angled toward the other, soft openness',
        'mysterious' => 'guarded posture, controlled movements',
        'determined' => 'forward lean, locked jaw',
        'contemplative' => 'stillness with subtle weight shifts',
    ];
    return $bodyLanguage[$emotion] ?? '';
}
```

### Proposed Temporal Beat Builder
```php
// NEW: VideoTemporalService.php
/**
 * Build temporal beat structure for video prompt.
 *
 * @param array $beats Array of ['action' => string, 'duration' => int]
 * @param int $totalDuration Total video duration in seconds
 * @return string Formatted temporal prompt section
 */
public function buildTemporalBeats(array $beats, int $totalDuration = 10): string
{
    $currentTime = 0;
    $formatted = [];

    foreach ($beats as $beat) {
        $endTime = min($currentTime + $beat['duration'], $totalDuration);
        $startFormatted = sprintf('%02d:%02d', floor($currentTime / 60), $currentTime % 60);
        $endFormatted = sprintf('%02d:%02d', floor($endTime / 60), $endTime % 60);

        $formatted[] = "[{$startFormatted}-{$endFormatted}] {$beat['action']}";
        $currentTime = $endTime;

        if ($currentTime >= $totalDuration) break;
    }

    return implode('. ', $formatted);
}

// Example usage:
$beats = [
    ['action' => 'Character turns head left noticing something off-screen', 'duration' => 2],
    ['action' => 'Eyes widen in recognition, eyebrows rise slightly', 'duration' => 2],
    ['action' => 'Slow smile spreads across face, shoulders relax', 'duration' => 3],
];
// Output: "[00:00-00:02] Character turns head left... [00:02-00:04] Eyes widen... [00:04-00:07] Slow smile..."
```

### Proposed Multi-Character Dynamics Builder
```php
// NEW: CharacterDynamicsService.php
/**
 * Build spatial relationship description for multi-character shots.
 */
public function buildSpatialDynamics(
    string $relationship, // 'dominant', 'equals', 'conflict', 'alliance'
    string $proximityZone, // 'intimate', 'personal', 'social', 'public'
    array $characters
): string {
    $proximity = self::PROXEMIC_ZONES[$proximityZone];
    $power = self::POWER_POSITIONING[$relationship] ?? self::POWER_POSITIONING['equals'];

    $description = [];

    // Add proximity
    $description[] = "Characters positioned {$proximity['prompt']}";

    // Add power dynamics
    if ($relationship === 'dominant_over_subordinate') {
        $description[] = "{$characters[0]} {$power['dominant']}";
        $description[] = "{$characters[1]} {$power['subordinate']}";
    } else {
        $description[] = $power['description'];
    }

    return implode('. ', $description);
}

// Example output: "Characters positioned at arm's length distance, personal space shared.
// Marcus positioned higher in frame, chin raised, occupying more frame space.
// Alex positioned lower, eyeline directed upward, compressed into corner of frame."
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Single action per clip | Timestamped multi-beat prompts | Runway Gen-4 (2025) | Can sequence 2-3 actions with timing |
| Camera movement only | Movement + duration + psychology | Veo 3/Gen-4.5 (2025-2026) | More intentional, emotion-driven motion |
| Implicit character relationships | Explicit proxemics + power positioning | AI video best practices (2025) | Reliable spatial dynamics |
| Static subjects | Micro-movement layers | Gen-4.5 (2026) | More lifelike, less "AI generated" feel |

**Deprecated/outdated:**
- Frame-accurate keyframe timing (AI models don't support this)
- Single-action-only clips (timestamp prompting now available)
- Relying on model to infer character relationships (must be explicit)

## Open Questions

1. **Timestamp format standardization**
   - What we know: Runway uses `[00:00-00:02]`, Veo uses `[start_time-end_time]`
   - What's unclear: Whether format matters or natural language suffices
   - Recommendation: Support both timestamp format AND natural language ("first 2 seconds... then...")

2. **Multi-character limit**
   - What we know: 2 characters work well, 3+ gets complex
   - What's unclear: Reliable patterns for 3+ character scenes
   - Recommendation: Default to 2-character max for automated prompts, warn for 3+

3. **Micro-movement intensity per model**
   - What we know: Different models (Runway, Kling, Pika) handle micro-movements differently
   - What's unclear: Optimal phrasing per model
   - Recommendation: Start with generic phrasing, add model-specific tuning if needed

4. **Duration precision variance**
   - What we know: AI models are approximate, not frame-accurate
   - What's unclear: Typical variance (is it +/- 0.5s or +/- 2s?)
   - Recommendation: Build in 1-second buffer per action; accept variance

## Sources

### Primary (HIGH confidence)
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\VideoPromptBuilderService.php - Existing video prompt patterns
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\CameraMovementService.php - Camera movement vocabulary
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\database\seeders\VwCameraMovementSeeder.php - Movement durations, emotions
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\CharacterPsychologyService.php - Physical manifestations
- [Runway Text to Video Prompting Guide](https://help.runwayml.com/hc/en-us/articles/42460036199443-Text-to-Video-Prompting-Guide) - Timestamp prompting
- [Runway Gen-4 Video Prompting Guide](https://help.runwayml.com/hc/en-us/articles/39789879462419-Gen-4-Video-Prompting-Guide) - Multi-action sequences
- [Veo 3.1 Prompt Guide](https://docs.cloud.google.com/vertex-ai/generative-ai/docs/video/video-gen-prompt-guide) - Temporal patterns

### Secondary (MEDIUM confidence)
- [Film Blocking Techniques](https://saturation.io/blog/filmmaking-techniques-film-blocking) - Spatial power dynamics
- [Proxemics in Film](https://www.filmmakersacademy.com/glossary/proxemic-patterns/) - Interpersonal distance zones
- [Camera Movement Psychology](https://wolfcrow.com/how-filmmakers-manipulate-our-emotions-using-camera-angles-and-movement/) - Dolly/push-in emotional effects
- [Film Transitions Guide](https://www.studiobinder.com/blog/types-of-editing-transitions-in-film/) - Match cuts, L-cuts, J-cuts
- [Runway Gen-4 Prompts Guide](https://filmart.ai/runway-gen-4-prompts/) - Camera movement syntax
- [26 Veo 3.1 Prompt Patterns](https://skywork.ai/blog/veo-3-1-prompt-patterns-shot-lists-camera-moves-lighting-cues/) - Timestamp format examples

### Tertiary (LOW confidence)
- [Kling AI Review](https://cybernews.com/ai-tools/kling-ai-review/) - Multi-character limitations
- [Neural Frames Animation](https://www.neuralframes.com/) - Micro-movement techniques

## Metadata

**Confidence breakdown:**
- Temporal beat structure: MEDIUM - Format supported but variance exists
- Camera duration/psychology: HIGH - Built on existing codebase patterns
- Multi-character dynamics: MEDIUM - Clear vocabulary but model-dependent results
- Micro-movements: MEDIUM - Works for close-ups, needs shot-type matching
- Transition vocabulary: HIGH - Editorial concepts map clearly to ending states

**Research date:** 2026-01-27
**Valid until:** 30 days (AI video generation is rapidly evolving; verify against latest model releases)
