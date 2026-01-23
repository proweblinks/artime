# Requirements: Video Wizard - Scene Text Inspector

**Defined:** 2026-01-23
**Core Value:** Full transparency into scene text, prompts, and metadata

## v1 Requirements

Requirements for Milestone 7. Each maps to roadmap phases.

### Speech Segment Display

- [x] **SPCH-01**: User can view ALL speech segments for a scene (not truncated) ✓
- [x] **SPCH-02**: Each segment shows correct type label (NARRATOR/DIALOGUE/INTERNAL/MONOLOGUE) ✓
- [x] **SPCH-03**: Each segment shows type-specific icon (🎙️/💬/💭/🗣️) ✓
- [x] **SPCH-04**: Each segment shows speaker name (if applicable) ✓
- [x] **SPCH-05**: Each segment shows lip-sync indicator (YES for dialogue/monologue, NO for narrator/internal) ✓
- [x] **SPCH-06**: Each segment shows estimated duration ✓
- [x] **SPCH-07**: Speaker matched to Character Bible shows character indicator ✓

### Prompts Display

- [x] **PRMT-01**: User can view full image prompt (not truncated) ✓
- [x] **PRMT-02**: User can view full video prompt (not truncated) ✓
- [x] **PRMT-03**: User can copy image prompt to clipboard with one click ✓
- [x] **PRMT-04**: User can copy video prompt to clipboard with one click ✓
- [x] **PRMT-05**: Shot type badge displayed with prompt ✓
- [x] **PRMT-06**: Camera movement indicator displayed ✓

### Scene Metadata

- [x] **META-01**: User can view scene duration ✓
- [x] **META-02**: User can view scene transition type ✓
- [x] **META-03**: User can view scene location ✓
- [x] **META-04**: User can view characters present in scene ✓
- [x] **META-05**: User can view emotional intensity indicator ✓
- [x] **META-06**: Climax scenes show climax badge ✓

### Modal UX

- [x] **MODL-01**: User can open inspector from scene card (🔍 Inspect button) ✓
- [x] **MODL-02**: Modal shows scene number and title ✓
- [x] **MODL-03**: Modal content is scrollable for long scenes ✓
- [x] **MODL-04**: Modal has close button ✓
- [x] **MODL-05**: Modal works on mobile (responsive) ✓

### Scene Card Fixes

- [x] **CARD-01**: Scene card shows dynamic label based on segment types present (not hardcoded "Dialogue") ✓
- [x] **CARD-02**: Scene card shows type-specific icons for segments ✓
- [x] **CARD-03**: Scene card indicates "click to view all" when truncated ✓

## Future Requirements

Deferred to later milestones.

### Advanced Features

- **ADVN-01**: Click speech segment to jump to timeline position
- **ADVN-02**: Edit speech segment inline in inspector
- **ADVN-03**: Prompt version history
- **ADVN-04**: Drawer/side-panel option instead of modal

## Out of Scope

Explicitly excluded.

| Feature | Reason |
|---------|--------|
| Inline editing of prompts | Separate edit flow exists, inspector is read-only |
| Regenerate from inspector | Keep inspector focused on viewing |
| Export prompts to file | Copy-to-clipboard covers this use case |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| SPCH-01 | Phase 8 | Complete |
| SPCH-02 | Phase 8 | Complete |
| SPCH-03 | Phase 8 | Complete |
| SPCH-04 | Phase 8 | Complete |
| SPCH-05 | Phase 8 | Complete |
| SPCH-06 | Phase 8 | Complete |
| SPCH-07 | Phase 8 | Complete |
| PRMT-01 | Phase 9 | Complete |
| PRMT-02 | Phase 9 | Complete |
| PRMT-03 | Phase 9 | Complete |
| PRMT-04 | Phase 9 | Complete |
| PRMT-05 | Phase 9 | Complete |
| PRMT-06 | Phase 9 | Complete |
| META-01 | Phase 7 | Complete |
| META-02 | Phase 7 | Complete |
| META-03 | Phase 7 | Complete |
| META-04 | Phase 7 | Complete |
| META-05 | Phase 7 | Complete |
| META-06 | Phase 7 | Complete |
| MODL-01 | Phase 7 | Complete |
| MODL-02 | Phase 7 | Complete |
| MODL-03 | Phase 7 | Complete |
| MODL-04 | Phase 7 | Complete |
| MODL-05 | Phase 10 | Complete |
| CARD-01 | Phase 7 | Complete |
| CARD-02 | Phase 7 | Complete |
| CARD-03 | Phase 7 | Complete |

**Coverage:**
- v1 requirements: 28 total
- Mapped to phases: 28 (100%)
- Unmapped: 0

**Phase distribution:**
- Phase 7 (Foundation): 14 requirements (MODL-01 to MODL-04, CARD-01 to CARD-03, META-01 to META-06)
- Phase 8 (Speech Segments): 7 requirements (SPCH-01 to SPCH-07)
- Phase 9 (Prompts + Copy): 6 requirements (PRMT-01 to PRMT-06)
- Phase 10 (Mobile + Polish): 1 requirement (MODL-05)

---
*Requirements defined: 2026-01-23*
*Last updated: 2026-01-23 after Phase 10 completion (28/28 requirements complete)*
*Milestone 7 complete: 2026-01-23*
