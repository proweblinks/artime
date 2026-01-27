---
phase: 27-ui-performance-polish
plan: 02
subsystem: ui
tags: [blade, alpine.js, livewire, responsive, prompt-comparison]

# Dependency graph
requires:
  - phase: 26-llm-powered-expansion
    provides: LLM expansion with llm_metadata including method field
  - phase: 27-01
    provides: Prompt caching and hollywood_expansion_enabled toggle
provides:
  - Prompt comparison accordion component for shot cards
  - Word/char/token count display with expansion ratio
  - Responsive side-by-side layout on wide screens
  - Expansion method badge (AI or Template)
affects: [28-voice-production, ui-consistency]

# Tech tracking
tech-stack:
  added: []
  patterns: [accordion-toggle-pattern, responsive-flex-layout, expansion-metadata-passthrough]

key-files:
  created:
    - modules/AppVideoWizard/resources/views/livewire/partials/prompt-comparison.blade.php
  modified:
    - modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php
    - modules/AppVideoWizard/app/Services/ImageGenerationService.php

key-decisions:
  - "Accordion pattern: expanded prompt visible by default, toggle reveals original"
  - "Metadata passthrough via project attributes (_lastExpandedPrompt, _lastExpansionMethod)"
  - "Responsive breakpoint at 1200px for side-by-side layout"

patterns-established:
  - "Prompt comparison accordion: default shows expanded, toggle shows original"
  - "Expansion metadata flow: StructuredPromptBuilder -> ImageGenerationService -> VideoWizard -> Blade"

# Metrics
duration: 6min
completed: 2026-01-27
---

# Phase 27 Plan 02: Prompt Comparison UI Summary

**Accordion-style prompt comparison component with word/char/token counts, expansion ratio badge, and responsive side-by-side layout**

## Performance

- **Duration:** 6 min (371 seconds)
- **Started:** 2026-01-27T13:24:22Z
- **Completed:** 2026-01-27T13:30:33Z
- **Tasks:** 2
- **Files modified:** 4

## Accomplishments

- Created 235-line Blade partial for prompt comparison with Alpine.js toggle
- Word count badge shows: X -> Y words | X -> Y chars | ~X -> ~Y tokens
- Expansion ratio badge (e.g., "12x") and method badge (AI or Template)
- Responsive CSS: side-by-side on >1200px, stacked on narrow screens
- Integrated component into storyboard shot cards replacing compact prompt section
- Extended metadata flow to pass expandedPrompt and expansionMethod through the system

## Task Commits

Each task was committed atomically:

1. **Task 1: Create prompt comparison Blade partial** - `ca2dfd7` (feat)
2. **Task 2: Integrate into storyboard shot cards** - `c3fdb55` (feat)

## Files Created/Modified

- `modules/AppVideoWizard/resources/views/livewire/partials/prompt-comparison.blade.php` - 235-line accordion component with word counts, toggle, and responsive layout
- `modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php` - Replaced compact prompt section with @include for comparison component
- `modules/AppVideoWizard/app/Services/ImageGenerationService.php` - Store _lastExpandedPrompt and _lastExpansionMethod on project
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Read metadata from project and store in storyboard scene data

## Decisions Made

1. **Metadata passthrough via project attributes**
   - Store expansion metadata on project using setAttribute() in ImageGenerationService
   - Read in VideoWizard and persist to storyboard scene array
   - Avoids changing return signatures of existing methods

2. **Responsive breakpoint at 1200px**
   - Side-by-side layout only on wide screens (>1200px)
   - Stacked layout on narrower screens for mobile compatibility

3. **Default to template method**
   - When metadata not available, default expansionMethod to 'template'
   - Graceful degradation for existing data without metadata

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Prompt comparison UI complete and integrated
- Users can see before/after expansion with word count difference
- Ready for 27-03 (UI Polish) or Phase 28 (Voice Production)

---
*Phase: 27-ui-performance-polish*
*Completed: 2026-01-27*
