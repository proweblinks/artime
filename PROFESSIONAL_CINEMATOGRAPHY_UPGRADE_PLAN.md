# Professional Cinematography Upgrade Plan
## Video Wizard - Hollywood-Style Production System

### Current State Summary

#### What's Already Implemented (Phases 1-5)
1. **Multi-Shot Architecture** - Scenes (25-45s) decompose into Shots (5-10s)
2. **Frame Chain Workflow** - Last frame of shot N → First frame of shot N+1
3. **Genre Presets** - 15 professional cinematography genres in `GENRE_PRESETS` constant
4. **Narrative Shot Selection** - Shot types based on emotional beats, not mechanical cycling
5. **Professional Prompt Builder** - 50-100 word prompts with lens specs, color grades
6. **Shot-Based Video Assembly** - Collect all shot videos for final render

#### Key Files
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Main component with GENRE_PRESETS
- `modules/AppVideoWizard/config/config.php` - Platform/format configuration
- `modules/AppVideoWizard/resources/views/admin/settings.blade.php` - Admin settings UI
- `modules/AppVideoWizard/app/Http/Controllers/Admin/VideoWizardAdminController.php` - Admin controller

---

## Professional Upgrades Plan

### Upgrade 1: Admin-Manageable Genre Presets

**Goal:** Move GENRE_PRESETS to database with full admin CRUD interface.

#### Database Schema
```sql
CREATE TABLE vw_genre_presets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category ENUM('documentary', 'cinematic', 'horror', 'comedy', 'social', 'commercial', 'experimental') NOT NULL,
    camera_language TEXT NOT NULL,
    color_grade TEXT NOT NULL,
    lighting TEXT NOT NULL,
    atmosphere TEXT NOT NULL,
    style TEXT NOT NULL,
    lens_preferences JSON, -- {"establishing": "24mm wide", "closeup": "85mm telephoto"}
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Admin Panel Features
- List all genre presets with preview cards
- Create/Edit genre with live preview
- Drag-and-drop reordering
- Enable/disable genres for users
- Import/export presets as JSON
- Clone existing presets
- "Test Generate" button to preview prompt output

---

### Upgrade 2: Admin-Manageable Shot Types System

**Goal:** Expand from 6 to 50+ professional shot types with admin control.

#### Database Schema
```sql
CREATE TABLE vw_shot_types (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category ENUM('framing', 'angle', 'movement', 'focus', 'special') NOT NULL,
    description TEXT,
    camera_specs TEXT, -- "85mm telephoto, f/1.4, shallow DOF"
    typical_duration_min INT DEFAULT 3,
    typical_duration_max INT DEFAULT 8,
    emotional_beats JSON, -- ["tension", "intimacy", "reveal"]
    best_for_genres JSON, -- ["thriller", "drama", "horror"]
    prompt_template TEXT, -- "Close-up shot {lens_spec}, capturing {subject} with {camera_movement}"
    motion_description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Seed with 50+ professional shot types
INSERT INTO vw_shot_types (slug, name, category, description, emotional_beats) VALUES
-- Framing shots
('extreme-wide', 'Extreme Wide Shot (EWS)', 'framing', 'Shows entire location, subject is tiny', '["establishing", "isolation", "scale"]'),
('very-wide', 'Very Wide Shot (VWS)', 'framing', 'Subject visible but environment dominates', '["context", "journey", "environment"]'),
('wide', 'Wide Shot (WS)', 'framing', 'Full body visible with environment', '["establishing", "geography", "movement"]'),
('full', 'Full Shot (FS)', 'framing', 'Head to toe framing', '["introduction", "action", "dance"]'),
('medium-wide', 'Medium Wide Shot (MWS)', 'framing', 'Knees up, also called 3/4 shot', '["dialogue", "group", "movement"]'),
('cowboy', 'Cowboy Shot', 'framing', 'Mid-thigh up, western genre origin', '["confrontation", "standoff", "tension"]'),
('medium', 'Medium Shot (MS)', 'framing', 'Waist up framing', '["dialogue", "interview", "reaction"]'),
('medium-close', 'Medium Close-Up (MCU)', 'framing', 'Chest up framing', '["emotion", "dialogue", "intimacy"]'),
('close-up', 'Close-Up (CU)', 'framing', 'Face fills frame', '["emotion", "intensity", "reaction"]'),
('big-close-up', 'Big Close-Up (BCU)', 'framing', 'Forehead to chin', '["intense-emotion", "critical-moment", "revelation"]'),
('extreme-close-up', 'Extreme Close-Up (ECU)', 'framing', 'Single feature (eye, mouth, hand)', '["detail", "suspense", "symbolism"]'),
('insert', 'Insert Shot', 'framing', 'Detail of object or action', '["information", "detail", "continuity"]'),
('cutaway', 'Cutaway', 'framing', 'Related but different subject', '["transition", "reaction", "context"]'),

-- Angle shots
('eye-level', 'Eye Level', 'angle', 'Camera at subject eye level', '["neutral", "conversation", "natural"]'),
('low-angle', 'Low Angle', 'angle', 'Camera below looking up', '["power", "dominance", "heroic"]'),
('high-angle', 'High Angle', 'angle', 'Camera above looking down', '["vulnerability", "weakness", "overview"]'),
('dutch-angle', 'Dutch Angle/Tilt', 'angle', 'Camera tilted on axis', '["unease", "tension", "disorientation"]'),
('birds-eye', 'Bird''s Eye View', 'angle', 'Directly overhead', '["god-view", "pattern", "isolation"]'),
('worms-eye', 'Worm''s Eye View', 'angle', 'Extreme low from ground', '["power", "monumentality", "awe"]'),
('over-shoulder', 'Over-the-Shoulder (OTS)', 'angle', 'Behind one subject toward another', '["dialogue", "connection", "perspective"]'),
('pov', 'Point of View (POV)', 'angle', 'From character''s eyes', '["immersion", "subjective", "tension"]'),

-- Movement shots
('static', 'Static Shot', 'movement', 'Camera remains fixed', '["stability", "observation", "dialogue"]'),
('pan', 'Pan', 'movement', 'Horizontal rotation on axis', '["reveal", "follow", "scan"]'),
('tilt', 'Tilt', 'movement', 'Vertical rotation on axis', '["reveal", "scale", "power"]'),
('dolly', 'Dolly Shot', 'movement', 'Camera moves toward/away', '["intimacy", "reveal", "emphasis"]'),
('tracking', 'Tracking Shot', 'movement', 'Camera follows alongside subject', '["journey", "conversation", "energy"]'),
('crane', 'Crane Shot', 'movement', 'Vertical and horizontal movement', '["epic", "reveal", "transition"]'),
('steadicam', 'Steadicam', 'movement', 'Smooth handheld following', '["immersion", "flow", "energy"]'),
('handheld', 'Handheld', 'movement', 'Intentional camera shake', '["urgency", "documentary", "chaos"]'),
('zoom', 'Zoom', 'movement', 'Lens focal length change', '["emphasis", "surprise", "reveal"]'),
('dolly-zoom', 'Dolly Zoom (Vertigo)', 'movement', 'Dolly + opposite zoom', '["disorientation", "realization", "horror"]'),
('whip-pan', 'Whip Pan', 'movement', 'Fast horizontal blur', '["transition", "energy", "surprise"]'),
('arc', 'Arc Shot', 'movement', 'Camera moves in circle around subject', '["drama", "revelation", "360-view"]'),

-- Focus shots
('rack-focus', 'Rack Focus', 'focus', 'Shift focus between subjects', '["connection", "reveal", "transition"]'),
('deep-focus', 'Deep Focus', 'focus', 'Everything sharp front to back', '["environment", "context", "staging"]'),
('shallow-focus', 'Shallow Focus', 'focus', 'Subject sharp, background blurred', '["isolation", "intimacy", "emphasis"]'),
('split-diopter', 'Split Diopter', 'focus', 'Two planes in focus simultaneously', '["dual-focus", "tension", "connection"]'),

-- Special shots
('two-shot', 'Two Shot', 'special', 'Two subjects in frame', '["relationship", "dialogue", "comparison"]'),
('three-shot', 'Three Shot', 'special', 'Three subjects in frame', '["group-dynamics", "triangle", "hierarchy"]'),
('master', 'Master Shot', 'special', 'Wide coverage of entire scene', '["establishing", "geography", "reference"]'),
('reaction', 'Reaction Shot', 'special', 'Subject responding to off-screen event', '["emotion", "impact", "comedy"]'),
('establishing', 'Establishing Shot', 'special', 'Sets location and context', '["opening", "transition", "geography"]'),
('weather', 'Weather Shot', 'special', 'Atmospheric/environmental shot', '["mood", "transition", "time-passage"]'),
('aerial', 'Aerial Shot', 'special', 'Filmed from above (drone/helicopter)', '["scale", "journey", "epic"]'),
('underwater', 'Underwater Shot', 'special', 'Filmed below water surface', '["surreal", "dream", "adventure"]');
```

#### Admin Panel Features
- Full CRUD for shot types
- Category grouping with collapsible sections
- Preview cards showing sample output
- Assign emotional beats via tag selector
- Assign compatible genres
- Edit prompt templates with variable placeholders
- Bulk enable/disable

---

### Upgrade 3: Emotional Beat Management System

**Goal:** Admin-configurable emotional beats for narrative-based shot selection.

#### Database Schema
```sql
CREATE TABLE vw_emotional_beats (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    story_position ENUM('act1_setup', 'act1_catalyst', 'act2_rising', 'act2_midpoint', 'act2_crisis', 'act3_climax', 'act3_resolution') NULL,
    intensity_level INT DEFAULT 5, -- 1-10 scale
    recommended_shot_types JSON, -- ["close-up", "medium", "reaction"]
    recommended_camera_movement JSON, -- ["slow-push", "static", "handheld"]
    pacing_suggestion ENUM('slow', 'medium', 'fast') DEFAULT 'medium',
    color_mood VARCHAR(100), -- "warm", "cold", "desaturated"
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Seed emotional beats based on professional filmmaking
INSERT INTO vw_emotional_beats (slug, name, description, story_position, intensity_level, recommended_shot_types, pacing_suggestion) VALUES
-- Act 1 beats
('hook', 'Hook', 'Attention-grabbing opening moment', 'act1_setup', 7, '["establishing", "wide", "aerial"]', 'medium'),
('introduction', 'Introduction', 'Character/world establishment', 'act1_setup', 3, '["wide", "medium", "tracking"]', 'slow'),
('normalcy', 'Normalcy', 'Everyday life before disruption', 'act1_setup', 2, '["medium", "wide", "master"]', 'slow'),
('inciting-incident', 'Inciting Incident', 'Event that starts the story', 'act1_catalyst', 8, '["close-up", "reaction", "dolly"]', 'medium'),
('refusal', 'Refusal of Call', 'Initial resistance to change', 'act1_catalyst', 5, '["medium", "over-shoulder", "static"]', 'slow'),
('acceptance', 'Acceptance', 'Commitment to journey', 'act1_catalyst', 6, '["medium-close", "low-angle", "push-in"]', 'medium'),

-- Act 2 beats
('exploration', 'Exploration', 'Discovering new world/situation', 'act2_rising', 4, '["wide", "tracking", "pov"]', 'medium'),
('fun-games', 'Fun and Games', 'Promise of premise delivered', 'act2_rising', 6, '["various", "dynamic", "montage"]', 'fast'),
('ally-enemy', 'Allies & Enemies', 'Relationship establishment', 'act2_rising', 5, '["two-shot", "over-shoulder", "reaction"]', 'medium'),
('first-challenge', 'First Challenge', 'Initial obstacle faced', 'act2_rising', 7, '["medium", "handheld", "quick-cuts"]', 'fast'),
('midpoint-twist', 'Midpoint Twist', 'Major revelation or shift', 'act2_midpoint', 9, '["close-up", "dolly-zoom", "dutch"]', 'medium'),
('stakes-raised', 'Stakes Raised', 'Consequences become real', 'act2_midpoint', 8, '["close-up", "low-angle", "dramatic"]', 'medium'),
('all-is-lost', 'All Is Lost', 'Lowest point, hope fades', 'act2_crisis', 9, '["high-angle", "wide", "desaturated"]', 'slow'),
('dark-moment', 'Dark Night of Soul', 'Internal reckoning', 'act2_crisis', 8, '["close-up", "static", "intimate"]', 'slow'),

-- Act 3 beats
('realization', 'Realization', 'Key insight discovered', 'act3_climax', 7, '["close-up", "rack-focus", "push-in"]', 'medium'),
('gathering-forces', 'Gathering Forces', 'Preparing for final battle', 'act3_climax', 6, '["montage", "tracking", "various"]', 'fast'),
('climax', 'Climax', 'Peak confrontation/action', 'act3_climax', 10, '["dynamic", "handheld", "fast-cuts"]', 'fast'),
('resolution', 'Resolution', 'Conflict resolved', 'act3_resolution', 5, '["medium", "wide", "slow"]', 'slow'),
('new-normal', 'New Normal', 'Changed world/character', 'act3_resolution', 3, '["wide", "establishing", "peaceful"]', 'slow'),
('final-image', 'Final Image', 'Closing shot echoing opening', 'act3_resolution', 4, '["matching-opening", "symbolic", "wide"]', 'slow'),

-- Standalone emotional beats
('tension', 'Tension', 'Building suspense or anxiety', NULL, 7, '["close-up", "dutch-angle", "slow-push"]', 'slow'),
('release', 'Release', 'Tension breaks, relief', NULL, 5, '["wide", "pull-back", "breathing-room"]', 'medium'),
('humor', 'Humor', 'Comic moment or relief', NULL, 4, '["medium", "reaction", "timing-cut"]', 'medium'),
('romance', 'Romance', 'Intimate connection moment', NULL, 5, '["close-up", "two-shot", "soft-focus"]', 'slow'),
('action', 'Action', 'Physical activity/conflict', NULL, 8, '["wide", "tracking", "handheld"]', 'fast'),
('mystery', 'Mystery', 'Intrigue or unknown revealed', NULL, 6, '["detail", "rack-focus", "shadow"]', 'slow'),
('triumph', 'Triumph', 'Victory or achievement', NULL, 9, '["low-angle", "crane-up", "epic"]', 'medium'),
('tragedy', 'Tragedy', 'Loss or devastating moment', NULL, 9, '["high-angle", "close-up", "slow"]', 'slow'),
('wonder', 'Wonder', 'Awe-inspiring moment', NULL, 7, '["wide", "tilt-up", "slow-reveal"]', 'slow'),
('fear', 'Fear', 'Frightening moment', NULL, 8, '["pov", "dutch", "quick-cuts"]', 'fast');
```

#### Admin Panel Features
- Map beats to three-act structure visually
- Intensity slider (1-10)
- Auto-suggest shot types based on beat
- Color mood picker
- Pacing recommendation
- Story arc visualization

---

### Upgrade 4: Three-Act Structure Configuration

**Goal:** Admin-controlled story structure distribution.

#### Database Schema
```sql
CREATE TABLE vw_story_structures (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    structure_type ENUM('three_act', 'five_act', 'hero_journey', 'save_the_cat', 'shorts', 'custom') NOT NULL,

    -- Act distribution (percentages, must sum to 100)
    act_distribution JSON NOT NULL,
    /* Example for three-act:
    {
        "act1": {"percentage": 25, "label": "Setup", "beats": ["hook", "introduction", "inciting-incident"]},
        "act2": {"percentage": 50, "label": "Confrontation", "beats": ["exploration", "midpoint-twist", "all-is-lost"]},
        "act3": {"percentage": 25, "label": "Resolution", "beats": ["climax", "resolution", "final-image"]}
    }
    */

    -- Pacing curve (for visual editor)
    pacing_curve JSON, -- [[0, 3], [25, 6], [50, 8], [75, 10], [100, 5]] - percentage, intensity

    best_for JSON, -- ["drama", "thriller", "documentary"]
    min_scenes INT DEFAULT 3,
    max_scenes INT DEFAULT 20,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Seed with professional structures
INSERT INTO vw_story_structures (slug, name, description, structure_type, act_distribution, best_for, is_default) VALUES
('classic-three-act', 'Classic Three-Act', 'Traditional Hollywood structure', 'three_act',
    '{"act1": {"percentage": 25, "label": "Setup", "beats": ["hook", "introduction", "inciting-incident", "acceptance"]}, "act2": {"percentage": 50, "label": "Confrontation", "beats": ["exploration", "fun-games", "midpoint-twist", "stakes-raised", "all-is-lost", "dark-moment"]}, "act3": {"percentage": 25, "label": "Resolution", "beats": ["realization", "climax", "resolution", "final-image"]}}',
    '["drama", "thriller", "action", "romance"]', TRUE),

('shorts-structure', 'Short-Form Structure', 'Optimized for 15-60 second content', 'shorts',
    '{"hook": {"percentage": 10, "label": "Hook", "beats": ["hook"]}, "content": {"percentage": 70, "label": "Content", "beats": ["fun-games", "action"]}, "cta": {"percentage": 20, "label": "CTA/Payoff", "beats": ["climax", "resolution"]}}',
    '["social", "viral", "commercial"]', FALSE),

('hero-journey', 'Hero''s Journey', '12-stage mythic structure', 'hero_journey',
    '{"departure": {"percentage": 25, "label": "Departure", "beats": ["normalcy", "inciting-incident", "refusal", "acceptance"]}, "initiation": {"percentage": 50, "label": "Initiation", "beats": ["exploration", "ally-enemy", "first-challenge", "midpoint-twist", "all-is-lost"]}, "return": {"percentage": 25, "label": "Return", "beats": ["realization", "climax", "resolution", "new-normal"]}}',
    '["fantasy", "adventure", "epic"]', FALSE),

('save-the-cat', 'Save the Cat', 'Blake Snyder''s 15-beat structure', 'save_the_cat',
    '{"act1": {"percentage": 25, "label": "Thesis", "beats": ["hook", "introduction", "inciting-incident", "acceptance"]}, "act2a": {"percentage": 25, "label": "Antithesis Pt 1", "beats": ["fun-games", "ally-enemy"]}, "act2b": {"percentage": 25, "label": "Antithesis Pt 2", "beats": ["midpoint-twist", "all-is-lost", "dark-moment"]}, "act3": {"percentage": 25, "label": "Synthesis", "beats": ["realization", "climax", "final-image"]}}',
    '["screenplay", "drama", "comedy"]', FALSE),

('documentary-arc', 'Documentary Arc', 'Non-fiction narrative structure', 'custom',
    '{"setup": {"percentage": 20, "label": "Context", "beats": ["hook", "introduction"]}, "exploration": {"percentage": 40, "label": "Investigation", "beats": ["exploration", "first-challenge", "stakes-raised"]}, "revelation": {"percentage": 25, "label": "Discovery", "beats": ["midpoint-twist", "realization"]}, "conclusion": {"percentage": 15, "label": "Impact", "beats": ["resolution", "final-image"]}}',
    '["documentary", "educational", "explainer"]', FALSE);
```

#### Admin Panel Features
- Visual story arc editor (drag points on curve)
- Beat placement on timeline
- Act percentage sliders
- Preview beat distribution for N scenes
- Clone and customize structures
- Assign to content types

---

### Upgrade 5: Professional Lens & Camera Specifications

**Goal:** Admin-manageable camera/lens presets for prompt generation.

#### Database Schema
```sql
CREATE TABLE vw_camera_specs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category ENUM('lens', 'camera_body', 'film_stock', 'format') NOT NULL,

    -- For lenses
    focal_length VARCHAR(50), -- "85mm", "24-70mm"
    aperture VARCHAR(20), -- "f/1.4", "f/2.8"
    characteristics TEXT, -- "creamy bokeh, sharp center, vintage rendering"

    -- For film stocks/looks
    look_description TEXT, -- "Kodak Portra 400: soft pastels, natural skin"

    -- Prompt snippets
    prompt_text TEXT NOT NULL, -- What to add to AI prompts

    best_for_shots JSON, -- ["portrait", "close-up", "medium"]
    best_for_genres JSON, -- ["drama", "romance", "documentary"]

    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Seed with professional camera specs
INSERT INTO vw_camera_specs (slug, name, category, focal_length, aperture, characteristics, prompt_text, best_for_shots) VALUES
-- Wide lenses
('ultra-wide-14', 'Ultra-Wide 14mm', 'lens', '14mm', 'f/2.8', 'Extreme perspective, dramatic distortion, immersive', 'shot on ultra-wide 14mm lens, dramatic perspective, expansive field of view', '["extreme-wide", "establishing", "pov"]'),
('wide-24', 'Wide Angle 24mm', 'lens', '24mm', 'f/1.4', 'Environmental context, slight distortion, storytelling', 'shot on 24mm wide-angle lens, environmental storytelling, cinematic depth', '["wide", "establishing", "tracking"]'),
('wide-35', 'Standard Wide 35mm', 'lens', '35mm', 'f/1.4', 'Natural perspective, versatile, documentary feel', 'shot on 35mm lens, natural perspective, documentary authenticity', '["wide", "medium-wide", "tracking", "master"]'),

-- Standard lenses
('standard-50', 'Standard 50mm', 'lens', '50mm', 'f/1.2', 'Human eye perspective, natural, classic', 'shot on 50mm lens at f/1.2, natural human perspective, classic cinematography', '["medium", "medium-close", "dialogue"]'),

-- Portrait/telephoto lenses
('portrait-85', 'Portrait 85mm', 'lens', '85mm', 'f/1.4', 'Flattering compression, creamy bokeh, intimate', 'shot on 85mm portrait lens at f/1.4, beautiful bokeh, intimate compression', '["close-up", "medium-close", "portrait"]'),
('telephoto-135', 'Telephoto 135mm', 'lens', '135mm', 'f/2', 'Strong compression, isolation, voyeuristic', 'shot on 135mm telephoto lens, compressed perspective, subject isolation', '["close-up", "big-close-up", "reaction"]'),
('long-tele-200', 'Long Telephoto 200mm', 'lens', '200mm', 'f/2.8', 'Extreme compression, surveillance feel, abstract', 'shot on 200mm telephoto lens, extreme compression, isolated subject', '["extreme-close-up", "detail", "surveillance"]'),

-- Specialty lenses
('macro', 'Macro Lens', 'lens', '100mm macro', 'f/2.8', 'Extreme detail, 1:1 magnification, texture reveal', 'shot on macro lens, extreme detail, texture visible, 1:1 magnification', '["extreme-close-up", "insert", "detail"]'),
('anamorphic', 'Anamorphic', 'lens', '50mm anamorphic', 'T2', 'Oval bokeh, lens flares, cinematic widescreen', 'shot on anamorphic lens, oval bokeh, horizontal lens flares, 2.39:1 cinematic', '["establishing", "wide", "medium"]'),
('tilt-shift', 'Tilt-Shift', 'lens', '45mm tilt-shift', 'f/2.8', 'Selective focus plane, miniature effect', 'shot on tilt-shift lens, selective focus plane, dreamlike quality', '["establishing", "aerial", "special"]'),

-- Film stocks/digital looks
('kodak-portra', 'Kodak Portra 400', 'film_stock', NULL, NULL, 'Soft pastels, natural skin tones, fine grain', 'Kodak Portra 400 film aesthetic, soft pastels, beautiful skin tones, subtle grain', '["portrait", "drama", "romance"]'),
('kodak-vision3', 'Kodak Vision3 500T', 'film_stock', NULL, NULL, 'Tungsten balanced, cinema standard, rich colors', 'Kodak Vision3 500T cinema film look, rich colors, professional motion picture quality', '["cinematic", "drama", "thriller"]'),
('fuji-velvia', 'Fuji Velvia 50', 'film_stock', NULL, NULL, 'Saturated colors, high contrast, vivid', 'Fuji Velvia film aesthetic, vivid saturated colors, high contrast, punchy', '["landscape", "commercial", "vibrant"]'),
('ilford-hp5', 'Ilford HP5 B&W', 'film_stock', NULL, NULL, 'Classic black and white, rich tones, grain', 'Ilford HP5 black and white film aesthetic, rich contrast, classic grain', '["noir", "documentary", "artistic"]');
```

#### Admin Panel Features
- Lens library with visual examples
- Film stock presets with sample images
- Assign to shot types automatically
- Combine lens + film stock presets
- Custom prompt text editor

---

### Upgrade 6: AI Prompt Templates System

**Goal:** Admin-editable prompt templates with variable system.

#### Database Schema
```sql
CREATE TABLE vw_prompt_templates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category ENUM('shot', 'scene', 'transition', 'style', 'technical') NOT NULL,

    template TEXT NOT NULL,
    /* Variables available:
       {subject} - Main subject description
       {environment} - Scene environment
       {shot_type} - Current shot type name
       {camera_movement} - Movement description
       {lens_spec} - Lens specification from camera_specs
       {color_grade} - Color grading from genre
       {lighting} - Lighting description from genre
       {atmosphere} - Atmospheric elements from genre
       {style} - Style description
       {emotional_beat} - Current emotional beat
       {duration} - Shot duration
       {aspect_ratio} - Video aspect ratio
    */

    required_variables JSON, -- ["subject", "shot_type"]
    optional_variables JSON, -- ["lens_spec", "color_grade"]

    max_words INT DEFAULT 100, -- Target word count

    example_output TEXT, -- Sample of what this template produces

    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Seed with professional templates
INSERT INTO vw_prompt_templates (slug, name, category, template, required_variables, max_words, is_default) VALUES
('cinematic-shot', 'Cinematic Shot', 'shot',
    '{subject} in {environment}. {shot_type} shot, {lens_spec}. {camera_movement}. {lighting}. {color_grade}. {style}. Cinematic quality, film grain, shallow depth of field.',
    '["subject", "environment", "shot_type"]', 80, TRUE),

('documentary-shot', 'Documentary Shot', 'shot',
    '{subject}. {shot_type} framing with {camera_movement}. Natural lighting, authentic environment. Documentary realism, observational style. {atmosphere}.',
    '["subject", "shot_type", "camera_movement"]', 60, FALSE),

('action-shot', 'Action Shot', 'shot',
    'Dynamic {shot_type} of {subject}. {camera_movement} tracking the action. High energy, motion blur on background. {lens_spec}. {color_grade}. Blockbuster cinematography.',
    '["subject", "shot_type"]', 70, FALSE),

('horror-shot', 'Horror Shot', 'shot',
    '{shot_type} of {subject}, {camera_movement}. {lighting}. Unsettling atmosphere, shadows encroaching. {color_grade}. Dread-inducing composition, negative space threatening.',
    '["subject", "shot_type"]', 75, FALSE),

('commercial-shot', 'Commercial Shot', 'shot',
    'Clean {shot_type} of {subject}. {lens_spec}, crisp focus. Professional lighting, polished production value. {color_grade}. Premium quality, advertising aesthetic.',
    '["subject", "shot_type"]', 60, FALSE),

('transition-match', 'Match Cut Transition', 'transition',
    'Seamless transition from {from_subject} to {to_subject}, matching {match_element}. Smooth visual continuity, professional editing rhythm.',
    '["from_subject", "to_subject", "match_element"]', 40, FALSE);
```

#### Admin Panel Features
- Template editor with syntax highlighting
- Variable autocomplete
- Live preview with sample data
- Word count checker
- A/B test different templates
- Template versioning

---

### Upgrade 7: Quality Control Settings

**Goal:** Admin-configurable quality thresholds and validation rules.

#### Database Schema
```sql
CREATE TABLE vw_quality_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value JSON NOT NULL,
    category ENUM('prompts', 'shots', 'scenes', 'pacing', 'technical') NOT NULL,
    description TEXT,
    updated_at TIMESTAMP
);

INSERT INTO vw_quality_settings (setting_key, setting_value, category, description) VALUES
-- Prompt quality
('prompt_min_words', '{"value": 30}', 'prompts', 'Minimum words for AI prompts'),
('prompt_max_words', '{"value": 100}', 'prompts', 'Maximum words for AI prompts (Sora optimal: 50-100)'),
('prompt_required_elements', '{"value": ["subject", "camera", "lighting"]}', 'prompts', 'Required elements in every prompt'),

-- Shot rules
('min_shots_per_scene', '{"value": 3}', 'shots', 'Minimum shots when decomposing scene'),
('max_shots_per_scene', '{"value": 8}', 'shots', 'Maximum shots when decomposing scene'),
('shot_duration_min', '{"value": 3}', 'shots', 'Minimum shot duration in seconds'),
('shot_duration_max', '{"value": 10}', 'shots', 'Maximum shot duration in seconds'),
('camera_angle_rule', '{"value": 30}', 'shots', '30-degree rule: minimum angle change between consecutive shots'),

-- Scene rules
('scene_duration_min', '{"value": 15}', 'scenes', 'Minimum scene duration in seconds'),
('scene_duration_max', '{"value": 60}', 'scenes', 'Maximum scene duration in seconds'),

-- Pacing rules
('pacing_variety_threshold', '{"value": 0.3}', 'pacing', 'Minimum variety in shot types (0-1)'),
('consecutive_same_shot_max', '{"value": 2}', 'pacing', 'Maximum same shot type in a row'),

-- Technical
('default_fps', '{"value": 30}', 'technical', 'Default frames per second'),
('default_resolution', '{"value": "1080p"}', 'technical', 'Default video resolution'),
('frame_chain_enabled', '{"value": true}', 'technical', 'Enable frame chain workflow by default');
```

#### Admin Panel Features
- Grouped settings by category
- Validation rules editor
- Test prompt against rules
- Quality score calculator
- Warning vs Error thresholds

---

## Admin Panel Implementation

### New Admin Routes
```php
// routes/admin.php additions
Route::prefix('video-wizard')->name('admin.video-wizard.')->group(function () {
    // Existing routes...

    // Genre Presets
    Route::resource('genres', GenrePresetController::class);
    Route::post('genres/{genre}/clone', [GenrePresetController::class, 'clone'])->name('genres.clone');
    Route::post('genres/reorder', [GenrePresetController::class, 'reorder'])->name('genres.reorder');

    // Shot Types
    Route::resource('shot-types', ShotTypeController::class);
    Route::post('shot-types/bulk-toggle', [ShotTypeController::class, 'bulkToggle'])->name('shot-types.bulk-toggle');

    // Emotional Beats
    Route::resource('emotional-beats', EmotionalBeatController::class);
    Route::get('story-arc-editor', [EmotionalBeatController::class, 'arcEditor'])->name('emotional-beats.arc-editor');

    // Story Structures
    Route::resource('story-structures', StoryStructureController::class);
    Route::post('story-structures/{structure}/preview', [StoryStructureController::class, 'preview'])->name('story-structures.preview');

    // Camera Specs
    Route::resource('camera-specs', CameraSpecController::class);
    Route::get('lens-library', [CameraSpecController::class, 'lensLibrary'])->name('camera-specs.lens-library');

    // Prompt Templates
    Route::resource('prompt-templates', PromptTemplateController::class);
    Route::post('prompt-templates/{template}/test', [PromptTemplateController::class, 'test'])->name('prompt-templates.test');

    // Quality Settings
    Route::get('quality-settings', [QualitySettingsController::class, 'index'])->name('quality-settings.index');
    Route::put('quality-settings', [QualitySettingsController::class, 'update'])->name('quality-settings.update');
});
```

### Admin Navigation Menu
```php
// Add to admin sidebar
'video-wizard-cinematography' => [
    'label' => 'Cinematography',
    'icon' => 'fa-solid fa-film',
    'children' => [
        'genres' => ['label' => 'Genre Presets', 'route' => 'admin.video-wizard.genres.index'],
        'shot-types' => ['label' => 'Shot Types', 'route' => 'admin.video-wizard.shot-types.index'],
        'emotional-beats' => ['label' => 'Emotional Beats', 'route' => 'admin.video-wizard.emotional-beats.index'],
        'story-structures' => ['label' => 'Story Structures', 'route' => 'admin.video-wizard.story-structures.index'],
        'camera-specs' => ['label' => 'Camera & Lenses', 'route' => 'admin.video-wizard.camera-specs.index'],
        'prompt-templates' => ['label' => 'Prompt Templates', 'route' => 'admin.video-wizard.prompt-templates.index'],
        'quality-settings' => ['label' => 'Quality Settings', 'route' => 'admin.video-wizard.quality-settings.index'],
    ],
],
```

---

## Implementation Priority

| Priority | Component | Impact | Effort |
|----------|-----------|--------|--------|
| 1 | Genre Presets Admin | High - Enables genre customization | Medium |
| 2 | Shot Types Admin | High - 50+ professional shots | Medium |
| 3 | Prompt Templates | High - Better AI output | Low |
| 4 | Emotional Beats | Medium - Narrative coherence | Medium |
| 5 | Camera Specs | Medium - Professional look | Low |
| 6 | Story Structures | Medium - Better pacing | Medium |
| 7 | Quality Settings | Low - Fine-tuning | Low |

---

## Migration Strategy

### Phase 1: Database & Models
1. Create migrations for all new tables
2. Create Eloquent models with relationships
3. Create seeders with professional defaults
4. Migrate existing `GENRE_PRESETS` constant to database

### Phase 2: Admin Controllers & Views
1. Create admin controllers with CRUD operations
2. Create Blade views with modern UI (cards, drag-drop, previews)
3. Add validation and authorization
4. Implement bulk operations

### Phase 3: Integration
1. Update `VideoWizard.php` to read from database instead of constants
2. Add caching layer for performance
3. Create service classes for complex logic
4. Update existing functionality to use new system

### Phase 4: User-Facing Features
1. Genre selector in wizard with visual previews
2. Shot type recommendations based on emotional beat
3. Story structure picker with visual arc
4. Real-time prompt preview

---

## Key Technical Notes

1. **The existing `GENRE_PRESETS` constant in VideoWizard.php** should become the seed data for the database table
2. **Frame Chain workflow** is already implemented - new system should integrate with it
3. **Multi-shot decomposition** is working - shot types from database should feed into it
4. **Admin settings page exists** at `/admin/video-wizard/settings` - extend this navigation

---

## Research Sources Referenced
- StudioBinder's Camera Shots Guide (50+ shot types)
- Sora 2 Prompt Best Practices (50-100 words optimal)
- MasterClass Three-Act Structure
- No Film School AI Prompts for Filmmakers
- 30-degree rule (cinematography standard)
