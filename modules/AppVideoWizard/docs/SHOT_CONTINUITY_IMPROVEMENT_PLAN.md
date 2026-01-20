# Shot Continuity & Video Generation Improvement Plan

## Executive Summary

This plan addresses the fundamental issues identified in our multi-shot video generation process:
- Redundant/similar shots breaking visual flow
- Loss of character consistency between shots
- Poor frame-to-frame continuity
- Unprofessional video prompts lacking cinematographic precision
- Missing story arc and narrative purpose per shot

---

## Research Findings Summary

### Key Industry Techniques (2025-2026)

| Technique | Description | Source |
|-----------|-------------|--------|
| **First-Last Frame (FLF)** | Use last frame of Shot N as first frame reference for Shot N+1 | [Wan-2.1 FLF2V](https://fal.ai/models/fal-ai/wan-flf2v), [Artlist](https://artlist.io/blog/ai-video-transitions-study-case/) |
| **Shot Tagging** | Use `<SHOT 1>`, `<SHOT 2>` markers for AI consistency | [Seedance 1.0](https://superduperai.co/en/blog/seedance-ai) |
| **StoryMem** | Memory mechanism maintaining character consistency across shots | [StoryMem Paper](https://arxiv.org/html/2512.19539v1) |
| **Professional Prompt Structure** | `[Camera] + [Pace] + [Action] + [Atmosphere]` | [Venice AI Guide](https://venice.ai/blog/the-complete-guide-to-ai-video-prompt-engineering) |
| **Continuity Columns** | Track: identity, wardrobe, props, palette, lighting, camera | [Veo 3.1 Best Practices](https://skywork.ai/blog/multi-prompt-multi-shot-consistency-veo-3-1-best-practices/) |

### Professional Camera Terminology
Use precise terms: "medium tracking shot", "dolly in", "push in slowly", "orbit clockwise", "tilt up", "pedestal down" - NOT vague descriptions like "camera moves" or "zoom in".

---

## Problems Identified in Current System

### 1. Shot Decomposition Issues
- **Redundant shots**: Shots 2 & 3 were nearly identical (same character, angle, action)
- **Random character switching**: Shot 4 introduced a different person without context
- **No variety validation**: System doesn't check if consecutive shots are too similar
- **Missing narrative purpose**: Each shot should advance the story

### 2. Video Prompt Quality Issues
- **Vague descriptions**: Current prompts lack cinematographic precision
- **No camera movement specs**: Missing specific camera direction terminology
- **No continuity anchors**: Prompts don't reference previous shot's ending state
- **Missing atmosphere/palette**: Color, lighting, mood not consistently specified

### 3. Frame Continuity Issues
- **No frame chaining**: Last frame of Shot N not used for Shot N+1
- **Character drift**: Face/clothing changes between shots
- **Camera direction breaks**: Inconsistent motion direction across cuts
- **Lighting jumps**: Different time-of-day feel between adjacent shots

### 4. Story Arc Issues
- **No emotional progression**: Character's emotional state doesn't evolve
- **No clear goal**: What is the character trying to achieve?
- **Static action**: Character just "walks" without purpose or destination

---

## Implementation Plan

### Phase 1: Shot Decomposition Enhancement

#### 1.1 Shot Purpose Validation
Each shot MUST have ONE of these purposes:
- **Establishing**: Set location/context (only ONE per scene)
- **Introduction**: Introduce character/element
- **Action**: Show character doing something
- **Reaction**: Show response to event
- **Transition**: Bridge between story beats
- **Climax**: Peak emotional/action moment
- **Resolution**: Conclude the beat

**Implementation:**
```php
// In decomposeSceneIntoShots() - add validation
private function validateShotPurposes(array $shots): array
{
    $purposes = array_column($shots, 'purpose');

    // Check for redundant purposes
    $establishingCount = count(array_filter($purposes, fn($p) => $p === 'establishing'));
    if ($establishingCount > 1) {
        // Remove extra establishing shots
    }

    // Check for consecutive same-purpose shots
    for ($i = 1; $i < count($shots); $i++) {
        if ($shots[$i]['purpose'] === $shots[$i-1]['purpose'] &&
            $shots[$i]['purpose'] !== 'action') {
            // Flag for review or merge
        }
    }

    return $shots;
}
```

#### 1.2 Shot Similarity Detection
Before finalizing decomposition, compare adjacent shots:

```php
private function detectSimilarShots(array $shots): array
{
    $issues = [];
    for ($i = 1; $i < count($shots); $i++) {
        $similarity = $this->calculateShotSimilarity($shots[$i-1], $shots[$i]);
        if ($similarity > 0.7) { // 70% similar = too redundant
            $issues[] = [
                'shots' => [$i-1, $i],
                'similarity' => $similarity,
                'recommendation' => 'merge_or_differentiate'
            ];
        }
    }
    return $issues;
}

private function calculateShotSimilarity(array $shotA, array $shotB): float
{
    $score = 0;
    $factors = 0;

    // Same shot type (wide, medium, close-up)
    if ($shotA['shotType'] === $shotB['shotType']) $score += 0.3;
    $factors++;

    // Same camera movement
    if ($shotA['cameraMovement'] === $shotB['cameraMovement']) $score += 0.2;
    $factors++;

    // Same subject focus
    if ($shotA['subjectFocus'] === $shotB['subjectFocus']) $score += 0.3;
    $factors++;

    // Similar action
    if ($this->actionsAreSimilar($shotA['action'], $shotB['action'])) $score += 0.2;
    $factors++;

    return $score;
}
```

#### 1.3 Shot Progression Logic
Enforce cinematic shot progression rules:

```
VALID PROGRESSIONS:
- Establishing → Wide → Medium → Close-up (classic tightening)
- Wide → Close-up → Wide (emphasis cut)
- Character A Close-up → Character B Close-up (dialogue/reaction)
- Action → Reaction (cause and effect)

INVALID PROGRESSIONS (flag these):
- Wide → Wide (redundant unless different angle)
- Close-up → Close-up of SAME subject (redundant)
- Establishing → Establishing (never have two)
```

---

### Phase 2: Video Prompt Engineering

#### 2.1 Professional Prompt Template

**Current (BAD):**
```
"The group emerges into the expansive scene, their collective presence establishing the scale."
```

**Improved (GOOD):**
```
"<SHOT 2> Medium tracking shot, steady pace, camera follows subject from slight left angle.
A rugged man in dark tactical jacket walks purposefully through crowded Cairo street.
Pyramids visible in distant background. Golden hour lighting, warm amber palette,
dust particles in air. Subject moves screen-left to screen-right.
End frame: subject reaches market stall, pauses.
Continuity: matches previous shot's sunset lighting and warm color grade."
```

#### 2.2 Prompt Structure Components

| Component | Example | Purpose |
|-----------|---------|---------|
| **Shot marker** | `<SHOT 2>` | AI sequence awareness |
| **Camera spec** | `Medium tracking shot` | Precise framing |
| **Pace** | `steady pace` | Motion speed |
| **Camera angle** | `slight left angle` | Perspective |
| **Subject description** | `rugged man in dark tactical jacket` | Consistency anchor |
| **Action** | `walks purposefully` | Not just "walks" |
| **Environment** | `crowded Cairo street, pyramids distant` | Location context |
| **Lighting** | `Golden hour, warm amber palette` | Mood/time |
| **Motion direction** | `screen-left to screen-right` | Continuity |
| **End frame description** | `reaches market stall, pauses` | FLF setup |
| **Continuity anchor** | `matches previous shot's sunset lighting` | Chain link |

#### 2.3 Camera Movement Vocabulary

```php
const CAMERA_MOVEMENTS = [
    // Horizontal movement
    'pan_left' => 'Camera pans smoothly left',
    'pan_right' => 'Camera pans smoothly right',
    'truck_left' => 'Camera trucks left (parallel movement)',
    'truck_right' => 'Camera trucks right (parallel movement)',

    // Depth movement
    'push_in' => 'Camera pushes in slowly toward subject',
    'pull_out' => 'Camera pulls out revealing environment',
    'dolly_in' => 'Dolly moves camera closer to subject',
    'dolly_out' => 'Dolly moves camera away from subject',

    // Vertical movement
    'tilt_up' => 'Camera tilts upward revealing height',
    'tilt_down' => 'Camera tilts downward',
    'pedestal_up' => 'Camera rises vertically',
    'pedestal_down' => 'Camera lowers vertically',

    // Complex movement
    'orbit_cw' => 'Camera orbits clockwise around subject',
    'orbit_ccw' => 'Camera orbits counter-clockwise',
    'tracking' => 'Camera tracks alongside moving subject',
    'follow' => 'Camera follows behind subject',

    // Static
    'static' => 'Camera remains stationary',
    'locked_off' => 'Camera locked on tripod, no movement',
];
```

---

### Phase 3: Frame Continuity System

#### 3.1 First-Last Frame (FLF) Chain

```php
public function generateShotWithContinuity(int $sceneIndex, int $shotIndex): array
{
    $shot = $this->getShot($sceneIndex, $shotIndex);
    $previousShot = $shotIndex > 0 ? $this->getShot($sceneIndex, $shotIndex - 1) : null;

    // Get last frame from previous shot as reference
    $firstFrameReference = null;
    if ($previousShot && !empty($previousShot['videoUrl'])) {
        $firstFrameReference = $this->extractLastFrame($previousShot['videoUrl']);
    }

    // Build continuity-aware prompt
    $prompt = $this->buildContinuityPrompt($shot, $previousShot);

    // Generate with FLF if supported by model
    if ($this->modelSupportsFLF($shot['selectedVideoModel'])) {
        return $this->generateWithFLF($shot, $firstFrameReference, $prompt);
    }

    // Fallback: include first frame as reference image
    return $this->generateWithFrameReference($shot, $firstFrameReference, $prompt);
}
```

#### 3.2 Continuity Tracking Object

```php
class ShotContinuity
{
    public string $characterIdentity;      // "rugged man, dark jacket, short hair"
    public string $characterWardrobe;      // "dark tactical jacket, gray shirt"
    public string $palette;                // "warm amber, golden hour"
    public string $timeOfDay;              // "sunset"
    public string $lightingDirection;      // "backlit, sun behind"
    public string $cameraMotionEnd;        // "moving right"
    public string $subjectPositionEnd;     // "screen-center"
    public string $subjectActionEnd;       // "paused at stall"
    public ?string $lastFrameBase64;       // Actual frame data

    public function toPromptAnchors(): string
    {
        return "Continuity: {$this->characterIdentity}, wearing {$this->characterWardrobe}. " .
               "Maintain {$this->palette} color palette, {$this->timeOfDay} lighting. " .
               "Subject enters from {$this->subjectPositionEnd}, continuing {$this->subjectActionEnd}.";
    }
}
```

#### 3.3 End Frame Extraction & Storage

```php
public function extractAndStoreLastFrame(int $sceneIndex, int $shotIndex): void
{
    $shot = $this->getShot($sceneIndex, $shotIndex);
    if (empty($shot['videoUrl'])) return;

    // Extract last frame using FFmpeg
    $lastFrame = $this->ffmpegExtractFrame($shot['videoUrl'], 'last');

    // Store as base64 for next shot reference
    $this->storyboard['scenes'][$sceneIndex]['decomposition']['shots'][$shotIndex]['lastFrameBase64'] = $lastFrame;

    // Also analyze the frame for continuity metadata
    $analysis = $this->analyzeFrameForContinuity($lastFrame);
    $this->storyboard['scenes'][$sceneIndex]['decomposition']['shots'][$shotIndex]['continuityData'] = $analysis;
}
```

---

### Phase 4: Story Arc Integration

#### 4.1 Scene-Level Story Beat Definition

Before decomposing, define the scene's story structure:

```php
$sceneStoryArc = [
    'opening_state' => 'Character arrives in Cairo, searching for contact',
    'tension_point' => 'Notices suspicious activity in crowd',
    'turning_point' => 'Makes eye contact with contact person',
    'closing_state' => 'Begins approach, heightened alertness',
    'emotional_arc' => ['curious', 'cautious', 'alert', 'determined'],
];
```

#### 4.2 Shot-to-Arc Mapping

Each shot maps to a story beat and emotion:

```php
$shots = [
    [
        'shotNumber' => 1,
        'purpose' => 'establishing',
        'storyBeat' => 'opening_state',
        'emotion' => 'curious',
        'narrativeFunction' => 'Show Cairo environment, establish exotic location'
    ],
    [
        'shotNumber' => 2,
        'purpose' => 'introduction',
        'storyBeat' => 'opening_state',
        'emotion' => 'cautious',
        'narrativeFunction' => 'Introduce protagonist navigating the crowd'
    ],
    [
        'shotNumber' => 3,
        'purpose' => 'action',
        'storyBeat' => 'tension_point',
        'emotion' => 'alert',
        'narrativeFunction' => 'Character scans crowd, notices something'
    ],
    // ... etc
];
```

---

### Phase 5: Multitalk Integration for Dialogue/Monologue

#### 5.1 Shot Types for Multitalk

| Shot Type | Multitalk Usage | Audio Source |
|-----------|-----------------|--------------|
| **Monologue** | Character speaking to self/camera | Generated voiceover |
| **Dialogue** | Two characters conversing | TTS for each character |
| **Reaction** | Character listening/reacting | No audio (or ambient) |
| **Action with VO** | Narrator over action | Narrator TTS |

#### 5.2 Lip-Sync Shot Workflow

```
1. Generate shot image (with character face visible)
2. Extract/generate monologue text
3. Generate voiceover audio (character's voice)
4. Send to Multitalk: image + audio → lip-synced video
5. Extract last frame for next shot continuity
```

#### 5.3 Dialogue Shot Sequence

For two-character dialogue:
```
Shot A: Character 1 speaking (Multitalk with Char1 audio)
Shot B: Character 2 listening/reacting (standard video, no Multitalk)
Shot C: Character 2 responding (Multitalk with Char2 audio)
Shot D: Character 1 reacting (standard video)
... alternating pattern
```

---

## Implementation Priority

### Immediate (This Session)
1. [x] Fix video prompt quality - add professional structure ✓
2. [x] Add shot similarity detection to prevent redundant shots ✓
3. [x] Add shot purpose validation ✓
4. [x] Improve decomposition prompts to request variety ✓
5. [ ] Verify Frame Capture modal functionality

### Short-term (Next Session)
6. [ ] Implement last-frame extraction after video generation
7. [ ] Add continuity anchors to video prompts
8. [ ] Implement FLF chain for supported models
9. [ ] Add camera movement vocabulary to prompts
10. [ ] Add Action-Reaction beat patterns to decomposition

### Medium-term (Phase 6 Story Building)
11. [ ] Implement scene type classification (75 types)
12. [ ] Add beat structure decomposition (4-7 beats per scene)
13. [ ] Implement dialogue shot/reverse shot system
14. [ ] Add insert shot detection for object reveals
15. [ ] Implement emotion progression mapping
16. [ ] Build interaction pattern library (reveal, confrontation, chase, etc.)

### Multitalk Integration (Phase 7)
17. [ ] Detect dialogue shots requiring Multitalk
18. [ ] Character voice assignment system
19. [ ] Monologue shot workflow (image → TTS → Multitalk)
20. [ ] Dialogue sequencing with alternating speakers
21. [ ] Lip-sync quality validation

---

## Phase 6: Cinematic Story Building Enhancement

This phase addresses the core requirement: **building proper stories with character interactions, props, dialogue, and emotional beats**.

### 6.1 Action-Reaction Beat Patterns

Every scene follows the **ARC Framework** (Action → Reaction → Consequence):

```
BEAT PATTERN TEMPLATE:
1. ACTION: Character A does something significant
2. REVEAL: What they did is shown (insert shot if needed)
3. REACTION: Character B responds (emotionally or verbally)
4. CONSEQUENCE: The situation changes

SHOT BREAKDOWN:
- Shot A: Character A's action (medium/close-up on actor)
- Shot B: Insert shot of object/result (if prop involved)
- Shot C: Character B's reaction (close-up on face)
- Shot D: Character B's response (medium shot if dialogue)
```

**Example - The Amulet Reveal (User's Template):**
```php
$beatPattern = [
    [
        'shotNumber' => 1,
        'type' => 'medium',
        'subject' => 'Zoran',
        'action' => 'looks at Nila with knowing expression',
        'camera' => 'static, eye level',
        'purpose' => 'setup',
        'emotion' => 'mysterious'
    ],
    [
        'shotNumber' => 2,
        'type' => 'close-up_insert',
        'subject' => "Zoran's hand",
        'action' => 'pulls amulet from jacket pocket',
        'camera' => 'push_in slowly on object',
        'purpose' => 'reveal',
        'emotion' => 'tension'
    ],
    [
        'shotNumber' => 3,
        'type' => 'close-up',
        'subject' => 'Nila',
        'action' => 'eyes widen, jaw drops slightly',
        'camera' => 'static, focus on eyes',
        'purpose' => 'reaction',
        'emotion' => 'shock/surprise'
    ],
    [
        'shotNumber' => 4,
        'type' => 'medium',
        'subject' => 'Nila',
        'action' => 'speaks: "How the hell did you get your hands on the rare Montipoli amulet?"',
        'camera' => 'slight push_in during dialogue',
        'purpose' => 'response',
        'emotion' => 'disbelief',
        'multitalk' => true // Flag for lip-sync
    ]
];
```

### 6.2 Core Interaction Patterns Library

These are reusable templates for common scene types:

#### Pattern: Object Reveal
```
SETUP → REACH → REVEAL (insert) → REACTION
Use for: Weapons, documents, gifts, evidence, keys, phones
```

#### Pattern: Dialogue Exchange
```
SPEAKER A (medium) → LISTENER B (reaction) → SPEAKER B (medium) → LISTENER A (reaction)
180-degree rule: Keep characters on same side of frame throughout
```

#### Pattern: Discovery
```
CHARACTER SEARCHING → FINDS SOMETHING → INSERT OF DISCOVERY → EMOTIONAL REACTION
Use for: Finding bodies, evidence, hidden rooms, secrets
```

#### Pattern: Confrontation
```
APPROACH → FACE-OFF (two-shot) → SPEAKER A → SPEAKER B → ESCALATION
Camera: Tightens with each cut as tension builds
```

#### Pattern: Chase/Pursuit
```
PURSUER → PURSUED (over-shoulder) → OBSTACLE → REACTION → ESCAPE/CAPTURE
Motion direction: Maintain consistent screen direction
```

#### Pattern: Transformation
```
BEFORE STATE → CATALYST MOMENT → PROCESS → AFTER STATE → REACTION
Use for: Makeovers, revelations, powers activating
```

### 6.3 Shot/Reverse Shot System (Dialogue)

For any two-character conversation:

```php
class DialogueDecomposer
{
    /**
     * The 180-degree rule: An imaginary line connects the two subjects.
     * All shots must be from the SAME SIDE of this line.
     */
    const DIALOGUE_RULES = [
        'eyeline_match' => 'Character A looks screen-right, Character B looks screen-left',
        'shot_sizes' => ['over-shoulder', 'medium', 'close-up'],
        'progression' => 'Start wider, get tighter as tension increases',
        'reaction_shots' => 'Include silent reaction shots between dialogue',
    ];

    public function decomposeDialogue(array $dialogueLines): array
    {
        $shots = [];
        $currentSpeaker = null;
        $shotSize = 'medium'; // Start medium, tighten over time

        foreach ($dialogueLines as $idx => $line) {
            $speaker = $line['character'];
            $isNewSpeaker = ($speaker !== $currentSpeaker);

            if ($isNewSpeaker && $currentSpeaker !== null) {
                // Add reaction shot of previous speaker before cutting
                $shots[] = [
                    'type' => 'close-up',
                    'subject' => $currentSpeaker,
                    'action' => 'listens, reacts silently',
                    'camera' => 'static',
                    'purpose' => 'reaction',
                    'duration' => 1.5, // Short beat
                ];
            }

            // Speaker shot
            $shots[] = [
                'type' => $shotSize,
                'subject' => $speaker,
                'action' => $line['action'] ?? "speaks: \"{$line['dialogue']}\"",
                'camera' => $this->getCameraForDialogue($idx, count($dialogueLines)),
                'purpose' => 'dialogue',
                'dialogue' => $line['dialogue'],
                'multitalk' => true,
                'eyeline' => $this->getEyeline($speaker),
            ];

            $currentSpeaker = $speaker;

            // Tighten shots as scene progresses
            if ($idx > count($dialogueLines) / 2) {
                $shotSize = 'close-up';
            }
        }

        return $shots;
    }

    private function getCameraForDialogue(int $lineIndex, int $totalLines): string
    {
        $progress = $lineIndex / $totalLines;
        if ($progress < 0.3) return 'static';
        if ($progress < 0.6) return 'slight push_in';
        return 'slow push_in'; // More dynamic as tension builds
    }
}
```

### 6.4 Insert Shot Rules

Insert shots are critical for:
- Object reveals (weapons, letters, phones)
- Hands performing actions
- Clocks/countdowns
- Reactions to off-screen events
- Environment details

```php
const INSERT_SHOT_TRIGGERS = [
    'pulls out' => true,    // Object reveal
    'hands over' => true,   // Object transfer
    'picks up' => true,     // Object interaction
    'looks at' => true,     // When object is significant
    'opens' => true,        // Document/door/container
    'types' => true,        // Phone/computer screen
    'presses' => true,      // Button/trigger
    'clock shows' => true,  // Time pressure
];

public function shouldAddInsertShot(string $action): bool
{
    foreach (self::INSERT_SHOT_TRIGGERS as $trigger => $required) {
        if (stripos($action, $trigger) !== false) {
            return true;
        }
    }
    return false;
}

public function createInsertShot(string $action, string $object): array
{
    return [
        'type' => 'extreme_close-up',
        'subject' => $object,
        'action' => $action,
        'camera' => 'push_in slowly',
        'purpose' => 'insert_reveal',
        'duration' => 2.0, // Short, punchy
        'focusArea' => 'object_only', // No face in frame
    ];
}
```

### 6.5 Scene Type Classification

Based on 75 professional scene types, categorize for appropriate shot patterns:

```php
const SCENE_TYPES = [
    // HIGH TENSION - Use tight shots, quick cuts
    'confrontation' => ['shot_style' => 'tight', 'pacing' => 'fast', 'camera' => 'handheld'],
    'chase' => ['shot_style' => 'dynamic', 'pacing' => 'rapid', 'camera' => 'tracking'],
    'fight' => ['shot_style' => 'tight', 'pacing' => 'rapid', 'camera' => 'handheld'],
    'escape' => ['shot_style' => 'wide_to_tight', 'pacing' => 'fast', 'camera' => 'tracking'],
    'final_battle' => ['shot_style' => 'epic_wide', 'pacing' => 'building', 'camera' => 'crane'],

    // EMOTIONAL - Use close-ups, slow pacing
    'revelation' => ['shot_style' => 'close-up_focus', 'pacing' => 'slow', 'camera' => 'static'],
    'reunion' => ['shot_style' => 'medium_to_tight', 'pacing' => 'slow', 'camera' => 'push_in'],
    'loss' => ['shot_style' => 'close-up', 'pacing' => 'slow', 'camera' => 'static'],
    'transformation' => ['shot_style' => 'progressive', 'pacing' => 'building', 'camera' => 'orbit'],
    'emotional_breakdown' => ['shot_style' => 'intimate', 'pacing' => 'slow', 'camera' => 'handheld'],

    // DISCOVERY - Use reveals, insert shots
    'discovery' => ['shot_style' => 'search_to_find', 'pacing' => 'building', 'camera' => 'pov'],
    'investigation' => ['shot_style' => 'detail_oriented', 'pacing' => 'methodical', 'camera' => 'tracking'],
    'exploration' => ['shot_style' => 'wide_establishing', 'pacing' => 'slow', 'camera' => 'pan'],

    // DIALOGUE - Use shot/reverse shot
    'conversation' => ['shot_style' => 'medium_dialogue', 'pacing' => 'natural', 'camera' => 'static'],
    'negotiation' => ['shot_style' => 'tightening', 'pacing' => 'building', 'camera' => 'push_in'],
    'interrogation' => ['shot_style' => 'oppressive', 'pacing' => 'slow', 'camera' => 'low_angle'],
    'seduction' => ['shot_style' => 'intimate', 'pacing' => 'slow', 'camera' => 'soft_focus'],

    // INTRODUCTION - Use wide establishing shots
    'first_meeting' => ['shot_style' => 'wide_to_medium', 'pacing' => 'building', 'camera' => 'reveal'],
    'arrival' => ['shot_style' => 'establishing', 'pacing' => 'slow', 'camera' => 'wide_pan'],
    'entering_new_world' => ['shot_style' => 'epic_wide', 'pacing' => 'slow', 'camera' => 'tilt_up'],
];

public function getSceneTypeSettings(string $sceneDescription): array
{
    // AI classification of scene type
    $detectedType = $this->classifySceneType($sceneDescription);
    return self::SCENE_TYPES[$detectedType] ?? self::SCENE_TYPES['conversation'];
}
```

### 6.6 Beat Structure Per Scene

Every scene has 4-7 beats. Each beat = 1-3 shots.

```php
const BEAT_STRUCTURE = [
    'opening_image' => [
        'purpose' => 'Set the tone, show status quo',
        'shots' => 1,
        'types' => ['establishing', 'wide'],
    ],
    'inciting_beat' => [
        'purpose' => 'Something disrupts the status quo',
        'shots' => 2,
        'types' => ['action', 'reaction'],
    ],
    'rising_action' => [
        'purpose' => 'Tension builds through complications',
        'shots' => '2-4',
        'types' => ['action', 'dialogue', 'insert'],
    ],
    'turning_point' => [
        'purpose' => 'Major shift in direction or understanding',
        'shots' => 2,
        'types' => ['revelation', 'close-up'],
    ],
    'climax_beat' => [
        'purpose' => 'Peak emotional or action moment',
        'shots' => '2-3',
        'types' => ['close-up', 'reaction', 'action'],
    ],
    'resolution_beat' => [
        'purpose' => 'New status quo established',
        'shots' => 1,
        'types' => ['medium', 'wide'],
    ],
];

public function decomposeSceneIntoBeats(array $scene): array
{
    $beats = [];

    // Analyze scene to identify beats
    $sceneAnalysis = $this->analyzeSceneForBeats($scene);

    foreach ($sceneAnalysis['beats'] as $beatType => $beatContent) {
        $beatConfig = self::BEAT_STRUCTURE[$beatType] ?? self::BEAT_STRUCTURE['rising_action'];

        $beats[] = [
            'type' => $beatType,
            'content' => $beatContent,
            'targetShotCount' => $beatConfig['shots'],
            'allowedShotTypes' => $beatConfig['types'],
            'purpose' => $beatConfig['purpose'],
        ];
    }

    return $beats;
}
```

### 6.7 Emotional Progression Mapping

Track character emotion through the scene:

```php
const EMOTION_PROGRESSIONS = [
    'building_tension' => ['calm', 'uneasy', 'worried', 'fearful', 'terrified'],
    'discovery_arc' => ['curious', 'intrigued', 'surprised', 'shocked', 'understanding'],
    'confrontation_arc' => ['calm', 'defensive', 'angry', 'furious', 'resolved'],
    'romance_arc' => ['nervous', 'interested', 'attracted', 'hopeful', 'joyful'],
    'loss_arc' => ['hopeful', 'worried', 'desperate', 'devastated', 'numb'],
];

public function mapEmotionToShot(string $emotion): array
{
    $emotionMapping = [
        'calm' => ['shot_type' => 'wide', 'camera' => 'static', 'lighting' => 'neutral'],
        'uneasy' => ['shot_type' => 'medium', 'camera' => 'slight_movement', 'lighting' => 'shadow'],
        'surprised' => ['shot_type' => 'close-up', 'camera' => 'quick_push_in', 'lighting' => 'bright'],
        'shocked' => ['shot_type' => 'extreme_close-up', 'camera' => 'static', 'lighting' => 'high_contrast'],
        'terrified' => ['shot_type' => 'close-up', 'camera' => 'handheld', 'lighting' => 'dark'],
        'angry' => ['shot_type' => 'medium', 'camera' => 'push_in', 'lighting' => 'hard'],
        'joyful' => ['shot_type' => 'medium', 'camera' => 'slight_orbit', 'lighting' => 'warm'],
    ];

    return $emotionMapping[$emotion] ?? $emotionMapping['calm'];
}
```

### 6.8 Implementation: Enhanced Decomposition Prompt

The decomposition prompt must now include all these patterns:

```php
protected function buildEnhancedDecompositionPrompt(array $scene, array $characters): string
{
    $characterList = $this->formatCharacterList($characters);
    $sceneType = $this->classifySceneType($scene['description']);
    $settings = self::SCENE_TYPES[$sceneType];

    return <<<PROMPT
You are a professional cinematographer decomposing a scene into shots.

SCENE DESCRIPTION:
{$scene['description']}

CHARACTERS IN SCENE:
{$characterList}

SCENE TYPE DETECTED: {$sceneType}
RECOMMENDED STYLE: {$settings['shot_style']}
PACING: {$settings['pacing']}
CAMERA APPROACH: {$settings['camera']}

DECOMPOSITION RULES:

1. **BEAT STRUCTURE**: Identify 4-7 story beats, then assign 1-3 shots per beat.

2. **ACTION-REACTION**: For EVERY significant action, include:
   - The action shot (who does what)
   - Insert shot if object involved (close-up of the object)
   - Reaction shot (how others respond)

3. **DIALOGUE**: Use shot/reverse shot pattern:
   - Speaker A medium shot
   - Listener B reaction close-up
   - Speaker B medium shot
   - Keep 180-degree rule (consistent screen direction)

4. **NO REDUNDANT SHOTS**: Each shot must be visually DISTINCT:
   - Different shot type (wide/medium/close-up)
   - Different subject focus
   - Different camera movement
   - Different emotional beat

5. **SHOT PROGRESSION**: Follow logical tightening:
   - Start WIDE (establish space)
   - Move to MEDIUM (introduce characters)
   - End on CLOSE-UP (emotional payoff)

6. **INSERT SHOTS**: Add for:
   - Object reveals (hands, props, documents)
   - Clocks/countdowns
   - Significant details

OUTPUT FORMAT (JSON):
{
  "beats": [
    {
      "type": "opening_image|inciting_beat|rising_action|turning_point|climax_beat|resolution_beat",
      "purpose": "Why this beat exists in the story",
      "shots": [
        {
          "shotNumber": 1,
          "shotType": "establishing|wide|medium|close-up|extreme_close-up|insert",
          "subject": "What/who is the focus",
          "action": "What happens (be specific)",
          "cameraMovement": "static|pan_left|push_in|tracking|orbit_cw|etc",
          "emotion": "Character's emotional state",
          "dialogue": "If character speaks (for Multitalk)",
          "purpose": "establishing|introduction|action|reaction|transition|climax|resolution",
          "eyeline": "screen-left|screen-right|camera" (for dialogue continuity)
        }
      ]
    }
  ],
  "continuityNotes": {
    "screenDirection": "Characters moving left-to-right",
    "lightingConsistency": "Golden hour, backlit",
    "costumeNotes": "Zoran: dark jacket, Nila: red dress"
  }
}

CRITICAL:
- NO two consecutive shots of same type with same subject
- Include reaction shots after significant actions
- Flag dialogue shots for Multitalk lip-sync
- Maintain consistent screen direction
PROMPT;
}
```

---

## Phase 7: Multitalk Integration for Dialogue

### 7.1 Dialogue Shot Detection

```php
public function detectMultitalkShots(array $shots): array
{
    return array_filter($shots, function($shot) {
        return !empty($shot['dialogue']) ||
               ($shot['purpose'] === 'dialogue') ||
               (stripos($shot['action'] ?? '', 'says') !== false) ||
               (stripos($shot['action'] ?? '', 'speaks') !== false);
    });
}
```

### 7.2 Dialogue Workflow

```
1. Generate shot image (character with visible face)
2. Extract dialogue text from shot
3. Generate voiceover audio (character's voice via TTS)
4. Send to Multitalk: image + audio → lip-synced video
5. Extract last frame for next shot continuity
```

### 7.3 Character Voice Assignment

```php
public function getCharacterVoice(string $characterName): array
{
    $voices = $this->sceneMemory['characterBible']['characters'] ?? [];

    foreach ($voices as $char) {
        if ($char['name'] === $characterName) {
            return [
                'voiceId' => $char['voiceId'] ?? 'default_male',
                'voiceStyle' => $char['voiceStyle'] ?? 'neutral',
                'language' => $char['language'] ?? 'en',
            ];
        }
    }

    return ['voiceId' => 'default', 'voiceStyle' => 'neutral', 'language' => 'en'];
}
```

---

## Testing Protocol

After implementing changes, test with the SAME scene:

1. **Decomposition Test**
   - Generate new decomposition
   - Verify no redundant shots (similarity < 0.7)
   - Verify each shot has unique purpose
   - Verify shot type progression is logical
   - Verify beat structure (4-7 beats identified)

2. **Story Building Test**
   - Verify Action-Reaction patterns present
   - Check for insert shots on object reveals
   - Verify dialogue uses shot/reverse shot
   - Check emotion progression is logical
   - Verify screen direction consistency (180-degree rule)

3. **Video Prompt Test**
   - Check prompts include all components
   - Verify camera terminology is precise
   - Verify continuity anchors present
   - Check emotion/mood included in prompts

4. **Continuity Test**
   - Generate all videos
   - Play sequence
   - Check: character consistency, lighting consistency, motion flow
   - Verify eyeline matching in dialogue scenes

5. **Multitalk Test**
   - Identify shots with monologue/dialogue
   - Generate voiceovers with correct character voices
   - Animate with Multitalk
   - Verify lip-sync quality
   - Check audio-visual sync

6. **Frame Capture Test**
   - Open Frame Capture modal on animated shot
   - Verify video scrubbing works
   - Capture frame at specific timestamp
   - Transfer frame to next shot
   - Verify face correction works if needed

---

## Success Criteria

| Metric | Current | Target |
|--------|---------|--------|
| Adjacent shot similarity | >70% | <50% |
| Shots with clear purpose | ~50% | 100% |
| Professional prompt compliance | ~20% | 100% |
| Character consistency score | ~60% | >85% |
| Viewer perceived continuity | Poor | Smooth |
| Beat structure coverage | 0% | 100% |
| Action-Reaction patterns | 0% | >80% of applicable scenes |
| Insert shots for objects | 0% | 100% when object involved |
| Dialogue shot/reverse shot | 0% | 100% of dialogue scenes |
| Emotion progression | 0% | Logical arc per scene |
| 180-degree rule compliance | ~30% | 100% |
| Multitalk lip-sync quality | N/A | Natural appearance |

---

## Sources

- [Veo 3.1 Multi-Prompt Best Practices](https://skywork.ai/blog/multi-prompt-multi-shot-consistency-veo-3-1-best-practices/)
- [StoryMem: Multi-shot Long Video Storytelling](https://arxiv.org/html/2512.19539v1)
- [Venice AI Video Prompt Engineering Guide](https://venice.ai/blog/the-complete-guide-to-ai-video-prompt-engineering)
- [LTX Studio Prompt Guide](https://ltx.studio/blog/ai-video-prompt-guide)
- [Artlist AI Video Transitions](https://artlist.io/blog/ai-video-transitions-study-case/)
- [Wan-2.1 First-Last Frame](https://fal.ai/models/fal-ai/wan-flf2v)
- [Seedance 1.0 Multi-Shot](https://superduperai.co/en/blog/seedance-ai)
- [ShotDirector Paper](https://arxiv.org/html/2512.10286v1)
- [HoloCine Paper](https://arxiv.org/html/2510.20822v1)
