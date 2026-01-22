# 🎬 Video Wizard Working Mode - Entry Prompt

Copy and paste the text below to start a Video Wizard development session:

---

```
## Video Wizard Development Session

I want to work on the Video Wizard project in an organized manner using the GSD + Ralph Loop methodology.

**First, read these files to understand the current state:**
1. `VIDEO_WIZARD_MISSION.md` - Full project overview and goals
2. `.planning/STATE.md` - Current progress and what was done last session
3. `.planning/ROADMAP.md` - Development phases and milestones
4. `.planning/VIDEO_WIZARD_PROMPT.md` - Working patterns and protocols

**Project Context:**
- Artime Video Creation Wizard (Laravel/Livewire)
- Location: `modules/AppVideoWizard/`
- Goal: Hollywood-grade AI video production system

**Current Phase:** Milestone 1 - Stability & Bug Fixes (80% complete)

**Working Mode:**
1. Track tasks with TodoWrite
2. One commit per logical fix
3. Test after deployment
4. Update STATE.md at session end

**Session Goal:** [SPECIFY YOUR GOAL HERE OR SAY "Continue from last session"]

Let's begin.
```

---

## Quick Variations

### Continue Previous Work
```
Continue Video Wizard development from last session.
Read STATE.md and ROADMAP.md to see current progress.
Pick up the next pending task and proceed.
```

### Fix Specific Bug
```
Video Wizard bug fix session.
Issue: [DESCRIBE THE BUG]
Read VIDEO_WIZARD_MISSION.md for context.
Find the relevant code, fix it, test it.
```

### Add New Feature
```
Video Wizard feature development.
Feature: [DESCRIBE THE FEATURE]
Read VIDEO_WIZARD_MISSION.md for architecture overview.
Plan the implementation, then execute.
```

### Quick Fix Mode
```
Quick Video Wizard fix - don't need full context.
Just fix: [DESCRIBE THE ISSUE]
Commit and deploy when done.
```

---

## Ralph Loop Mode (Autonomous)

For longer autonomous sessions, use:

```bash
# In project directory
ralph-setup video-wizard-session
cd video-wizard-session

# Edit .ralph/PROMPT.md with:
"""
Implement NarrativeMomentService for Video Wizard.

Requirements:
1. Read VIDEO_WIZARD_MISSION.md for context
2. Create modules/AppVideoWizard/app/Services/NarrativeMomentService.php
3. Implement decomposeNarrationIntoMoments()
4. Implement extractEmotionalArc()
5. Add unit tests
6. Output <promise>COMPLETE</promise> when done

Test by running: php artisan test --filter=NarrativeMoment
"""

# Start autonomous loop
ralph --monitor --max-iterations 30
```

---

## GSD Commands Reference

| Command | Use When |
|---------|----------|
| `/gsd:progress` | Check current status |
| `/gsd:quick` | Quick fix without full planning |
| `/gsd:plan-phase 2` | Plan Milestone 2 (Narrative Intelligence) |
| `/gsd:execute-phase 1` | Execute remaining Milestone 1 tasks |
| `/gsd:verify-work` | Run verification after implementation |

---

*Save this file for quick access to enter Video Wizard development mode*
