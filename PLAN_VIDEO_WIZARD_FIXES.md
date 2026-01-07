# Video Wizard - Comprehensive Fix Plan for Steps 2 & 3

## Analysis Summary

After comparing the original video-creation-wizard (ytseo.siteuo.com) with the current PHP/Livewire implementation (artime.ai), I've identified the following missing features and discrepancies.

---

## STEP 2: CONCEPT (Niche & Style)

### Currently Implemented ✅
- "What's your video about?" textarea
- "Enhance with AI" button
- Style Inspiration (Optional) field
- Things to Avoid (Optional) field
- "Generate Unique Ideas" button
- Concept variations display with selection
- "Generate Different Concepts" button
- Context bar showing production type/subtype

### Missing Features ❌

#### 1. Character Intelligence Section
**Priority: HIGH**

The original has a complete "Character Intelligence" panel below the concept section:

```
┌─────────────────────────────────────────────────────────────┐
│ 👥 Character Intelligence                              [✓] │
│ AI-suggested based on your production type                  │
│                                                             │
│ NARRATION STYLE                                             │
│ ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐   │
│ │ 🎙️        │ │ 💬        │ │ 📖        │ │ 🔇        │   │
│ │ Voiceover │ │ Dialogue  │ │ Narrator  │ │ No Voice  │   │
│ │           │ │ (crossed) │ │           │ │           │   │
│ └───────────┘ └───────────┘ └───────────┘ └───────────┘   │
│                                                             │
│ CHARACTER COUNT    SUGGESTED: 4                             │
│ ════════════════●════════════════════════════════════ 4     │
│                                                             │
│ Minimum 2 characters recommended                            │
└─────────────────────────────────────────────────────────────┘
```

**Required Changes:**

**A. Add new properties to VideoWizard.php:**
```php
// Character Intelligence
public array $characterIntelligence = [
    'enabled' => true,
    'narrationStyle' => 'voiceover', // voiceover, dialogue, narrator, none
    'characterCount' => 4,
    'suggestedCount' => 4,
];
```

**B. Add UI component to concept.blade.php:**
- Checkbox to enable/disable Character Intelligence
- Narration style selector (4 options with icons)
- Character count slider with suggested value indicator
- Helper text showing recommended minimum

**C. Add backend logic:**
- Method to calculate suggested character count based on production type
- Save characterIntelligence to project content_config

---

## STEP 3: SCRIPT

### Currently Implemented ✅
- Concept summary card
- Script Tone selector (4 options)
- Content Depth selector (4 options)
- Additional Instructions textarea
- "Generate Script with AI" button
- Script results with scenes display
- Hook and CTA sections
- Regenerate button

### Missing Features ❌

#### 1. Script Header Bar with Stats
**Priority: HIGH**

The original shows a stats bar above the scenes:
```
┌──────────────────────────────────────────────────────────────────┐
│ VISUAL TIME    NARRATION    PER SCENE                            │
│ 3m 22s         2s           ~101s                    ⚖️ Balanced │
└──────────────────────────────────────────────────────────────────┘
```

**Required Changes:**
- Add computed stats: total visual time, narration duration, per-scene average
- Display "Balanced pacing" indicator

---

#### 2. Voice & Dialogue Status Panel
**Priority: HIGH**

```
┌──────────────────────────────────────────────────────────────────┐
│ 🎙️ Voice & Dialogue Status                    [9 voice pending] │
│                                                                   │
│ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐    │
│ │     0      │ │     0      │ │     0      │ │     0      │    │
│ │ Dialogue   │ │  Speakers  │ │   Voices   │ │ Scenes w/  │    │
│ │   Lines    │ │            │ │   Mapped   │ │  Dialogue  │    │
│ └────────────┘ └────────────┘ └────────────┘ └────────────┘    │
└──────────────────────────────────────────────────────────────────┘
```

**Required Changes:**

**A. Add new properties to VideoWizard.php:**
```php
public array $voiceStatus = [
    'dialogueLines' => 0,
    'speakers' => 0,
    'voicesMapped' => 0,
    'scenesWithDialogue' => 0,
    'pendingVoices' => 0,
];
```

**B. Add method to calculate voice stats from script scenes**

**C. Add UI panel to script.blade.php**

---

#### 3. Full Script View Button
**Priority: MEDIUM**

Original has "📄 Full Script" button in header that opens full script view.

**Required Changes:**
- Add modal or expandable section for full script view
- Include all narration text concatenated

---

#### 4. Scene Cards with Advanced Editing
**Priority: HIGH**

The original scene cards are expandable and contain multiple sections:

```
┌──────────────────────────────────────────────────────────────────┐
│ ● 🎵 Music only (no voiceover)                              ▲   │
│ │  10s  • cut • neutral │ [lo_res_2mb]                       │  │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │ 🖼️ Visual Prompt                            [Write for SR] │  │
│ │ Describe the visual scene for AI video generation. Include │  │
│ │ camera movements, lighting, subject...                     │  │
│ │                                                            │  │
│ │ [Visual description text...]                               │  │
│ │                                                            │  │
│ └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│ │ Her eyes snap open, fixed on something beyond the mountain │  │
│ │ range. Her hand moves to the jade pendant at her throat... │  │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │ 🎙️ Voiceover                               [Write for MR44] │  │
│ │ 🎵 Music/ambient only - no voiceover for this scene        │  │
│ │ ───────────────────────────────────────────────────────────│  │
│ │ Note: No narration, moments of falling visual thunder      │  │
│ └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│ ⏱️ Duration (seconds)  │ 📋 Transition                          │
│ ┌──────────────┐       │ ┌────────────────────────────────┐    │
│ │     101      │       │ │ Cut                         ▼ │    │
│ └──────────────┘       │ └────────────────────────────────┘    │
│                                                                  │
│             [🔄 Regenerate]        [↑] [↓] [🗑️]                 │
└──────────────────────────────────────────────────────────────────┘
```

**Required Changes:**

**A. Scene structure update in script array:**
```php
// Each scene should have:
[
    'id' => 'scene_1',
    'title' => 'Scene 1',
    'narration' => '...',
    'visualDescription' => '...',
    'visualPrompt' => '...',  // NEW: AI-generated visual prompt
    'voiceover' => [          // NEW: Voiceover settings
        'enabled' => false,   // "Music only" toggle
        'text' => '',
        'voiceId' => null,
        'status' => 'pending', // pending, generated, error
    ],
    'duration' => 15,
    'transition' => 'cut',    // NEW: cut, dissolve, fade, wipe
    'mood' => 'neutral',      // NEW: Scene mood
    'status' => 'draft',      // draft, ready, generating
]
```

**B. Add Livewire methods:**
```php
public function toggleSceneMusicOnly(int $sceneIndex): void
public function updateSceneDuration(int $sceneIndex, int $duration): void
public function updateSceneTransition(int $sceneIndex, string $transition): void
public function updateSceneVisualPrompt(int $sceneIndex, string $prompt): void
public function updateSceneVoiceover(int $sceneIndex, string $text): void
public function regenerateScene(int $sceneIndex): void
public function reorderScene(int $sceneIndex, string $direction): void // 'up' or 'down'
public function deleteScene(int $sceneIndex): void
public function addScene(): void
public function writeVisualPromptForScene(int $sceneIndex): void // AI-generates visual prompt
public function writeVoiceoverForScene(int $sceneIndex): void    // AI-generates voiceover text
```

**C. Update script.blade.php with expandable scene cards**

---

#### 5. Add Scene Button
**Priority: MEDIUM**

```
┌──────────────────────────────────────────────────────────────────┐
│                        [+ Add Scene]                             │
└──────────────────────────────────────────────────────────────────┘
```

**Required Changes:**
- Add button below scenes list
- Method to create new blank scene with defaults

---

## Implementation Order (Recommended)

### Phase 1: Data Structure Updates
1. Update `script` array structure in VideoWizard.php
2. Add `characterIntelligence` property
3. Add `voiceStatus` property
4. Update scene structure with new fields

### Phase 2: Step 2 (Concept) UI
1. Add Character Intelligence section to concept.blade.php
2. Add narration style selector
3. Add character count slider
4. Add backend calculation methods

### Phase 3: Step 3 (Script) UI - Header & Stats
1. Add script stats bar (visual time, narration, per-scene)
2. Add Voice & Dialogue Status panel
3. Add Full Script view button/modal

### Phase 4: Step 3 (Script) UI - Scene Cards
1. Create expandable scene card component
2. Add "Music only" toggle
3. Add Visual Prompt section with "Write for SR" button
4. Add Voiceover section with "Write for MR" button
5. Add Duration input field
6. Add Transition dropdown
7. Add Regenerate, Reorder, Delete buttons
8. Add "+ Add Scene" button

### Phase 5: Backend Logic
1. Implement AI visual prompt generation
2. Implement AI voiceover text generation
3. Implement scene regeneration
4. Implement voice status calculation
5. Connect to voiceover generation service

---

## Files to Modify

1. **modules/AppVideoWizard/app/Livewire/VideoWizard.php**
   - Add new properties
   - Add new methods

2. **modules/AppVideoWizard/resources/views/livewire/steps/concept.blade.php**
   - Add Character Intelligence section

3. **modules/AppVideoWizard/resources/views/livewire/steps/script.blade.php**
   - Add stats bar
   - Add Voice & Dialogue Status panel
   - Rewrite scene cards with full editing features

4. **modules/AppVideoWizard/app/Services/ScriptGenerationService.php**
   - Add visual prompt generation method
   - Add voiceover text generation method
   - Add single scene regeneration method

5. **config/appvideowizard.php**
   - Add transition types configuration
   - Add narration style options

---

## Estimated Complexity

| Feature | Complexity | Time Estimate |
|---------|------------|---------------|
| Character Intelligence UI | Medium | 2-3 hours |
| Voice & Dialogue Status | Medium | 1-2 hours |
| Script Stats Bar | Low | 1 hour |
| Expandable Scene Cards | High | 4-6 hours |
| Scene Editing Methods | Medium | 2-3 hours |
| AI Prompt Generation | Medium | 2-3 hours |
| Full Script View | Low | 1 hour |

**Total Estimated Time: 13-19 hours**

---

## Questions for User Clarification

1. Should the "Write for SR" button call a specific AI service or use the existing Gemini integration?
2. Should voiceover generation be integrated with the existing VoiceoverService?
3. Are there specific transition effects that should be available (Cut, Dissolve, Fade, Wipe)?
4. Should Character Intelligence affect the script generation prompt?
5. What voice options should be available for mapping to speakers?
