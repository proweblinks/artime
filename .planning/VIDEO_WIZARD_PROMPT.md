# 🎬 Video Wizard Development Session

> **Mode:** GSD + Ralph Loop Hybrid
> **Project:** Artime Video Creation Wizard
> **Goal:** Transform into Hollywood-grade AI video production system

---

## 📋 Session Initialization

When you start this session, Claude should:

1. **Read the mission file:**
   ```
   Read: VIDEO_WIZARD_MISSION.md
   ```

2. **Check current state:**
   ```
   Read: .planning/STATE.md (if exists)
   Read: .planning/ROADMAP.md (if exists)
   ```

3. **Understand the codebase:**
   - `modules/AppVideoWizard/app/Livewire/VideoWizard.php`
   - `modules/AppVideoWizard/resources/views/livewire/modals/multi-shot.blade.php`
   - `modules/AppVideoWizard/app/Services/`

---

## 🎯 Current Objectives

### Immediate (This Session)
1. Verify recent bug fixes are working (dialogue, lip-sync, collage preview)
2. Create `NarrativeMomentService.php` for micro-moment decomposition
3. Modify `addBasicShotVariety()` to use unique moments per shot

### Short-term (This Week)
4. Add AI retry logic with exponential backoff
5. Implement emotion-driven shot type selection
6. Extract character portraits for consistency

### Medium-term (This Month)
7. Full Shot/Reverse Shot dialogue coverage
8. Emotional arc system with intensity curves
9. UI/UX polish and progress indicators

---

## 🔄 Working Loop Pattern

For each task:

```
1. IDENTIFY
   - What phase/task are we on?
   - What files need modification?
   - What's the expected outcome?

2. RESEARCH
   - Read relevant code sections
   - Understand current implementation
   - Identify dependencies

3. PLAN
   - Design the solution
   - Consider edge cases
   - Plan test approach

4. IMPLEMENT
   - Make focused changes
   - Keep changes atomic
   - Follow existing patterns

5. TEST
   - Commit changes
   - Push to repository
   - Deploy to cPanel
   - Verify in browser

6. ITERATE
   - Check Laravel logs for errors
   - Fix any issues found
   - Move to next task
```

---

## ⚡ Quick Commands

| Need | Action |
|------|--------|
| Check progress | Read `VIDEO_WIZARD_MISSION.md` |
| Fix a bug | Identify → Read code → Fix → Commit → Deploy → Test |
| Add feature | Plan → Implement → Test → Document |
| Deploy | `git add . && git commit && git push` then user deploys |

---

## 🎬 Hollywood Patterns to Implement

### Narrative Micro-Moments
```
INPUT: "Jack arrives in Shibuya, spots someone, chases them, loses them"
OUTPUT:
  Shot 1 (0.3): "Wide view of neon-lit Shibuya, Jack entering"
  Shot 2 (0.5): "Jack walks through market, scanning crowd"
  Shot 3 (0.7): "Jack's eyes widen, spotting familiar face"
  Shot 4 (0.8): "Jack pushes through crowd urgently"
  Shot 5 (0.6): "Jack stops, target gone, shoulders slump"
```

### Emotion → Shot Type
```php
if ($intensity >= 0.85) return 'extreme-close-up';
if ($intensity >= 0.7) return 'close-up';
if ($intensity >= 0.55) return 'medium-close';
if ($intensity >= 0.4) return 'medium';
if ($intensity >= 0.25) return 'wide';
return 'establishing';
```

---

## 📁 Key Files Reference

```
modules/AppVideoWizard/
├── app/Livewire/VideoWizard.php
│   ├── getDialogueForShot()        # Line ~20140
│   ├── addBasicShotVariety()       # Line ~16662
│   ├── decomposeSceneIntoStoryBeats() # Line ~16253
│   └── decomposeSceneWithDynamicEngine() # Line ~15900
├── app/Services/
│   ├── DynamicShotEngine.php       # Shot type logic
│   ├── DialogueSceneDecomposerService.php
│   └── NarrativeMomentService.php  # TO CREATE
└── resources/views/livewire/modals/
    └── multi-shot.blade.php        # Multi-shot UI
```

---

## ✅ Definition of Done

A task is complete when:
- [ ] Code changes committed with descriptive message
- [ ] No PHP errors in Laravel logs
- [ ] Feature works in browser testing
- [ ] No regressions in existing functionality

---

## 🚨 Constraints

1. **No breaking changes** - Existing projects must continue working
2. **Backwards compatible** - Old data structures must still function
3. **Performance** - Don't add excessive API calls
4. **Code style** - Match existing patterns in codebase

---

## 🔁 Session End Protocol

Before ending session:
1. Commit all pending changes
2. Push to repository
3. Update `STATE.md` with current progress
4. Note any blockers or next steps

---

## 💬 Communication Style

- Be direct and technical
- Show code snippets when relevant
- Explain reasoning briefly
- Test thoroughly before declaring done
- Ask for deployment when changes are ready

---

*Enter this mode by saying: "Let's work on Video Wizard"*
*Exit with: "Save state and end session"*
