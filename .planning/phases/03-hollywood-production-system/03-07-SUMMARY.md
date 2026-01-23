# Phase 3 Plan 7: Smart Defaults from Concept Summary

> Smart defaults auto-configure Step 1 settings (platform, duration, pacing) by analyzing concept keywords with optional AI enhancement

---

## Frontmatter

```yaml
phase: 03
plan: 07
subsystem: wizard-configuration
tags: [smart-defaults, concept-analysis, auto-config, ai-enhanced]

dependency-graph:
  requires: [03-04, 03-05]
  provides: [concept-based-settings-suggestions]
  affects: [step-1-configuration, user-experience]

tech-stack:
  patterns: [keyword-matching, word-count-heuristics, ai-fallback]

key-files:
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

decisions:
  - keyword-first-ai-optional: Use fast keyword matching by default, AI as enhancement option
  - overwrite-parameter: Allow user control over whether to overwrite existing settings
  - platform-aspect-ratio-mapping: Auto-set aspect ratio based on platform selection
  - word-count-duration-heuristic: Short concept (< 20 words) = 30s, long (100+) = 180s

metrics:
  duration: ~4 minutes
  completed: 2026-01-23
```

---

## What Was Built

### 1. Concept Analysis Method (analyzeConceptForDefaults)

Pattern-based keyword matching for intelligent setting suggestions:

**Platform Detection:**
- `tiktok` keywords: viral, trend, short form, quick, snappy, 15/30/60 second
- `youtube` keywords: tutorial, documentary, long form, in-depth, comprehensive
- `instagram` keywords: reel, story, aesthetic, lifestyle, fashion
- `linkedin` keywords: professional, business, corporate, b2b
- `twitter` keywords: x.com, breaking, news, announcement

**Duration Detection:**
- Word count heuristic: < 20 words = 30s, < 50 = 60s, < 100 = 120s, 100+ = 180s
- Explicit indicators override: "quick", "brief" = short; "documentary", "in-depth" = long

**Production Type Detection:**
- commercial, narrative, documentary, music_video, tutorial, vlog

**Visual Mode Detection:**
- cinematic-realistic, stylized-animation, mixed-hybrid

**Pacing Detection:**
- fast, balanced, contemplative

### 2. Apply Suggestions Method (applySuggestedSettings)

Applies detected settings to wizard properties:
- `$platform` - Platform selection
- `$aspectRatio` - Auto-set based on platform (9:16 for TikTok/Instagram, 16:9 for YouTube/LinkedIn)
- `$targetDuration` - Duration in seconds
- `$productionType` - Production type
- `$content['visualMode']` - Visual style
- `$content['pacing']` - Pacing preference

**Overwrite Parameter:** When false, respects existing user choices (default behavior).

### 3. Refresh Suggestions Method (refreshSuggestedSettings)

UI-callable method for manual re-analysis with overwrite enabled.

### 4. AI-Enhanced Analysis (analyzeConceptWithAI)

Optional Gemini-powered analysis for more accurate suggestions:
- Uses GeminiService for analysis
- Falls back to keyword matching on failure
- Extracts JSON from various response formats (direct, markdown code block, bare object)

### 5. Integration Hook

Auto-applies suggestions when concept is enhanced:
- Added to `enhanceConcept()` method after concept update
- Uses `overwrite: false` to respect user choices

---

## Key Methods Added

| Method | Purpose | Location |
|--------|---------|----------|
| `analyzeConceptForDefaults(string $concept): array` | Keyword-based setting detection | Line 2950 |
| `applySuggestedSettings(bool $overwrite = false): void` | Apply suggestions to properties | Line 3103 |
| `refreshSuggestedSettings(): void` | Manual UI refresh trigger | Line 3157 |
| `analyzeConceptWithAI(): void` | AI-enhanced analysis option | Line 3177 |
| `extractJsonFromResponse(string $response): ?array` | Parse AI JSON responses | Line 3237 |

---

## Property Added

```php
public array $suggestedSettings = [];
```

Stores the analysis results for UI display and debugging.

---

## Deviations from Plan

None - plan executed exactly as written.

---

## Decisions Made

| Decision | Rationale |
|----------|-----------|
| Keyword-first, AI-optional | Fast response for common cases, AI for complex concepts |
| Overwrite parameter default false | Respect user's manual configuration choices |
| Platform -> Aspect Ratio mapping | TikTok/Instagram are 9:16, YouTube/LinkedIn are 16:9 |
| Word count heuristic | Simple proxy for concept complexity |
| extractJsonFromResponse helper | AI responses vary in format |

---

## Verification

- [x] `analyzeConceptForDefaults()` method exists with keyword patterns
- [x] `applySuggestedSettings()` method exists with overwrite logic
- [x] `refreshSuggestedSettings()` method exists for UI refresh
- [x] `analyzeConceptWithAI()` method exists using GeminiService
- [x] `extractJsonFromResponse()` helper handles various formats
- [x] Integration hook added in `enhanceConcept()` method
- [x] `$suggestedSettings` property added
- [x] Confidence tracking in suggestions array

---

## Commits

| Hash | Type | Description |
|------|------|-------------|
| `9acd783` | feat | Add smart defaults from concept analysis |

---

## Next Phase Readiness

**Completed:** Phase 3 Plan 7 - Smart Defaults from Concept

**Phase 3 Status:** 6/7 plans complete (01, 02, 03, 04, 05, 07)

**Remaining:** Plan 06 - Batch Generation Progress UI

---

*Completed: 2026-01-23*
