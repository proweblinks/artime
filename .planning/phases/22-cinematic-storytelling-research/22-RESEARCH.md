# Phase 22: Cinematic Storytelling Research

**Researched:** 2026-01-28
**Domain:** AI Image Generation Prompt Engineering for Cinematic Frames
**Confidence:** HIGH (multiple sources cross-verified)

## Executive Summary

This research investigates why AI image generators produce portrait-like images instead of cinematic storytelling frames, and documents solutions for the Artime Video Wizard prompt pipeline.

**Root Cause Identified:** AI image generators default to "portrait mode" because of three compounding factors:
1. **Training data bias** - Models trained on datasets dominated by stock photos and front-facing portraits
2. **Tag frequency effects** - Tags like "looking at viewer" appear in millions of training images (especially Danbooru-based models)
3. **Prompt structure** - Character descriptions without explicit action verbs or camera direction trigger portrait associations

**Primary Recommendation:** Transform prompts from static character descriptions to narrative action sequences with explicit camera direction, environmental context, and character DOING something rather than BEING something.

**Key Insight:** The difference between a portrait and a cinematic frame is **narrative intention** - a frame extracted from a larger story with deliberate mood, lighting, and compositional choices that imply what happened before and what happens next.

---

## 1. Root Cause Analysis: Why Portrait Mode Happens

### 1.1 Training Data Bias (HIGH confidence)

AI image models are trained on massive internet-scraped datasets that over-represent certain image types:

| Dataset | Size | Problem |
|---------|------|---------|
| LAION-5B (Stable Diffusion) | 5B+ images | Dominated by stock photos, portraits, product images |
| Danbooru (anime models) | Millions | Character illustrations with "looking at viewer" as common tag |
| Stock photo libraries | Scraped without curation | Portrait-style poses optimized for licensing/thumbnails |

**Source:** [MIT Technology Review - AI Image Model Bias](https://www.technologyreview.com/2023/03/22/1070167/these-news-tool-let-you-see-for-yourself-how-biased-ai-image-models-are/)

**Key Finding:** When generating "a photo of a person," models produce front-facing portraits 65%+ of the time because that's what dominates training data.

### 1.2 Tag Association Effects (HIGH confidence)

Danbooru-trained models (anime/illustration) have strong associations:

| Tag | Training Frequency | Effect |
|-----|-------------------|--------|
| `looking_at_viewer` | Extremely common | Default gaze behavior |
| `portrait` | Very common | Framing defaults to head/shoulders |
| `standing` | Common | Static pose without action |
| `solo` | Very common | Single subject, no environmental story |

**Source:** [Danbooru Prompting Guide on Civitai](https://civitai.com/articles/10962/illustrious-prompting-guide-or-v01-or-generate-anime-art-with-ai)

**Implication:** Without explicit counter-direction, models gravitate toward these high-frequency patterns.

### 1.3 Prompt Structure Triggers Portrait Mode (MEDIUM confidence)

Prompts that describe WHO without WHAT THEY'RE DOING trigger portrait associations:

**Portrait-Triggering Patterns:**
```
"A woman with long dark hair"           -> Portrait
"A detective in a trench coat"          -> Portrait
"A young man with blue eyes"            -> Portrait
"Character name in location"            -> Portrait
```

**Cinematic-Triggering Patterns:**
```
"A woman running through rain"          -> Action frame
"Detective investigating crime scene"   -> Narrative frame
"Young man recoiling in shock"          -> Reaction shot
"Character reaching for object"         -> Insert shot opportunity
```

**Key Pattern:** Verbs create narrative; adjectives create portraits.

### 1.4 Fourth Wall Breaking (MEDIUM confidence)

The "looking at camera" phenomenon breaks cinematic immersion:

- **In photography:** Subject addressing viewer is common (engagement)
- **In cinematography:** Subject addressing camera breaks fourth wall (rare, intentional)
- **In AI generation:** Default behavior without explicit direction

**Professional film rule:** Characters look at other characters, objects, or off-screen action - NOT at the camera (except for specific narrative effects like breaking fourth wall).

---

## 2. Cinematography Principles for Storytelling Frames

### 2.1 What Makes a Frame "Tell a Story" (HIGH confidence)

A cinematic frame implies narrative through:

| Element | Portrait | Cinematic Frame |
|---------|----------|-----------------|
| **Gaze Direction** | At camera | At something/someone in scene |
| **Body Language** | Static pose | Mid-action, gesture, movement |
| **Environment** | Background blur/irrelevant | Context that matters to story |
| **Framing** | Centered subject | Rule of thirds, lead room |
| **Emotional State** | Neutral/posed | Specific emotion visible |
| **Implied Motion** | None | Before/after moment suggested |

**Source:** [Suite Studios - Framing the Narrative](https://blog.suitestudios.io/article/framing-the-narrative-how-composition-tells-a-story-in-film)

### 2.2 The 180-Degree Rule and Eyeline (HIGH confidence)

For multi-shot sequences (dialogue, action-reaction):

**The Rule:** Draw an imaginary line between characters. Keep all cameras on ONE side.

**Result:**
- Character A always looks screen-right
- Character B always looks screen-left
- Consistent spatial geography for viewer

**Application to Prompts:** Specify "looking screen-left" or "looking screen-right" rather than "looking at camera."

**Source:** [MasterClass - 180-Degree Rule](https://www.masterclass.com/articles/understanding-the-180-degree-rule-in-cinematography)

### 2.3 Shot Types and Their Narrative Functions (HIGH confidence)

| Shot Type | Frame | Narrative Purpose | Gaze Direction |
|-----------|-------|-------------------|----------------|
| **Establishing** | Ultra-wide | Set geography | N/A (environment focus) |
| **Wide** | Full body + environment | Show action context | At scene elements |
| **Medium** | Waist up | Balance action + emotion | At other characters/objects |
| **Close-up** | Face fills frame | Emotional intensity | Off-camera or internal |
| **Insert/Detail** | Object/hands | Highlight story element | N/A (object focus) |
| **POV** | What character sees | Immersive perspective | N/A (we ARE the character) |
| **Over-Shoulder** | Behind one, facing other | Conversation depth | At conversation partner |
| **Reaction** | Face responding | Show emotional impact | Off-screen toward stimulus |

**Key Insight:** Most shot types have characters looking at something OTHER than the camera.

### 2.4 Camera Angles and Power Dynamics (HIGH confidence)

| Angle | Effect | When to Use |
|-------|--------|-------------|
| **Low angle** | Subject appears powerful, dominant | Hero moments, villains, authority |
| **Eye level** | Neutral, equal footing | Standard dialogue, neutral scenes |
| **High angle** | Subject appears vulnerable, diminished | Defeat, fear, isolation |
| **Dutch angle** | Unease, disorientation | Tension, psychological distress |

**Source:** [Videomaker - Cinematography 101](https://www.videomaker.com/article/c18/15419-cinematography-techniques/)

---

## 3. Prompt Engineering Solutions

### 3.1 The Cinematic Prompt Formula (HIGH confidence)

Based on professional AI video/image guides, the optimal structure:

```
[SHOT TYPE] + [CAMERA SPEC] + [SUBJECT + ACTION] + [ENVIRONMENT] + [MOOD/LIGHTING] + [TECHNICAL]
```

**Example Transformation:**

| Before (Portrait) | After (Cinematic) |
|-------------------|-------------------|
| "A detective in a dark alley" | "MEDIUM SHOT. Detective crouching to examine evidence, flashlight beam cutting through darkness. Rain-slicked alley, neon signs reflecting on wet pavement. Film noir lighting, deep shadows." |

**Source:** [Aiarty - Midjourney Cinematic Prompts](https://www.aiarty.com/midjourney-prompts/midjourney-cinematic-prompts.htm)

### 3.2 Action Verbs That Create Narrative (HIGH confidence)

Replace static descriptions with dynamic action verbs:

**Static (Avoid):**
- standing, sitting, posing, looking, wearing, being

**Dynamic (Use):**
- running, reaching, turning, recoiling, examining, fleeing, confronting
- investigates, discovers, reveals, pursues, escapes, observes
- mid-stride, mid-gesture, frozen moment of action

**Source:** [LTX Studio - AI Video Prompt Guide](https://ltx.studio/blog/ai-video-prompt-guide)

### 3.3 Gaze Direction Keywords (MEDIUM confidence)

To control where characters look:

| Keyword | Effect |
|---------|--------|
| `looking away` | Generic off-camera |
| `looking screen-left` | Specific direction |
| `looking screen-right` | Specific direction |
| `looking down at [object]` | Grounds gaze in scene |
| `eyes fixed on [target]` | Creates narrative connection |
| `gaze toward horizon` | Contemplative mood |
| `profile view` | Side-facing, inherently not at camera |
| `from behind` | No face visible |

**Negative prompts to avoid portrait mode:**
- `looking at camera, looking at viewer, staring at viewer, eye contact, direct gaze, front-facing portrait`

**Source:** [Civitai - Looking at Viewer Sliders](https://civitai.com/models/229962/looking-at-viewer-sliders-ntcaixyz)

### 3.4 Environment and Context Keywords (HIGH confidence)

Cinematic frames need environment that MATTERS:

**Portrait Pattern:** Background is decorative blur
**Cinematic Pattern:** Background is story context

**Keywords that add context:**
- `in the midst of [action]`
- `surrounded by [environmental elements]`
- `with [relevant props] visible`
- `[location] establishing geography`

**Example:**
```
Before: "Woman in office"
After: "Woman hunched over laptop, papers scattered across desk, coffee cup abandoned,
        city skyline through window showing late hour, harsh fluorescent lighting"
```

### 3.5 Negative Prompts for Anti-Portrait (MEDIUM confidence)

Add to negative prompts to discourage portrait behavior:

```
looking at camera, looking at viewer, staring at viewer, eye contact, direct gaze,
front-facing, centered composition, posed, static, portrait style, studio lighting,
stock photo, headshot, passport photo, selfie
```

**Source:** [Aiarty - 200+ Stable Diffusion Negative Prompts](https://www.aiarty.com/stable-diffusion-prompts/stable-diffusion-negative-prompt.htm)

---

## 4. Shot Type Recommendations for Video Wizard

### 4.1 Shot Type Prompt Modifications

Based on existing `buildShotPrompt()` structure, add these shot-specific elements:

#### Establishing Shot
```php
$shotModifier = "ULTRA-WIDE ESTABLISHING SHOT. Camera positioned far away. " .
    "Characters appear small in vast environment. Location is the star of the frame. " .
    "Camera angle: aerial or high vantage point. 24mm ultra-wide perspective.";
$gazeDirection = ""; // No gaze specified - environment focus
$negativePrompt = "close-up, portrait, centered subject, single person focus";
```

#### Wide Shot
```php
$shotModifier = "WIDE SHOT showing full body head to toe. " .
    "Significant environment visible around subject. " .
    "Camera at eye level, full figure plus surrounding context.";
$gazeDirection = "Subject looking at [scene element], not at camera";
$negativePrompt = "close-up, portrait, looking at camera, centered";
```

#### Medium Shot
```php
$shotModifier = "MEDIUM SHOT framing waist up. " .
    "Face and gestures clearly visible with some body language. " .
    "50mm lens perspective, conversational distance.";
$gazeDirection = "Subject engaged with [action/other character], gaze directed at scene element";
$negativePrompt = "looking at camera, posed, static, portrait";
```

#### Close-up
```php
$shotModifier = "CLOSE-UP with face filling frame. Head and shoulders only. " .
    "Eyes at top third (rule of thirds). Background blurred. " .
    "100mm lens, emotional detail visible through facial expression.";
$gazeDirection = "Intense expression, gaze directed off-screen toward [stimulus]";
$negativePrompt = "looking at camera, posed, neutral expression, passport photo";
```

#### Reaction Shot
```php
$shotModifier = "REACTION SHOT capturing emotional response. " .
    "Close framing on face showing CHANGE in expression. " .
    "Character reacting to something off-screen.";
$gazeDirection = "Eyes widening/narrowing, looking toward off-screen [stimulus]";
$negativePrompt = "looking at camera, neutral, posed, static";
```

#### Over-the-Shoulder
```php
$shotModifier = "OVER-THE-SHOULDER SHOT. One character's shoulder/back of head in blurred foreground. " .
    "Other character's face in focus in background. Conversational depth.";
$gazeDirection = "In-focus character looking at foreground character (conversation partner)";
$negativePrompt = "both facing camera, looking at viewer, static poses";
```

#### POV Shot
```php
$shotModifier = "POINT-OF-VIEW SHOT. First-person perspective showing what character sees. " .
    "No character visible - we ARE the character. " .
    "Hands/body edges at frame edges if interacting.";
$gazeDirection = ""; // No subject - we're the viewer
$negativePrompt = "face visible, looking at camera, third person";
```

### 4.2 Action Integration by Shot Type

| Shot Type | Action Emphasis | Example |
|-----------|-----------------|---------|
| Establishing | Environmental storytelling | "City awakening at dawn, cars beginning to move" |
| Wide | Full body action | "Character sprinting across rooftop" |
| Medium | Gesture + expression | "Character gesturing emphatically while explaining" |
| Close-up | Micro-expression | "Jaw clenching, eyes narrowing with suspicion" |
| Reaction | Emotional response | "Eyes widening in shock, mouth dropping open" |
| Insert | Object interaction | "Fingers trembling as they open the envelope" |

---

## 5. Implementation Recommendations

### 5.1 Prompt Structure Changes (Priority: HIGH)

**Current Structure:**
```
[Story Content] + [Dialogue if present] + [Camera Shot] + [Technical Specs]
```

**Recommended Structure:**
```
[SHOT TYPE DIRECTIVE] + [CHARACTER + ACTION VERB] + [GAZE DIRECTION] +
[ENVIRONMENTAL CONTEXT] + [EMOTIONAL BEAT] + [CAMERA/LENS] + [LIGHTING/MOOD]
```

### 5.2 Mandatory Anti-Portrait Elements

Every shot prompt MUST include:

1. **Action verb** - Character doing something, not being something
2. **Gaze direction** - Explicit "looking at [scene element]" unless POV/establishing
3. **Environmental anchor** - Where this happens and why it matters
4. **Shot type prefix** - ALL CAPS directive at start (e.g., "CLOSE-UP.")

### 5.3 Negative Prompt Additions

Add to global negative prompts:
```php
$antiPortraitNegative = 'looking at camera, looking at viewer, staring at viewer, ' .
    'eye contact, direct gaze, front-facing portrait, centered composition, ' .
    'posed, static pose, stock photo, headshot, passport photo, selfie, ' .
    'studio portrait, formal portrait, ID photo';
```

### 5.4 Shot-Specific Gaze Templates

```php
const GAZE_TEMPLATES = [
    'establishing' => '', // No gaze - environment focus
    'wide' => 'Subject looking toward [direction/object], engaged with environment',
    'medium' => 'Subject looking at [other character/object], mid-conversation/action',
    'close-up' => 'Intense expression, gaze fixed on [off-screen element]',
    'reaction' => 'Eyes directed toward [stimulus], expression changing',
    'over-shoulder' => 'Subject looking at conversation partner',
    'pov' => '', // We ARE the viewer
    'detail' => '', // Object focus
    'two-shot' => 'Characters engaged with each other, not camera',
];
```

### 5.5 Action Verb Library by Scene Type

```php
const ACTION_VERBS = [
    'dialogue' => ['speaking with intensity', 'gesturing emphatically', 'leaning forward', 'responding'],
    'tension' => ['recoiling', 'backing away', 'clenching fists', 'freezing in place'],
    'discovery' => ['examining closely', 'reaching toward', 'reacting to', 'uncovering'],
    'pursuit' => ['running', 'chasing', 'fleeing', 'dodging', 'sprinting'],
    'emotion' => ['breaking down', 'composing themselves', 'fighting tears', 'erupting in joy'],
    'investigation' => ['scrutinizing', 'searching', 'investigating', 'analyzing'],
];
```

---

## 6. Before/After Examples

### Example 1: Dialogue Scene

**Before (Portrait Mode):**
```
"Elena in the detective's office, wearing a red dress"
```

**After (Cinematic):**
```
"MEDIUM SHOT. Elena leaning forward across the desk, finger pointing accusingly
at the detective. Her eyes fixed on his face, demanding answers.
Detective's office, case files scattered, harsh desk lamp creating dramatic shadows.
Tense atmosphere, film noir lighting. 50mm lens."
```

### Example 2: Discovery Scene

**Before (Portrait Mode):**
```
"Marcus finding a clue in the warehouse"
```

**After (Cinematic):**
```
"CLOSE-UP. Marcus's face illuminated by flashlight beam, eyes widening with recognition
as he examines the blood-stained document. Looking down at evidence, jaw tightening.
Dusty warehouse backdrop, cobwebs visible, industrial decay.
Suspenseful atmosphere, harsh directional lighting. 85mm lens, shallow depth of field."
```

### Example 3: Action Scene

**Before (Portrait Mode):**
```
"A man running through the city"
```

**After (Cinematic):**
```
"WIDE SHOT. Man mid-stride sprinting through rain-slicked street,
coat billowing behind him, looking over shoulder at pursuing threat.
Neon signs reflecting on wet pavement, other pedestrians scattering.
Desperate urgency, sodium vapor streetlight glow. 35mm lens, motion blur on feet."
```

---

## 7. State of the Art (2025-2026)

### Current Best Practices

| Old Approach | Current Approach | Impact |
|--------------|------------------|--------|
| "Character in location" | "Character [action verb] in [contextual location]" | Prevents portrait default |
| Generic shot names | ALL CAPS shot directives | Stronger model compliance |
| No gaze direction | Explicit "looking at [target]" | Controls eye direction |
| Style keywords only | Camera spec + lens + aspect ratio | More cinematic framing |
| Single positive prompt | Positive + anti-portrait negative | Better results |

### Model-Specific Notes

- **Midjourney v7** (default June 2025): Prefers short, high-signal phrases with `--ar 21:9` or `--ar 2.35:1`
- **Stable Diffusion/Flux**: Tag-style prompts with Danbooru vocabulary for anime, natural language for realistic
- **DALL-E/GPT-Image**: Natural language paragraphs with camera terminology

**Source:** [Skywork AI - Midjourney Prompts 2025](https://skywork.ai/blog/midjourney-prompts-formulas-2025/)

---

## 8. Open Questions

### 8.1 Model-Specific Effectiveness

**Question:** Do different image models (nanobanana-pro, DALL-E, etc.) respond differently to anti-portrait prompts?

**Recommendation:** Test the recommended prompt structure across models used in Video Wizard. Document which keywords have strongest effect per model.

### 8.2 Character Consistency vs. Cinematic Framing

**Question:** Does adding gaze direction and action conflict with Character Bible reference images (which are likely front-facing)?

**Recommendation:** Reference images should be used for IDENTITY (face features, clothing), not POSE. Add explicit instruction in prompt: "Same character identity as reference, but in action pose looking [direction]."

### 8.3 Automated Action Selection

**Question:** Can we automatically select appropriate action verbs based on scene narration?

**Recommendation:** Implement action verb extraction from narration using NLP. Map emotion tags to suggested action verbs.

---

## 9. Sources

### Primary (HIGH confidence)
- [MIT Technology Review - AI Image Model Bias](https://www.technologyreview.com/2023/03/22/1070167/these-news-tool-let-you-see-for-yourself-how-biased-ai-image-models-are/)
- [MasterClass - 180-Degree Rule](https://www.masterclass.com/articles/understanding-the-180-degree-rule-in-cinematography)
- [Suite Studios - Framing the Narrative](https://blog.suitestudios.io/article/framing-the-narrative-how-composition-tells-a-story-in-film)
- [Videomaker - Cinematography 101](https://www.videomaker.com/article/c18/15419-cinematography-techniques/)
- [LTX Studio - AI Video Prompt Guide](https://ltx.studio/blog/ai-video-prompt-guide)

### Secondary (MEDIUM confidence)
- [Aiarty - Midjourney Cinematic Prompts](https://www.aiarty.com/midjourney-prompts/midjourney-cinematic-prompts.htm)
- [OpenArt - Midjourney Prompts for Cinematic](https://openart.ai/blog/post/midjourney-prompts-for-cinematic)
- [Venice AI - Camera Position Prompts](https://venice.ai/blog/5-tips-for-ai-image-generator-camera-position-prompts)
- [Aiarty - Stable Diffusion Negative Prompts](https://www.aiarty.com/stable-diffusion-prompts/stable-diffusion-negative-prompt.htm)
- [Civitai - Illustrious Prompting Guide](https://civitai.com/articles/10962/illustrious-prompting-guide-or-v01-or-generate-anime-art-with-ai)

### Project-Specific
- `modules/AppVideoWizard/docs/SHOT_CONTINUITY_IMPROVEMENT_PLAN.md` - Existing research on shot decomposition
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Current prompt pipeline (buildShotPrompt)

---

## 10. Metadata

**Confidence breakdown:**
- Root cause analysis: HIGH - Multiple academic and industry sources confirm training data bias
- Cinematography principles: HIGH - Established film theory, verified with multiple sources
- Prompt engineering solutions: MEDIUM-HIGH - Based on community best practices, some model-specific variation
- Implementation patterns: MEDIUM - Based on code review + research synthesis, needs validation

**Research date:** 2026-01-28
**Valid until:** 2026-04-28 (90 days - prompt engineering evolves with model updates)

---

## Summary Action Items

1. **Immediate:** Add anti-portrait negative prompts to all shot generation
2. **Immediate:** Prefix all shot prompts with ALL CAPS shot type directive
3. **Short-term:** Implement gaze direction templates per shot type
4. **Short-term:** Replace static descriptions with action verbs in prompt generation
5. **Medium-term:** Create action verb library mapped to scene types/emotions
6. **Medium-term:** Test and document model-specific keyword effectiveness
