# 🎬 Video Wizard Mission Control

## Project Overview

**Product:** Artime Video Creation Wizard
**Stack:** Laravel/Livewire, Blade, Alpine.js, PHP 8.x
**AI Integrations:** Gemini, NanoBanana, HIDream, Minimax, Multitalk, Kling AI
**Repository:** artime/modules/AppVideoWizard

---

## 🎯 Vision

Transform the Video Wizard into a **Hollywood-grade AI video production system** that creates cinematic content with:
- Professional shot sequencing (Shot/Reverse Shot patterns)
- Character consistency across all shots
- Emotional arc-driven cinematography
- Intelligent dialogue scene handling
- Seamless multi-shot decomposition

---

## 🔥 Current Pain Points

### Critical Bugs (P0)
1. **Dialogue Distribution** - Dialogue not properly parsed by speaker, sentences split incorrectly
2. **Lip-Sync Flags** - `needsLipSync` not set on all speaking shots
3. **Collage Preview Empty** - Shot images not displayed when no collage exists
4. **Duplicate Methods** - Code duplication causing PHP fatal errors

### Major Issues (P1)
5. **Identical Shot Descriptions** - All shots get same IMAGE PROMPT instead of unique moments
6. **Character Inconsistency** - Different faces across shots (should be same character)
7. **Flat Emotional Arc** - No intensity variation, all shots same framing
8. **AI Decomposition Failures** - Story beat extraction fails silently, no retry

### UX Issues (P2)
9. **Hidden Dialogue Text** - Monologue/dialogue text not visible in shot cards
10. **Missing Shot Type Labels** - Hard to identify shot types at a glance
11. **No Progress Indicators** - Users don't know what's generating

---

## 🏗️ Architecture Components

```
modules/AppVideoWizard/
├── app/
│   ├── Livewire/
│   │   └── VideoWizard.php          # Main 25k+ line component
│   └── Services/
│       ├── DialogueSceneDecomposerService.php
│       ├── DynamicShotEngine.php
│       ├── NarrativeMomentService.php
│       ├── SmartReferenceService.php
│       └── GeminiService.php
├── resources/views/livewire/
│   ├── video-wizard.blade.php
│   └── modals/
│       └── multi-shot.blade.php
└── config/
```

### Key Methods in VideoWizard.php
| Method | Purpose | Status |
|--------|---------|--------|
| `getDialogueForShot()` | Parse dialogue by speaker | ✅ Fixed |
| `decomposeSceneWithDynamicEngine()` | Multi-shot decomposition | ⚠️ Needs work |
| `addBasicShotVariety()` | Apply shot type variety | ⚠️ Needs moments |
| `decomposeSceneIntoStoryBeats()` | AI story extraction | ⚠️ Needs retry |
| `extractCollageQuadrantsToShots()` | Collage → shots | ⚠️ Needs portraits |
| `generateShotImage()` | Single shot generation | ✅ Working |

---

## 📋 Development Phases

### Phase 1: Bug Fixes & Stability
- [x] Fix `getDialogueForShot()` dialogue parsing
- [x] Fix `needsLipSync` flag on all dialogue shots
- [x] Fix Collage Preview to show shot images
- [x] Remove duplicate method declarations
- [ ] Add retry logic to AI decomposition
- [ ] Fix error handling in shot generation

### Phase 2: Narrative Micro-Moments
- [ ] Create `NarrativeMomentService.php`
- [ ] Decompose narration into distinct moments per shot
- [ ] Extract emotional intensity per moment
- [ ] Map intensity → shot type (Hollywood pattern)
- [ ] Ensure each shot has UNIQUE visual description

### Phase 3: Character Consistency
- [ ] Extract character portraits from collage
- [ ] Store portraits in Character Bible
- [ ] Pass character reference to shot generation
- [ ] Validate same face across all shots

### Phase 4: Dialogue Scene Excellence
- [ ] Implement Shot/Reverse Shot pattern
- [ ] Add OTS (Over-the-Shoulder) shot detection
- [ ] Create reaction shot placeholders
- [ ] Support two-character dialogue coverage

### Phase 5: Emotional Arc System
- [ ] Build intensity curve extraction
- [ ] Map emotions to shot types:
  - 0.0-0.3: Wide/Establishing
  - 0.3-0.5: Medium
  - 0.5-0.7: Medium Close-up
  - 0.7-0.9: Close-up
  - 0.9-1.0: Extreme Close-up
- [ ] Ensure climax moments get tightest framing

### Phase 6: UI/UX Polish
- [ ] Show dialogue text in shot cards
- [ ] Add shot type badges with colors
- [ ] Progress indicators for generation
- [ ] Live preview updates

---

## 🎬 Hollywood Cinematography Reference

### Shot/Reverse Shot Pattern
```
TWO-SHOT (establish) → OTS Speaker A → OTS Speaker B → CU (peak) → REACTION
```

### Emotional Intensity Mapping
| Intensity | Shot Type | Use Case |
|-----------|-----------|----------|
| 0.0-0.2 | Extreme Wide | Location establish |
| 0.2-0.4 | Wide | Full scene context |
| 0.4-0.6 | Medium | Standard dialogue |
| 0.6-0.8 | Medium Close-up | Emotional engagement |
| 0.8-1.0 | Close-up/XCU | Climax moments |

### Key Rules
1. **First shot** = Establishing (unless very short scene)
2. **Last shot** = Character-centric for animation
3. **Dialogue scenes** = Shot/Reverse Shot coverage
4. **Emotional peaks** = Tightest framing
5. **Each shot** = Unique micro-moment, never duplicates

---

## 🔧 Technical Specifications

### Speech Types & Lip-Sync
| Speech Type | Lips Move | Video Model |
|-------------|-----------|-------------|
| `narrator` | No | Minimax |
| `internal` | No | Minimax |
| `monologue` | Yes | Multitalk |
| `dialogue` | Yes | Multitalk |

### Shot Structure
```php
[
    'type' => 'close-up',
    'duration' => 5,
    'uniqueVisualDescription' => 'Unique moment description',
    'emotionalIntensity' => 0.75,
    'needsLipSync' => true,
    'dialogue' => 'Character line here',
    'selectedVideoModel' => 'multitalk',
    'imageUrl' => null,
    'videoUrl' => null,
]
```

---

## 🚀 Success Criteria

### Quality Metrics
- [ ] Zero duplicate shot descriptions in any scene
- [ ] Same character face across all shots (visual consistency)
- [ ] Lip-sync on 100% of dialogue/monologue shots
- [ ] Emotional arc visible in shot type progression
- [ ] All AI operations have retry with fallback

### Performance Targets
- Scene decomposition: < 10 seconds
- Shot image generation: < 30 seconds each
- Full scene (8 shots): < 5 minutes total

---

## 📁 Key Files to Modify

| File | Priority | Changes Needed |
|------|----------|----------------|
| `VideoWizard.php` | HIGH | Moment decomposition, retry logic |
| `multi-shot.blade.php` | HIGH | UI fixes, shot display |
| `DynamicShotEngine.php` | MEDIUM | Emotion-based shot selection |
| `NarrativeMomentService.php` | HIGH | New service creation |
| `DialogueSceneDecomposerService.php` | MEDIUM | Enhance dialogue patterns |

---

## 🎯 Immediate Next Actions

1. **Test current fixes** - Verify dialogue/lip-sync/collage fixes work
2. **Create NarrativeMomentService** - Core decomposition logic
3. **Modify addBasicShotVariety()** - Use moments instead of single action
4. **Add AI retry logic** - Exponential backoff with quality validation
5. **Implement emotion-driven shots** - Intensity → shot type mapping

---

## 💡 Working Mode

When entering Video Wizard development:

1. **Load Context:**
   ```
   Read: VIDEO_WIZARD_MISSION.md
   Read: modules/AppVideoWizard/app/Livewire/VideoWizard.php (key methods)
   Read: Current phase tasks
   ```

2. **Before Any Change:**
   - Identify which phase/task
   - Read relevant code sections
   - Plan approach
   - Test after implementation

3. **Commit Pattern:**
   - One commit per logical fix
   - Include Co-Authored-By
   - Test before pushing

4. **Verification:**
   - Deploy to cPanel
   - Test in browser
   - Check Laravel logs

---

*Last Updated: 2026-01-22*
*Mission Status: Active Development*
