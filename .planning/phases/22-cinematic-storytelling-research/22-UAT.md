---
status: complete
phase: 22-cinematic-storytelling-research
source: [22-01-SUMMARY.md, 22-02-SUMMARY.md, 22-03-SUMMARY.md]
started: 2026-01-28T16:00:00Z
updated: 2026-01-28T16:00:00Z
---

## Current Test

[testing complete]

## Tests

### 1. Anti-Portrait Negative Prompts Active
expected: Generate a new image for any scene. Character(s) should NOT look directly at camera - should be engaged with scene/other characters, not posing like a portrait.
result: pass
notes: User confirmed "images now are not of a character looking at the camera" - individual shot quality improved.

### 2. Gaze Direction Varies by Shot Type
expected: Generate images using different shot types (e.g., "close-up" vs "medium"). Close-ups should show intense off-screen gaze. Medium shots should show engagement with other characters or objects.
result: skipped
reason: Deferred - broader continuity issue identified requiring new research phase

### 3. Action Verbs in Prompts
expected: Generate an image and inspect the prompt (or observe the result). Characters should appear DOING something (reaching, examining, reacting) rather than standing/posing statically.
result: skipped
reason: Deferred - broader continuity issue identified requiring new research phase

### 4. Dialogue Scene Has Conversational Gaze
expected: Generate an image for a dialogue scene with multiple characters. Characters should be looking at each other (engaged in conversation), not at camera.
result: skipped
reason: Deferred - broader continuity issue identified requiring new research phase

### 5. Establishing Shot Has No Subject Gaze
expected: Generate an establishing or wide shot. Since these focus on environment, there should be no awkward character gaze - environment dominates the frame.
result: skipped
reason: Deferred - broader continuity issue identified requiring new research phase

## Summary

total: 5
passed: 1
issues: 0
pending: 0
skipped: 4

## Gaps

[none - Phase 22 achieved its goal of improving individual shot quality. Shot-to-shot continuity is a separate concern requiring Phase 23]

## User Feedback (Critical Discovery)

User identified that while Phase 22 fixed individual shot quality (no portrait poses), the shots lack:
- Logical connection between sequential shots
- Spatial continuity (180° rule, consistent screen positions)
- Eyeline matching across cuts
- Action continuity (movement continuation)
- Deliberate shot progression serving emotional arc

This is the difference between "good individual frames" and "Hollywood scene construction."

**Action:** New Phase 23 created for Scene-Level Shot Continuity research, including investigation of existing admin panel settings that may relate to this infrastructure.
