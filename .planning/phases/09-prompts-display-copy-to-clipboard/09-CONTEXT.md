# Phase 9: Prompts Display + Copy-to-Clipboard - Context

**Gathered:** 2026-01-23
**Status:** Ready for planning

<domain>
## Phase Boundary

Display full image and video prompts in the Scene Text Inspector modal with one-click copy functionality. Users can view complete prompt text (not truncated), see shot type badges and camera movement indicators, and copy prompts to clipboard with visual feedback.

</domain>

<decisions>
## Implementation Decisions

### Claude's Discretion

User trusts Claude to make all implementation decisions for this phase, following established patterns from:
- Existing storyboard prompt display patterns
- Phase 8 speech segments display (type badges, section headers)
- Phase 7 metadata display (badge styling, visual hierarchy)

**Specific areas for Claude to decide:**
- Prompt layout (expandable sections vs always visible)
- Copy button placement and styling
- Copy feedback mechanism (button text change, toast, animation)
- Shot metadata badge positioning
- Camera movement indicator design

**Guiding principle:** Match existing modal patterns for visual consistency. Follow the "automatic, effortless" principle — copy should feel instant and reliable.

</decisions>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches.

Follow existing patterns from:
- Timeline component (has working clipboard implementation)
- Storyboard prompt display
- Character Bible/Location Bible modal styling

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 09-prompts-display-copy-to-clipboard*
*Context gathered: 2026-01-23*
