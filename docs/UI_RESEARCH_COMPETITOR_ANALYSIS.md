# Storyboard Studio UI/UX Research & Competitor Analysis

> **Research Date:** January 2026
> **Focus:** Competitor UI patterns, modern design trends, and recommendations for premium AI-native experience

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Competitor Analysis](#competitor-analysis)
3. [Modern UI Trends 2024-2025](#modern-ui-trends-2024-2025)
4. [Component-Specific Research](#component-specific-research)
5. [Design Recommendations](#design-recommendations)
6. [Implementation Roadmap](#implementation-roadmap)
7. [Sources](#sources)

---

## Executive Summary

### What Makes AI Video Tools Feel "Premium"

Based on extensive research across Runway ML, Pika Labs, Kaiber AI, Luma AI, Kling AI, Leonardo.ai, and Midjourney, premium AI creative tools share these characteristics:

| Pattern | Description | Competitors Using It |
|---------|-------------|---------------------|
| **Board/Canvas-based UI** | Infinite canvas for ideation, not just linear workflows | Luma AI, Kaiber, Midjourney |
| **Contextual Toolbars** | Floating toolbars that appear based on current selection/task | Adobe Suite, Figma |
| **Progressive Disclosure** | Show only what's needed, reveal complexity gradually | Pika Labs, Leonardo.ai |
| **Real-time Feedback** | Streaming generation, skeleton loading, progress indicators | Runway, Kling AI |
| **Multi-modal Input** | Text, image, video all accepted as input seamlessly | Kling AI, Runway Gen-4 |
| **Iterative Workflows** | "Brainstorm" features suggesting variations | Luma AI Dream Machine |

### Key Differentiators to Implement

1. **Unified Board Experience** - Like Luma AI's "single continuous board without handoffs"
2. **Scene DNA System** - Already unique to Artime, needs better visual prominence
3. **AI-First Interaction** - Let AI suggest first, user refines (not blank canvas problem)
4. **Micro-interactions** - Smooth 200-500ms animations for all state changes

---

## Competitor Analysis

### 1. Runway ML (Gen-3/Gen-4)

**Interface Philosophy:** Professional-grade with comprehensive tool suite

**Key UI Features:**
- Motion Brush for selective animation
- Keyframe controls (start, middle, end)
- Camera controls (handheld shake, static, etc.)
- Multi-modal generation (text-to-video, image-to-video)

**What Makes It Premium:**
- Comprehensive suite approach (not just generation)
- Precise control options (keyframes, camera moves)
- Fast generation with real-time preview
- Professional pricing tiers with clear value differentiation

**Design Patterns:**
```
┌─────────────────────────────────────────┐
│  [Model Selector]  [Controls Panel]     │
├─────────────────────────────────────────┤
│                                         │
│           Canvas/Preview Area           │
│                                         │
├─────────────────────────────────────────┤
│  [Timeline] [Keyframes] [Motion Brush]  │
└─────────────────────────────────────────┘
```

**Takeaway for Artime:** Runway's keyframe system and motion brush could inspire shot-level controls. The split between "creative" and "technical" controls is well-executed.

---

### 2. Pika Labs (2.0-2.5)

**Interface Philosophy:** Beginner-friendly with innovative features

**Key UI Features:**
- "Scene Ingredients" - Upload characters/objects for composition
- Pikaframes for keyframe control
- Pikaswaps/Pikadditions for element manipulation
- Physics simulation integration

**What Makes It Premium:**
- Revolutionary "Scene Ingredients" compositing
- Natural language understanding for complex prompts
- Approachable yet powerful interface
- Clear visual hierarchy with intuitive controls

**Design Patterns:**
```
┌───────────────────────────────────────────┐
│  Prompt Bar (Full Width)                  │
├─────────────┬─────────────────────────────┤
│  Ingredients│       Generation Canvas     │
│  Panel      │                             │
│  [+ Person] │       [Video Preview]       │
│  [+ Object] │                             │
│  [+ Scene]  │                             │
└─────────────┴─────────────────────────────┘
```

**Takeaway for Artime:** The "Scene Ingredients" concept aligns with Artime's Bible system (Character, Location, Style). Make uploading reference images more prominent and visual.

---

### 3. Kaiber AI (Superstudio)

**Interface Philosophy:** Infinite canvas meets AI generation

**Key UI Features:**
- Superstudio infinite canvas
- Multiple model integration (Luma, Flux, Kaiber)
- Audioreactivity for music sync
- Flipbook animation style
- Flow-based workflows

**What Makes It Premium:**
- Unified canvas for all creative work
- Multiple AI models in one interface
- Audio-visual synchronization
- 4.8 App Store rating (mobile-first design)

**Design Patterns:**
```
┌─────────────────────────────────────────────────┐
│  Toolbar (Floating, Contextual)                 │
├─────────────────────────────────────────────────┤
│                                                 │
│         ∞ Infinite Canvas                       │
│                                                 │
│    [Card] [Card] [Card]                         │
│              [Card] [Card]                      │
│                                                 │
├─────────────────────────────────────────────────┤
│  [Model Selector: Luma | Flux | Kaiber]         │
└─────────────────────────────────────────────────┘
```

**Takeaway for Artime:** The infinite canvas approach for storyboarding could be powerful. Consider letting users arrange shot cards freely, not just in a grid.

---

### 4. Luma AI (Dream Machine)

**Interface Philosophy:** Conversational, board-based creativity

**Key UI Features:**
- Board types: Artboard, Storyboard, Moodboard
- Brainstorm feature (AI suggests variations)
- Natural language "Modify with Instructions"
- Ray2 for high-quality generation
- Photon for image generation

**What Makes It Premium:**
- "Work inside a single continuous board"
- Brainstorm suggestions accelerate ideation
- Natural language editing without masks
- Under 10-second generation speed

**Design Patterns:**
```
┌──────────────────────────────────────────────────┐
│  [Artboard] [Storyboard] [Moodboard]   [Export]  │
├──────────────────────────────────────────────────┤
│                                                  │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐          │
│  │ Shot 1  │→ │ Shot 2  │→ │ Shot 3  │          │
│  └─────────┘  └─────────┘  └─────────┘          │
│                                                  │
│  💡 Brainstorm: "Try a wider angle..."          │
│                                                  │
├──────────────────────────────────────────────────┤
│  [Prompt Input with Natural Language]            │
└──────────────────────────────────────────────────┘
```

**Takeaway for Artime:** The "Brainstorm" feature is brilliant - AI-suggested variations. Implement something similar for shot alternatives. Board-switching (Storyboard/Moodboard) concept is excellent.

---

### 5. Kling AI (2.5-2.6)

**Interface Philosophy:** Multi-modal with precise control

**Key UI Features:**
- "@" syntax for element compositing
- Start/End frame keyframing
- Creativity slider (prompt adherence vs. freedom)
- Lip-sync feature
- Voice and motion control (v2.6)
- Video extension to 3 minutes

**What Makes It Premium:**
- Native voice generation with video
- Most extensive video length (3 min)
- Multi-element prompting (up to 4 references)
- Conversational editing interface

**Design Patterns:**
```
┌────────────────────────────────────────────────┐
│  Mode: [Text→Video] [Image→Video] [Elements]   │
├────────────────────────────────────────────────┤
│  Prompt: @character1 walking through @scene1   │
│  ┌──────────┐ ┌──────────┐                     │
│  │ Char Ref │ │ Scene Ref│                     │
│  └──────────┘ └──────────┘                     │
├────────────────────────────────────────────────┤
│  Creativity: ████████░░░░ 65%                  │
│  Duration:   ████░░░░░░░░ 10s                  │
├────────────────────────────────────────────────┤
│  [Start Frame]  ───────→  [End Frame]          │
└────────────────────────────────────────────────┘
```

**Takeaway for Artime:** The "@" mention syntax for referencing bibles could be powerful. "Use @MainCharacter in @CityStreet" is intuitive. Creativity slider is a nice balance control.

---

### 6. Leonardo.ai

**Interface Philosophy:** Browser-based canvas with layers

**Key UI Features:**
- Real-time canvas editing with layers
- Transparency mode (PNG output)
- Character reference for consistency
- Presets for quick style application
- Image Gen V2 with improved UX

**What Makes It Premium:**
- Layers and masks like traditional tools
- Transparency/PNG export workflow
- Professional editing capabilities
- Clean, intuitive interface

**Acquired by Canva (July 2024) - Indicates industry-validated design approach

**Takeaway for Artime:** Layer-based approach for complex scenes. Preset system for quick style application aligns with Style Bible concept.

---

### 7. Midjourney

**Interface Philosophy:** Web app replacing Discord complexity

**Key UI Features:**
- Simple web interface (replaced Discord)
- Image editing (retexture, expand, crop, inpaint)
- Style/character references for consistency
- Image upload and conditioning

**What Makes It Premium:**
- Best-in-class image quality (v7)
- Reference image system for consistency
- Clean, focused interface
- Strong community and iteration speed

**Takeaway for Artime:** Midjourney's reference system (style, character references) is similar to Bible concept. Their simplified web UI from Discord shows value of accessibility.

---

## Modern UI Trends 2024-2025

### 1. Bento Grid Layouts

**What it is:** Modular layouts inspired by Japanese bento boxes, with irregularly sized sections creating visual hierarchy.

**Why it matters for Artime:**
- Perfect for dashboard-style information display
- Adapts well to different screen sizes
- Creates visual interest without chaos
- Ideal for mixing stats, controls, and previews

**Implementation:**
```css
.bento-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  grid-template-rows: repeat(3, 1fr);
  gap: 16px;
}

.scene-stats { grid-area: 1 / 1 / 2 / 2; }
.shot-grid { grid-area: 1 / 2 / 3 / 4; }
.controls { grid-area: 1 / 4 / 2 / 5; }
.timeline { grid-area: 3 / 1 / 4 / 5; }
```

---

### 2. Glassmorphism (Refined)

**What it is:** Frosted-glass effect with blur, transparency, and subtle layering.

**2025 Evolution:** More subtle, focused on accessibility. Dynamic blurring and adaptive layers.

**Best practices:**
- Use for floating panels, modals, toolbars
- Maintain 4.5:1 contrast ratio for text
- Apply to decorative elements, not primary content
- Combine with subtle borders for definition

**Implementation:**
```css
.glass-panel {
  background: rgba(30, 30, 30, 0.7);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
}
```

---

### 3. Dark Mode Best Practices

**Core principles for creative apps:**

| Element | Recommendation |
|---------|----------------|
| Background | Dark gray (#121212-#1E1E1E), NOT pure black |
| Primary text | 87% white opacity |
| Secondary text | 60% white opacity |
| Disabled text | 38% white opacity |
| Contrast ratio | Minimum 4.5:1 for body text |
| Accent colors | Desaturate by 10-20% from light mode |

**Why dark mode for Artime:**
- Reduces eye strain during long sessions
- Makes visual content (images/video) pop
- Industry standard for creative tools
- Better for color accuracy perception

**Accessibility considerations:**
- Support reduced motion preferences
- Provide high-contrast mode option
- Test with screen readers
- Consider astigmatic users (avoid pure black)

---

### 4. Micro-Animations & Transitions

**Timing guidelines:**
- Button hover: 150-200ms
- Panel transitions: 200-300ms
- Loading shimmer: 1.5-2s cycle
- Maximum attention animation: 500ms

**Where to apply:**
```
✓ State changes (hover, active, focus)
✓ Loading/progress indicators
✓ Card expansion/collapse
✓ Toolbar appearance
✓ Modal open/close
✓ Drag and drop feedback

✗ Primary content reading
✗ Long-running animations
✗ Distracting decorative motion
```

---

### 5. Skeleton Loading

**When to use:**
- Content feeds (shot grid)
- Dashboard data loading
- Profile/settings panels
- Any content taking >300ms to load

**When NOT to use:**
- Fast operations (<1 second)
- Video buffering (use progress bar)
- File uploads (use determinate progress)

**Implementation pattern:**
```
1. Show skeleton matching content structure
2. Animate subtle shimmer (1.5-2s cycle)
3. Fade smoothly to real content
4. Research shows 20-30% faster perceived load time
```

---

### 6. Contextual/Floating Toolbars

**Modern approach (inspired by Adobe & Figma):**

```
┌─────────────────────────────────────────────┐
│                Canvas                        │
│                                             │
│    ┌─────────────────────────────────┐      │
│    │     Selected Shot Card          │      │
│    │  ┌──────────────────────────┐   │      │
│    │  │ 🎨 Edit │ 🔄 Regenerate │ ⋮ │   │      │
│    │  └──────────────────────────┘   │      │
│    │        (Floating Toolbar)       │      │
│    └─────────────────────────────────┘      │
│                                             │
└─────────────────────────────────────────────┘
```

**Benefits:**
- Reduces cognitive load
- Shows only relevant actions
- Keeps user focused on content
- Reduces permanent UI chrome

---

## Component-Specific Research

### Scene Card Redesign Patterns

**Current state:** Compact cards in grid layout

**Recommended improvements:**

#### Option A: Timeline View
```
┌────────────────────────────────────────────────────────┐
│ Scene 1                                                │
│ ┌───────┬───────┬───────┬───────┬───────┬───────┐     │
│ │Shot 1 │Shot 2 │Shot 3 │Shot 4 │Shot 5 │ + Add │     │
│ │ [img] │ [img] │ [img] │ [img] │ [img] │       │     │
│ │ 2.5s  │ 3.0s  │ 1.5s  │ 4.0s  │ 2.0s  │       │     │
│ └───────┴───────┴───────┴───────┴───────┴───────┘     │
│ Total: 13.0s                                          │
└────────────────────────────────────────────────────────┘
```

#### Option B: Expanded Card with Context
```
┌──────────────────────────────────────┐
│  ┌────────────────────────────────┐  │
│  │                                │  │
│  │     [Shot Thumbnail/Video]    │  │
│  │                                │  │
│  ├────────────────────────────────┤  │
│  │ Shot 3 of 7          ⏱ 2.5s   │  │
│  │ "Wide establishing shot..."   │  │
│  ├────────────────────────────────┤  │
│  │ ● Generated  │ Camera: Wide   │  │
│  │ 📍 Location Bible: City       │  │
│  └────────────────────────────────┘  │
│  [Edit] [Regenerate] [Delete] [⋮]    │
└──────────────────────────────────────┘
```

#### Option C: Kanban-Style Workflow
```
┌──────────────┬──────────────┬──────────────┐
│   Pending    │  Generating  │   Complete   │
├──────────────┼──────────────┼──────────────┤
│   [Card]     │   [Card]     │   [Card]     │
│   [Card]     │   ░░░░░░░    │   [Card]     │
│   [Card]     │              │   [Card]     │
│              │              │   [Card]     │
└──────────────┴──────────────┴──────────────┘
```

---

### Settings/Control Panel Approaches

**Pattern 1: Collapsible Sections (Current-ish)**
```
┌─────────────────────────────────┐
│ ▼ Visual Style           [PRO] │
├─────────────────────────────────┤
│   Mood:     [Auto        ▼]    │
│   Lighting: [Auto        ▼]    │
│   Colors:   [Auto        ▼]    │
│   Shot:     [Auto        ▼]    │
└─────────────────────────────────┘
│ ▶ Video Model                   │
│ ▶ Scene Memory           [NEW] │
└─────────────────────────────────┘
```

**Pattern 2: Tab-Based Panel**
```
┌────────────────────────────────────────┐
│ [Style] [Model] [Memory] [Export]      │
├────────────────────────────────────────┤
│                                        │
│   Active Style Profile ✓               │
│   "Energetic and immersive..."         │
│                                        │
│   ┌─────────┐ ┌─────────┐              │
│   │ Mood    │ │Lighting │              │
│   │ [Auto]  │ │ [Auto]  │              │
│   └─────────┘ └─────────┘              │
│                                        │
└────────────────────────────────────────┘
```

**Pattern 3: Side Panel (Like Figma)**
```
┌────────────────────────┬───────────────┐
│                        │ Properties    │
│    Main Canvas         ├───────────────┤
│                        │ Visual Style  │
│    [Shot Grid]         │ ────────────  │
│                        │ Mood: Auto    │
│                        │ Lighting: Auto│
│                        │               │
│                        │ Video Model   │
│                        │ ────────────  │
│                        │ [Minimax]     │
└────────────────────────┴───────────────┘
```

---

### Progress Indicators for AI Generation

**Recommended pattern for Artime:**

```
┌────────────────────────────────────────────────────┐
│  Generating Shot 3 of 7                            │
│                                                    │
│  ┌────────────────────────────────────────────┐   │
│  │                                            │   │
│  │    [Progressive Image Reveal]              │   │
│  │         (Show partial result)              │   │
│  │                                            │   │
│  └────────────────────────────────────────────┘   │
│                                                    │
│  ████████████████░░░░░░░░░░░░░░░░░░  45%         │
│                                                    │
│  Step: Generating frames... (~12s remaining)      │
│                                                    │
│  [Cancel]                              [Pause]    │
└────────────────────────────────────────────────────┘
```

**Key elements:**
1. **Determinate progress** (percentage-based)
2. **Estimated time remaining**
3. **Current step description**
4. **Progressive/streaming preview** when possible
5. **Cancel/pause controls**
6. **Batch progress** (X of Y shots)

---

### Modal Dialog Modern Patterns

**When to use modals:**
- Confirming destructive actions
- Critical settings that affect generation
- First-time user onboarding
- Bible editing (full focus needed)

**When to use side panels instead:**
- Editing shot properties
- Quick settings changes
- Reference material browsing
- Non-critical information

**Modern modal structure:**
```
┌──────────────────────────────────────────┐
│                                     [×]  │
│  ┌────────────────────────────────────┐  │
│  │       Edit Character Bible         │  │
│  └────────────────────────────────────┘  │
│                                          │
│  Name: [Maya Chen                    ]   │
│                                          │
│  Reference Images:                       │
│  ┌─────┐ ┌─────┐ ┌─────┐                │
│  │ img │ │ img │ │  +  │                │
│  └─────┘ └─────┘ └─────┘                │
│                                          │
│  Description:                            │
│  [                                    ]  │
│                                          │
│  ─────────────────────────────────────   │
│            [Cancel]  [Save Changes]      │
└──────────────────────────────────────────┘
```

---

## Design Recommendations

### Answering Key Questions

#### 1. Dark vs. Light Theme?
**Recommendation: Dark theme as default, with light option**

Rationale:
- Industry standard for creative/video tools
- Reduces eye strain during long sessions
- Makes visual content pop
- Aligns with competitor expectations
- Current Artime already uses dark theme

#### 2. Grid vs. List vs. Timeline View?
**Recommendation: Default to Grid, offer Timeline toggle**

```
[Grid View] [Timeline View]

Grid: Best for overview, drag-reorder, visual scanning
Timeline: Best for timing, pacing, video preview
```

Consider hybrid:
- Grid within scenes
- Timeline for scene-to-scene flow

#### 3. Floating Toolbar vs. Side Panels?
**Recommendation: Both, context-dependent**

| Context | Use |
|---------|-----|
| Selected shot card | Floating toolbar (quick actions) |
| Generation settings | Side panel (persistent) |
| Bible editing | Modal (full focus) |
| Global controls | Top bar (always visible) |

#### 4. How to Make It Feel "AI-Native"?
**Key patterns:**

1. **AI Goes First**: Pre-fill suggestions, show variations
2. **Confidence Indicators**: Show AI certainty levels
3. **Progressive Reveal**: Stream generation results
4. **Natural Language**: Support conversational editing
5. **Brainstorm Mode**: AI suggests alternatives
6. **Context Awareness**: UI adapts to current task

---

### Priority Improvements

#### High Priority (Immediate Impact)

1. **Contextual Floating Toolbar for Shot Cards**
   - Appears on selection
   - Quick actions: Edit, Regenerate, Delete, Extend
   - Reduces clicks for common tasks

2. **Enhanced Progress Indicators**
   - Determinate progress bars
   - Estimated time remaining
   - Streaming preview when possible

3. **Skeleton Loading for Shot Grid**
   - Match card layout structure
   - Smooth shimmer animation
   - Professional perceived performance

4. **Bible System Visual Prominence**
   - Make Scene DNA more visually prominent
   - Visual indicators showing which bibles are in use
   - Quick-access panel for bible switching

#### Medium Priority (Quality of Life)

5. **Bento-Style Dashboard Layout**
   - Reorganize stats and controls
   - Better visual hierarchy
   - More adaptable to different screen sizes

6. **View Mode Toggle**
   - Grid view (current)
   - Timeline view (new)
   - Keyboard shortcuts for switching

7. **Micro-Animations**
   - State change transitions
   - Loading states
   - Hover effects
   - Card expand/collapse

#### Lower Priority (Polish)

8. **Glassmorphism Accents**
   - Floating panels
   - Modal overlays
   - Status indicators

9. **"Brainstorm" Feature**
   - AI-suggested shot variations
   - Style alternatives
   - Composition options

10. **"@" Mention Syntax**
    - Reference bibles in prompts
    - "@MainCharacter in @OfficeLocation"
    - Autocomplete support

---

## Implementation Roadmap

### Phase 1: Foundation (Core UX Improvements)

**Focus: Loading states, progress indicators, micro-interactions**

Tasks:
- [ ] Implement skeleton loading for shot grid
- [ ] Enhanced generation progress indicators
- [ ] Basic micro-animations (hover, transitions)
- [ ] Floating toolbar prototype for shot cards

**Technical considerations:**
- CSS custom properties for animation timing
- Framer Motion or CSS transitions
- Skeleton component extraction

### Phase 2: Layout Enhancement

**Focus: Information architecture and visual hierarchy**

Tasks:
- [ ] Bento grid layout for dashboard sections
- [ ] Collapsible/expandable settings panels
- [ ] Side panel for properties (contextual)
- [ ] View mode toggle (grid/timeline)

**Technical considerations:**
- CSS Grid for bento layout
- Panel state management
- View mode preference persistence

### Phase 3: AI-Native Features

**Focus: Making the AI feel integrated and helpful**

Tasks:
- [ ] Brainstorm/variation suggestions
- [ ] Progressive generation preview
- [ ] Natural language editing support
- [ ] "@" mention syntax for bibles

**Technical considerations:**
- Streaming API integration
- Autocomplete/mention system
- Suggestion UI components

### Phase 4: Polish & Refinement

**Focus: Premium feel and visual polish**

Tasks:
- [ ] Glassmorphism refinements
- [ ] Advanced animation choreography
- [ ] Keyboard shortcuts system
- [ ] Light theme option

**Technical considerations:**
- Theme system architecture
- Animation orchestration
- Accessibility audit

---

## Technical Recommendations

### CSS Architecture

```
/styles
  /tokens
    colors.css       # CSS custom properties
    spacing.css
    typography.css
    animation.css
  /components
    shot-card.css
    floating-toolbar.css
    progress-indicator.css
    skeleton.css
  /layouts
    bento-grid.css
    side-panel.css
```

### Animation Library Options

| Library | Pros | Cons | Best For |
|---------|------|------|----------|
| **Framer Motion** | Declarative, React-native, great DX | Bundle size | Complex choreography |
| **CSS Transitions** | Zero bundle cost, performant | Limited orchestration | Simple state changes |
| **GSAP** | Powerful, timeline control | Learning curve | Complex animations |
| **Auto-animate** | Drop-in solution | Less control | Quick wins |

**Recommendation:** CSS for simple transitions, Framer Motion for complex interactions

### Component Extraction Priorities

1. `<SkeletonLoader />` - Reusable loading states
2. `<FloatingToolbar />` - Contextual actions
3. `<ProgressIndicator />` - Generation progress
4. `<BentoGrid />` - Layout system
5. `<GlassPanel />` - Overlay/modal base

---

## Sources

### Competitor Research
- [Runway ML Product](https://runwayml.com/product)
- [Runway ML Changelog](https://runwayml.com/changelog)
- [Pika Labs 2.5 Features](https://pikartai.com/pika-2-5/)
- [Kaiber Superstudio Launch](https://www.businesswire.com/news/home/20241016279399/en/)
- [Luma AI Dream Machine](https://lumalabs.ai/dream-machine)
- [Kling AI Guide](https://www.litmedia.ai/resource-ai-video/kling-ai-video-generator/)
- [Leonardo.ai Review](https://www.allaboutai.com/ai-reviews/leonardo-ai/)

### UI/UX Trends
- [Smashing Magazine - Inclusive Dark Mode](https://www.smashingmagazine.com/2025/04/inclusive-dark-mode-designing-accessible-dark-themes/)
- [Bento Grid Trends 2025](https://medium.com/@support_82111/from-bento-boxes-to-brutalism-decoding-the-top-ui-design-trends-for-2025-f524d0a49569)
- [UI/UX Design Trends 2025](https://www.wearetenet.com/blog/ui-ux-design-trends)

### Design Patterns
- [Shape of AI - UX Patterns](https://www.shapeof.ai/)
- [AI-Native UX Design](https://www.buzzi.ai/insights/ai-native-applications-ux-design-patterns)
- [Skeleton Screen Guide](https://blog.logrocket.com/ux-design/skeleton-loading-screen-design/)
- [Progress Indicator Design](https://lollypop.design/blog/2025/november/progress-indicator-design/)
- [Modal UX Design 2025](https://userpilot.com/blog/modal-ux-design/)
- [Adobe Contextual Task Bar](https://helpx.adobe.com/photoshop/using/contextual-task-bar.html)
- [Carbon Design System - Modal](https://carbondesignsystem.com/components/modal/usage/)

---

*Document prepared for Artime Storyboard Studio UI/UX redesign initiative*
