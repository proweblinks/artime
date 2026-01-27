---
phase: 27-ui-performance-polish
created: 2026-01-27
status: ready-to-plan
---

# Phase 27: UI & Performance Polish — Context

**Goal:** Users can preview, compare, and efficiently use expanded prompts

**Requirements:**
- INF-05: Prompt caching for performance (avoid re-expanding identical contexts)
- INF-06: Prompt comparison view in UI (before/after expansion, word count)

**Success Criteria:**
1. Identical contexts return cached prompts without re-processing
2. UI shows before/after prompt comparison with word count difference visible
3. Prompt expansion toggle available in settings

---

## Decisions Made

### 1. Comparison UI Layout

**Decision:** Expandable accordion with responsive side-by-side

**Details:**
- Default view shows expanded (Hollywood) prompt only
- "Show original" toggle reveals the brief original prompt
- On wide screens (>1200px), expanded state displays side-by-side columns
- On narrow screens, stacked vertical layout
- Keeps storyboard UI clean while providing comparison when needed

**Rationale:** Storyboard is already information-dense. Users care about the expanded prompt 90% of the time. Accordion pattern follows GitHub diff view conventions.

### 2. Expansion Toggle Placement

**Decision:** Global setting only

**Details:**
- Single toggle in Video Wizard settings panel
- Affects all shots uniformly
- When OFF: Template-only expansion (faster, simpler prompts)
- When ON: Complex shots route through LLM expansion (Hollywood quality)

**Rationale:** Simpler mental model. Per-shot toggles add cognitive load without proportional benefit. Users who want faster generation can disable globally; those who want quality keep it on.

### 3. Word Count Display

**Decision:** Detailed breakdown with words, characters, and tokens

**Details:**
- Format: `50 → 650 words | 320 → 4,200 chars | ~12 → ~180 tokens`
- Displayed as compact text near the prompt
- Token count uses rough estimation (words × 1.3)
- Helps users understand CLIP 77-token limit implications

**Rationale:** Technical users appreciate seeing the full picture. Token count is particularly useful since CLIP models have strict 77-token limits — users can see when compression is needed.

---

## Gray Areas NOT Discussed (Default Behavior)

### Cache Behavior

**Default approach:**
- Cache key: MD5 hash of shot data + Story Bible context
- TTL: 24 hours (matches LLMExpansionService existing pattern)
- Scope: Per-shot (each shot cached independently)
- No manual clear controls in UI (cache expires naturally)
- Cache stored in Laravel's default cache driver

---

## Implementation Notes

### UI Components Needed

1. **Prompt comparison component** (accordion style)
   - Blade partial: `prompt-comparison.blade.php`
   - Shows expanded prompt by default
   - "Show original" toggle for before/after
   - Responsive: side-by-side on wide, stacked on narrow

2. **Word count badge component**
   - Inline display near prompt
   - Shows words → words | chars → chars | ~tokens → ~tokens
   - Subtle styling (muted text, small font)

3. **Settings toggle**
   - Add to Video Wizard settings panel
   - Label: "Hollywood prompt expansion"
   - Description: "Enable AI-enhanced prompts for complex shots"
   - Default: ON

### Caching Strategy

- Use existing Laravel Cache facade
- Cache at StructuredPromptBuilderService level
- Key format: `prompt_cache:{md5(json_encode($options))}`
- Only cache LLM-expanded results (template results are fast enough)

### Integration Points

- `StructuredPromptBuilderService::buildHollywoodPrompt()` — add cache check/store
- `storyboard.blade.php` — add comparison component to shot cards
- Video Wizard settings panel — add expansion toggle
- `VideoWizard.php` — read toggle setting, pass to prompt builder

---

*Context gathered: 2026-01-27*
*Ready for: /gsd:plan-phase 27*
