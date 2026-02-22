<?php

namespace Modules\AppVideoWizard\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppVideoWizard\Models\VwPrompt;

class VwPromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prompts = [
            [
                'slug' => 'script_generation',
                'name' => 'Script Generation',
                'description' => 'Main prompt for generating video scripts with scenes, character dialogue, and visual descriptions. Used for standard videos up to 5 minutes.',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'variables' => ['topic', 'tone', 'toneGuide', 'contentDepth', 'depthGuide', 'duration', 'minutes', 'targetWords', 'sceneCount', 'wordsPerScene', 'avgSceneDuration', 'additionalInstructions'],
                'prompt_template' => <<<'PROMPT'
You are an expert video scriptwriter creating a {{minutes}}-minute video script.

TOPIC: {{topic}}
TONE: {{tone}} - {{toneGuide}}
CONTENT DEPTH: {{contentDepth}} - {{depthGuide}}
TOTAL DURATION: {{duration}} seconds ({{minutes}} minutes)
TARGET WORD COUNT: {{targetWords}} words
NUMBER OF SCENES: {{sceneCount}}
WORDS PER SCENE: ~{{wordsPerScene}}
SCENE DURATION: ~{{avgSceneDuration}} seconds each

{{additionalInstructions}}

SCRIPT STRUCTURE:
1. HOOK (first 5 seconds) - Grab attention immediately with a bold statement or question
2. INTRODUCTION (10-15 seconds) - Set expectations for what viewers will learn
3. MAIN CONTENT ({{sceneCount}} scenes) - Deliver value with retention hooks every 30-45 seconds
4. CALL TO ACTION (5-10 seconds) - Clear next step for viewer

RESPOND WITH ONLY THIS JSON (no markdown code blocks, no explanation, just pure JSON):
{
  "title": "SEO-optimized title (max 60 chars)",
  "hook": "Attention-grabbing opening (5-10 words)",
  "scenes": [
    {
      "id": "scene-1",
      "title": "Scene title",
      "narration": "Character dialogue and speech segments ({{wordsPerScene}} words)",
      "visualDescription": "Detailed visual for AI image generation (describe setting, mood, colors, lighting, composition)",
      "duration": {{avgSceneDuration}},
      "kenBurns": {
        "startScale": 1.0,
        "endScale": 1.15,
        "startX": 0.5,
        "startY": 0.5,
        "endX": 0.5,
        "endY": 0.4
      }
    }
  ],
  "cta": "Clear call to action",
  "totalDuration": {{duration}},
  "wordCount": {{targetWords}}
}

CRITICAL REQUIREMENTS:
- Generate EXACTLY {{sceneCount}} scenes
- Each scene dialogue/speech MUST be approximately {{wordsPerScene}} words
- Total dialogue should equal approximately {{targetWords}} words
- Visual descriptions must be detailed enough for AI image generation
- Include varied Ken Burns movements (scale, position changes)
- Scene durations should add up to approximately {{duration}} seconds
- NO markdown formatting - output raw JSON only
PROMPT
            ],

            [
                'slug' => 'script_outline',
                'name' => 'Script Outline',
                'description' => 'Generates an outline/structure for long-form videos (over 5 minutes). Creates sections with key points and engagement hooks.',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'variables' => ['topic', 'tone', 'duration', 'minutes', 'sectionCount', 'additionalInstructions'],
                'prompt_template' => <<<'PROMPT'
You are a professional video content strategist. Create a detailed outline for a {{minutes}}-minute video.

TOPIC: {{topic}}
TONE: {{tone}}
TOTAL DURATION: {{duration}} seconds ({{minutes}} minutes)
SECTIONS NEEDED: {{sectionCount}}

{{additionalInstructions}}

Create an outline that divides this video into {{sectionCount}} main sections. Each section should:
- Have a clear focus and purpose
- Build on the previous section
- Include engagement hooks every 30-45 seconds

RESPOND WITH ONLY THIS JSON (no markdown, no explanation):
{
  "title": "SEO-optimized video title (max 60 chars)",
  "hook": "Attention-grabbing opening line (5-10 words)",
  "sections": [
    {
      "title": "Section title",
      "focus": "What this section covers",
      "duration": 90,
      "keyPoints": ["point 1", "point 2", "point 3"],
      "engagementHook": "Question or statement to keep viewers watching"
    }
  ],
  "cta": "Call to action text"
}
PROMPT
            ],

            [
                'slug' => 'section_scenes',
                'name' => 'Section Scenes',
                'description' => 'Generates detailed scenes for a specific section of a long-form video. Creates character dialogue and visual descriptions for each scene.',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 3000,
                'variables' => ['topic', 'sectionTitle', 'sectionFocus', 'sectionDuration', 'sceneCount', 'avgSceneDuration', 'tone', 'keyPointsText'],
                'prompt_template' => <<<'PROMPT'
You are an expert video script writer. Create {{sceneCount}} detailed scenes for a video section.

OVERALL TOPIC: {{topic}}
SECTION: {{sectionTitle}}
SECTION FOCUS: {{sectionFocus}}
SECTION DURATION: {{sectionDuration}} seconds
SCENES NEEDED: {{sceneCount}}
AVERAGE SCENE DURATION: {{avgSceneDuration}} seconds
TONE: {{tone}}

KEY POINTS TO COVER:
{{keyPointsText}}

RESPOND WITH ONLY THIS JSON (no markdown, no explanation):
{
  "scenes": [
    {
      "id": "scene-1",
      "title": "Scene title (descriptive)",
      "narration": "Character dialogue and action (25-50 words using CHARACTER: format)",
      "visualDescription": "Detailed visual description for AI image generation (describe setting, mood, colors, composition)",
      "duration": {{avgSceneDuration}}
    }
  ]
}

REQUIREMENTS:
- Each scene should contain character dialogue (CHARACTER: text format) or monologue (25-50 words)
- Visual descriptions must be detailed and specific for image generation
- Include mood, lighting, colors, and composition details in visuals
- Dialogue should flow naturally from scene to scene
PROMPT
            ],

            [
                'slug' => 'scene_regenerate',
                'name' => 'Scene Regeneration',
                'description' => 'Regenerates a single scene with fresh content while maintaining the video theme and connection to the overall topic.',
                'model' => 'gpt-4',
                'temperature' => 0.8,
                'max_tokens' => 1000,
                'variables' => ['topic', 'tone', 'sceneIndex', 'existingTitle', 'existingDuration', 'existingId'],
                'prompt_template' => <<<'PROMPT'
You are an expert video scriptwriter. Regenerate this scene with fresh content while maintaining the video's theme.

TOPIC: {{topic}}
TONE: {{tone}}
SCENE NUMBER: {{sceneIndex}}
CURRENT SCENE TITLE: {{existingTitle}}
CURRENT DURATION: {{existingDuration}} seconds

Generate a new version of this scene with:
- Fresh character dialogue/monologue that fits the same duration
- New visual description for AI image generation
- Maintain connection to the overall topic

RESPOND WITH ONLY THIS JSON (no markdown):
{
  "id": "{{existingId}}",
  "title": "New scene title",
  "narration": "Character dialogue and speech (match duration, use CHARACTER: format)",
  "visualDescription": "Detailed visual for AI image generation",
  "visualPrompt": "Concise prompt for image AI (50-100 words)",
  "voiceover": {
    "enabled": true,
    "text": "",
    "voiceId": null,
    "status": "pending"
  },
  "duration": {{existingDuration}},
  "mood": "cinematic",
  "status": "draft"
}
PROMPT
            ],

            [
                'slug' => 'visual_prompt',
                'name' => 'Visual Prompt Generation',
                'description' => 'Generates detailed visual prompts for AI image generation based on scene dialogue. Optimized for Stable Diffusion/DALL-E style prompts.',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 500,
                'variables' => ['narration', 'conceptContext', 'styleContext', 'mood', 'productionType', 'aspectRatio'],
                'prompt_template' => <<<'PROMPT'
You are a cinematographer creating a visual prompt for AI image generation.

SCENE DIALOGUE: {{narration}}
{{conceptContext}}
{{styleContext}}
MOOD: {{mood}}
PRODUCTION TYPE: {{productionType}}
ASPECT RATIO: {{aspectRatio}}

Create a detailed visual prompt that:
1. Captures the essence of the scene and dialogue visually
2. Includes specific details: lighting, colors, composition, camera angle
3. Sets the appropriate mood and atmosphere
4. Is optimized for AI image generation (Stable Diffusion/DALL-E style)

RESPOND WITH ONLY THE PROMPT TEXT (no JSON, no explanation, just the visual description):
PROMPT
            ],

            [
                'slug' => 'script_improve',
                'name' => 'Script Improvement',
                'description' => 'Improves and refines an existing script based on specific instructions. Maintains the original structure while enhancing content.',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'variables' => ['scriptJson', 'instruction'],
                'prompt_template' => <<<'PROMPT'
You are an expert video script editor. Improve the following script based on the instruction.

CURRENT SCRIPT:
{{scriptJson}}

INSTRUCTION: {{instruction}}

RESPOND WITH ONLY THE IMPROVED JSON (no markdown, no explanation):
PROMPT
            ],

            [
                'slug' => 'story_bible_generation',
                'name' => 'Story Bible Generation',
                'description' => 'Generates a comprehensive Story Bible in a single pass. Contains characters, locations, visual style, narrative structure, and all constraints for consistent script generation.',
                'model' => 'grok-4',
                'temperature' => 0.7,
                'max_tokens' => 16000,
                'variables' => ['concept', 'duration', 'minutes', 'storyType', 'structureTemplate', 'structureDescription', 'additionalInstructions'],
                'prompt_template' => <<<'PROMPT'
You are a master storyteller and world-builder creating a comprehensive STORY BIBLE for a {{minutes}}-minute {{storyType}} video.

A Story Bible is the "DNA" of the project - it defines EVERYTHING about the story world before any script is written. Every future generation (scripts, scenes, images, dialogue) MUST conform to this Bible.

CONCEPT: {{concept}}
DURATION: {{duration}} seconds ({{minutes}} minutes)
STORY TYPE: {{storyType}}

NARRATIVE STRUCTURE: {{structureTemplate}}
{{structureDescription}}

{{additionalInstructions}}

Generate a COMPLETE Story Bible with:

1. **TITLE & LOGLINE**
   - SEO-optimized title (max 60 characters)
   - Compelling one-sentence logline that captures the essence

2. **THEME & TONE**
   - Core theme (the deeper message or lesson)
   - Tone (emotional quality: dramatic, comedic, inspirational, etc.)
   - Genre (documentary, narrative, educational, promotional, etc.)

3. **NARRATIVE STRUCTURE (ACTS)**
   Based on the {{structureTemplate}} structure, define each act:
   - Act title and purpose
   - Key beats/events that must occur
   - Emotional arc for this act
   - Approximate duration

4. **CHARACTERS** (if applicable for story type)
   For each character define:
   - Name and role (protagonist, mentor, antagonist, etc.)
   - Visual description (detailed for AI image consistency)
   - Core traits (3-5 defining characteristics)
   - Character arc (how they change)
   - Voice/speaking style

5. **LOCATIONS**
   For each setting define:
   - Name and type (interior/exterior)
   - Visual description (colors, lighting, atmosphere)
   - Time of day and weather
   - Mood and significance to story

6. **VISUAL STYLE GUIDE**
   - Visual mode (cinematic/documentary/animated/stock)
   - Color palette (3-5 primary colors)
   - Lighting style (natural, dramatic, soft, high-contrast)
   - Camera language (wide establishing, intimate close-ups, etc.)
   - Visual motifs or recurring imagery

7. **PACING & RHYTHM**
   - Overall pace (fast/medium/slow)
   - Scene transition style
   - Music/audio mood suggestions
   - Engagement hooks (where to place retention moments)

RESPOND WITH ONLY THIS JSON (no markdown code blocks, no explanation, just pure JSON):
{
  "title": "SEO-optimized title (max 60 chars)",
  "logline": "One compelling sentence capturing the story essence",
  "theme": "The deeper message or lesson",
  "tone": "Emotional quality of the piece",
  "genre": "Documentary/Narrative/Educational/Promotional",
  "acts": [
    {
      "number": 1,
      "title": "Act title",
      "purpose": "What this act accomplishes",
      "beats": ["Key event 1", "Key event 2", "Key event 3"],
      "emotionalArc": "Emotional journey in this act",
      "duration": 60
    }
  ],
  "characters": [
    {
      "name": "Character name",
      "role": "protagonist/antagonist/mentor/ally/etc",
      "visualDescription": "Detailed visual for AI consistency: age, appearance, clothing, distinctive features",
      "traits": ["trait1", "trait2", "trait3"],
      "arc": "How this character changes",
      "voiceStyle": "How they speak",
      "voice": {"id": null, "gender": "female/male"}
    }
  ],
  "locations": [
    {
      "name": "Location name",
      "type": "interior/exterior",
      "visualDescription": "Detailed setting description",
      "timeOfDay": "morning/afternoon/evening/night",
      "atmosphere": "Mood and feel of this place",
      "significance": "Why this location matters"
    }
  ],
  "visualStyle": {
    "mode": "cinematic/documentary/animated/stock",
    "colorPalette": ["#color1", "#color2", "#color3"],
    "colorDescription": "Description of the color mood",
    "lighting": "Lighting style description",
    "cameraLanguage": "Camera approach and movement style",
    "motifs": ["Visual motif 1", "Visual motif 2"]
  },
  "pacing": {
    "overallPace": "fast/medium/slow",
    "transitionStyle": "Description of how scenes connect",
    "musicMood": "Audio/music suggestions",
    "engagementHooks": [
      {"position": 30, "type": "question/reveal/cliffhanger", "description": "Hook description"}
    ]
  },
  "generatedAt": "{{generatedAt}}"
}

CRITICAL REQUIREMENTS:
- This Story Bible is AUTHORITATIVE - all future generations MUST conform to it
- Character visual descriptions must be specific enough for AI image consistency
- Location descriptions must include lighting, colors, and atmosphere
- Acts must cover the full {{duration}} second duration
- Generate appropriate number of characters/locations for a {{minutes}}-minute {{storyType}}
- NO markdown formatting - output raw JSON only
PROMPT
            ],

            [
                'slug' => 'story_bible_character_add',
                'name' => 'Story Bible Character Addition',
                'description' => 'Adds a new character to an existing Story Bible, ensuring consistency with established visual style and narrative.',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'variables' => ['existingBible', 'characterDescription', 'role'],
                'prompt_template' => <<<'PROMPT'
You are expanding a Story Bible by adding a new character that fits the established world.

EXISTING STORY BIBLE:
{{existingBible}}

NEW CHARACTER REQUEST:
Role: {{role}}
Description: {{characterDescription}}

Create a new character that:
1. Fits the established visual style and tone
2. Complements existing characters
3. Has consistent visual description for AI generation

RESPOND WITH ONLY THIS JSON (no markdown):
{
  "name": "Character name",
  "role": "{{role}}",
  "visualDescription": "Detailed visual matching story bible style",
  "traits": ["trait1", "trait2", "trait3"],
  "arc": "Character development",
  "voiceStyle": "Speaking pattern",
  "voice": {"id": null, "gender": "female/male"}
}
PROMPT
            ],

            [
                'slug' => 'story_bible_location_add',
                'name' => 'Story Bible Location Addition',
                'description' => 'Adds a new location to an existing Story Bible, ensuring visual consistency with established style guide.',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'variables' => ['existingBible', 'locationDescription', 'locationType'],
                'prompt_template' => <<<'PROMPT'
You are expanding a Story Bible by adding a new location that fits the established world.

EXISTING STORY BIBLE:
{{existingBible}}

NEW LOCATION REQUEST:
Type: {{locationType}}
Description: {{locationDescription}}

Create a new location that:
1. Matches the established visual style and color palette
2. Fits the story's tone and atmosphere
3. Has consistent visual description for AI generation

RESPOND WITH ONLY THIS JSON (no markdown):
{
  "name": "Location name",
  "type": "{{locationType}}",
  "visualDescription": "Detailed setting matching story bible style",
  "timeOfDay": "morning/afternoon/evening/night",
  "atmosphere": "Mood and feel",
  "significance": "Role in the story"
}
PROMPT
            ],

            // =============================================
            // CONCEPT DEVELOPMENT PROMPTS
            // =============================================

            [
                'slug' => 'concept_improve',
                'name' => 'Concept Improvement',
                'description' => 'Transforms a rough idea into a refined, detailed video concept with characters, world building, and mood analysis.',
                'model' => 'grok-4',
                'temperature' => 0.7,
                'max_tokens' => 8000,
                'variables' => ['rawInput', 'typeContext'],
                'prompt_template' => <<<'PROMPT'
You are a creative video concept developer. Transform this rough idea into a refined, detailed concept.

RAW IDEA:
{{rawInput}}

{{typeContext}}

Analyze the idea and return a JSON response with:
{
  "improvedConcept": "A detailed, polished version of the concept (2-3 paragraphs)",
  "logline": "A one-sentence summary that captures the essence",
  "suggestedMood": "The overall mood/atmosphere (e.g., inspiring, mysterious, energetic)",
  "suggestedTone": "The tone (e.g., professional, casual, humorous)",
  "keyElements": ["element1", "element2", "element3"],
  "uniqueElements": ["what makes this unique 1", "what makes this unique 2"],
  "avoidElements": ["cliche to avoid 1", "cliche to avoid 2"],
  "targetAudience": "Description of the ideal viewer",
  "characters": [
    {
      "name": "Character name",
      "role": "protagonist/supporting/narrator",
      "archetype": "hero/mentor/trickster/etc",
      "description": "Brief description"
    }
  ],
  "worldBuilding": {
    "setting": "Where/when the story takes place",
    "rules": ["Any special rules or elements of the world"],
    "atmosphere": "The visual/emotional atmosphere"
  }
}

Be creative but stay true to the core idea. Make suggestions that enhance without completely changing the concept.
PROMPT
            ],

            [
                'slug' => 'concept_variations',
                'name' => 'Concept Variations',
                'description' => 'Generates multiple unique variations of a concept that explore different angles or approaches.',
                'model' => 'grok-4',
                'temperature' => 0.7,
                'max_tokens' => 8000,
                'variables' => ['count', 'concept'],
                'prompt_template' => <<<'PROMPT'
Based on this video concept, generate {{count}} unique variations that explore different angles or approaches:

ORIGINAL CONCEPT:
{{concept}}

Return as JSON array:
[
  {
    "title": "Variation title",
    "concept": "The variation concept",
    "angle": "How this differs from original",
    "strengths": ["strength1", "strength2"]
  }
]
PROMPT
            ],

            [
                'slug' => 'concept_viral_seedance',
                'name' => 'Viral Ideas — Seedance',
                'description' => 'Generates viral 9:16 vertical video concepts optimized for Seedance engine with auto-generated audio from text prompts.',
                'model' => 'grok-4',
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'variables' => ['themeContext', 'count', 'styleModifier', 'chaosModifier', 'structureRules', 'technicalRules', 'templateExample'],
                'prompt_template' => <<<'PROMPT'
You are a viral content specialist who creates massively shareable short-form video concepts.

{{themeContext}}

{{styleModifier}}

{{chaosModifier}}

IMPORTANT: These ideas will be animated using Seedance — an AI model that generates
video + voice + sound effects ALL FROM A TEXT PROMPT. There is no separate audio recording.
The model auto-generates dialogue, environmental sounds, and sound effects from the prompt.
CRITICAL: ABSOLUTELY NO background music in the videoPrompt. NEVER write "music plays", "upbeat music", "beat drops", "soundtrack", or any music reference. Seedance auto-generates audio — any music text causes unwanted background music. Only write dialogue, character sounds (meowing, screaming, yowling), and physical sound effects (crashing, shattering, splashing).

Generate exactly {{count}} unique viral 9:16 vertical video concepts. Each MUST follow the proven viral formula:
- An ANIMAL or quirky CHARACTER in an absurd/funny human situation
- Single continuous shot (NO scene changes, NO transitions)
- 4-12 seconds duration
- Focus on VISUAL COMEDY, physical humor, dramatic reactions, animals in situations
- Short punchy scenes with strong visual hooks
- Environmental sounds and ambient audio (sizzling, splashing, crowd noise)
- Dialogue should be SHORT (1-2 lines max, embedded in scene description)
- Emphasis on MOTION and ACTION (not talking heads)

Mix of MONOLOGUE and DIALOGUE but focus on visual storytelling — Seedance excels at
action scenes, physical comedy, and dramatic moments more than extended conversations.

Each idea MUST include a "videoPrompt" field — a Seedance 1.5 Pro optimized prompt.

VIDEO PROMPT RULES — THIS IS CRITICAL:

WORD COUNT: 150-180 words. This is the proven sweet spot for Seedance 1.5 Pro.
Under 140 words loses critical intensity. Over 200 words gets redundant. Aim for 160-175.

DO NOT describe character appearances (fur color, clothing, accessories) — that goes in "character" and "characters" fields.
The video prompt describes actions, reactions, sounds, voice, and SIZE/SCALE.
Convey emotion through BODY LANGUAGE, not facial expressions — see Face Consistency rule below.

{{structureRules}}

{{technicalRules}}

EXAMPLE — GOOD VIDEO PROMPT (~170 words):
{{templateExample}}

Return ONLY a JSON array (no markdown, no explanation):
[
  {
    "title": "Catchy title (max 6 words)",
    "concept": "One sentence describing the full visual scene",
    "speechType": "monologue" or "dialogue",
    "characters": [
      {"name": "Character Name", "description": "detailed visual description including species, clothing, accessories", "role": "role", "expression": "expression description", "position": "spatial position: foreground/background, left/right/center, facing direction"}
    ],
    "character": "Combined description of ALL main visible characters with their spatial relationship for image generation",
    "imageStartState": "The CALM INITIAL state for the starting image — characters in positions BEFORE action begins. NO chaos, NO flying objects. Just characters in their starting poses.",
    "situation": "One concise sentence: what happens from start to finish focusing on KEY dramatic beats",
    "setting": "Detailed location with specific props, brand elements, decor, lighting",
    "props": "Key visual props in the scene",
    "audioType": "voiceover",
    "audioDescription": "Brief description of what happens (for metadata)",
    "dialogueLines": [
      {"speaker": "Character Name", "text": "Short punchy line"}
    ],
    "videoPrompt": "150-180 word Seedance-optimized prompt following the STRUCTURE and TECHNICAL RULES above. See EXAMPLE above for reference.",
    "cameraFixed": true,
    "mood": "funny" or "absurd" or "wholesome" or "chaotic" or "cute",
    "viralHook": "Why this would go viral (one sentence)"
  }
]
PROMPT
            ],

            [
                'slug' => 'concept_viral_infinitetalk',
                'name' => 'Viral Ideas — InfiniteTalk',
                'description' => 'Generates viral 9:16 vertical video concepts optimized for InfiniteTalk engine with lip-sync from custom voices.',
                'model' => 'grok-4',
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'variables' => ['themeContext', 'count', 'styleModifier', 'chaosModifier'],
                'prompt_template' => <<<'PROMPT'
You are a viral content specialist who creates massively shareable short-form video concepts.

{{themeContext}}

{{styleModifier}}

{{chaosModifier}}

Generate exactly {{count}} unique viral 9:16 vertical video concepts. Each MUST follow the proven viral formula:
- An ANIMAL or quirky CHARACTER in an absurd/funny human situation
- Single continuous shot (NO scene changes, NO transitions)
- 8-10 seconds duration
- Characters' mouths will be LIP-SYNCED to audio

IMPORTANT: Mix of TWO types:
1. DIALOGUE scenes (at least half): TWO characters interacting — e.g., an animal employee and a human customer, a cat boss and a dog intern. The comedy comes from the interaction.
2. MONOLOGUE scenes: One character speaking directly to camera or doing a solo bit.

For DIALOGUE concepts:
- "speechType": "dialogue"
- "characters": array of 2 character objects with name, description, role, expression
- "dialogueLines": array of 3-4 short alternating lines (speaker + text, max 12 words per line)
- The dialogue must be FUNNY — deadpan humor, sarcasm, absurd complaints, unexpected responses

For MONOLOGUE concepts:
- "speechType": "monologue"
- "character": single character description
- "audioDescription": what they say (max 20 words)

For ALL concepts, also specify:
- "audioType": "voiceover" (spoken dialogue/monologue) or "music-lipsync" (character sings)
- Detailed "setting" with specific props, decor, brand elements, and environmental details

Return ONLY a JSON array (no markdown, no explanation):
[
  {
    "title": "Catchy title (max 6 words)",
    "concept": "One sentence describing the full visual scene with all characters",
    "speechType": "dialogue" or "monologue",
    "characters": [
      {"name": "Character Name", "description": "detailed visual description including species, clothing, accessories", "role": "employee/customer/boss/etc", "expression": "deadpan, slightly annoyed", "position": "spatial position: foreground/background, left/right/center, facing direction"},
      {"name": "Character 2 Name", "description": "detailed visual description", "role": "role", "expression": "expression description", "position": "spatial position"}
    ],
    "character": "Combined description of ALL main visible characters with their spatial relationship for image generation",
    "imageStartState": "The CALM INITIAL state for the starting image — characters in positions BEFORE action begins. NO chaos, NO flying objects. Just characters in their starting poses with neutral expressions.",
    "situation": "One concise sentence: what happens from start to finish focusing on KEY dramatic beats",
    "setting": "Detailed location with specific props, brand elements, decor, lighting (e.g., 'Papa John's counter with red pizza boxes, menu boards with pizza images, cash register, drink cups, warm fluorescent lighting')",
    "props": "Key visual props in the scene (e.g., 'open pizza box, green uniform with cap, branded counter')",
    "audioType": "voiceover",
    "audioDescription": "For monologue: the spoken text. For dialogue: brief scene description",
    "dialogueLines": [
      {"speaker": "Character Name", "text": "Short punchy line"},
      {"speaker": "Character 2 Name", "text": "Funny response"}
    ],
    "mood": "funny" or "absurd" or "wholesome" or "chaotic" or "cute",
    "viralHook": "Why this would go viral (one sentence)"
  }
]
PROMPT
            ],

            [
                'slug' => 'concept_seedance_compliance',
                'name' => 'Seedance Compliance Validator',
                'description' => 'AI-powered Seedance 1.5 compliance validator. Scans video prompts against all Seedance rules and returns violations + fixed prompt + score.',
                'model' => 'grok-4',
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'variables' => ['rules', 'wordCount', 'prompt', 'cloneOverride', 'wordCountSection'],
                'prompt_template' => <<<'PROMPT'
You are a Seedance video prompt compliance validator. Scan the prompt below and fix violations.

=== RULES ===
{{rules}}

=== ADDITIONAL RULES ===
- Dialogue in quotes is ALLOWED and should be PRESERVED — it drives audio generation
- Character sounds (meowing, yelling, screaming) are ALLOWED and should be PRESERVED
- Camera style descriptions are ALLOWED (e.g., "chaotic handheld camera")
- Natural adverbs are ALLOWED — do NOT restrict to a fixed set
- Emotional states as part of actions are ALLOWED (e.g., "leans aggressively", "angrily points")
- NO facial micro-expression descriptions (eyes widening, brow furrowing, mouth curving into smile)
- EXCEPTION: "glance", "look", "stare", "gaze" are HEAD/EYE ACTIONS — keep them.
- NO "toward camera", "at the camera", "eyes locked on camera" — rewrite direction without camera mention
- If the prompt is truncated (ends mid-sentence), fix it by completing or trimming to last complete sentence
- Must NOT contain face/identity prefix text like "Maintain face consistency"
- Must NOT contain scene/setting descriptions — the source image already shows the scene
- Must start directly with the first action
- Must end with "Cinematic, photorealistic."
- ABSOLUTELY NO background music mentions (soundtrack, score, beat, rhythm, melody)
{{cloneOverride}}

{{wordCountSection}}

=== PROMPT TO VALIDATE ===
{{prompt}}

=== INSTRUCTIONS ===
1. Scan for violations of the rules above
2. List ALL violations found
3. Provide the COMPLETE fixed prompt with violations corrected
4. Rate compliance 0-100

Return ONLY valid JSON (no markdown, no explanation):
{"score":85,"violations":[{"word":"the violating text","rule":"rule broken","fix":"correction"}],"fixedPrompt":"entire corrected prompt","summary":"one sentence summary"}

CRITICAL: Preserve ALL original actions, dialogue, sounds, and camera descriptions. Only fix genuine violations.
PROMPT
            ],

            [
                'slug' => 'concept_video_analysis',
                'name' => 'Video Analysis (Gemini)',
                'description' => 'Comprehensive video analysis prompt for Gemini native video understanding. Covers characters, setting, action timeline, audio, camera style, and viral formula detection. ~1100 words.',
                'model' => 'gemini-2.5-pro',
                'temperature' => 0.1,
                'max_tokens' => 4000,
                'variables' => [],
                'prompt_template' => <<<'PROMPT'
You are analyzing a short-form video (TikTok/Reels/Shorts). You can see the FULL video with all its temporal flow, motion, and audio cues. Analyze it with EXTREME PRECISION.

CRITICAL INSTRUCTION: You MUST identify every character/creature/animal with 100% accuracy. If you see a monkey, say MONKEY — not "primate" or "creature." If you see a golden retriever, say GOLDEN RETRIEVER — not just "dog." Be as specific as possible about breed, species, and subspecies. NEVER guess or generalize. Describe EXACTLY what you see.

1. CHARACTERS (be EXACT):
   - EXACT species — e.g., "capuchin monkey", "tabby cat", "golden retriever puppy", "adult human male." Do NOT generalize.
   - Fur/skin color, patterns, distinguishing marks
   - Clothing, accessories, colors (be specific: "red baseball cap", not "hat")
   - Facial expression and body language as they CHANGE throughout the video
   - Role: protagonist, supporting, background
   - SPATIAL POSITION: Where is each character relative to others? Who is in the foreground/background? Who faces whom?

   SIZE & SCALE — THIS IS CRITICAL, DO NOT SKIP:
   - What is each character's size RELATIVE to the human/environment? Compare to real-world expectations.
   - Are characters their NORMAL real-world size, or are they digitally MINIATURIZED, ENLARGED, or otherwise scaled?
   - If animals appear SMALLER or LARGER than their real species normally would be, SAY SO EXPLICITLY.
   - Compare character height to nearby objects: doorways, furniture, other characters' body parts (ankle, knee, hip, shoulder).
   - COUNT characters precisely: "exactly 3 cats" or "a line of approximately 12-15 cats" — do NOT say "some cats" or "several cats."
   - If many identical/similar characters form a GROUP, describe the group size, formation pattern, and whether they move in unison or independently.

   CHARACTER-OBJECT INTERACTIONS — WHAT ARE THEY DOING WITH WHAT THEY HOLD:
   - If a character is HOLDING an object, describe HOW they are USING it — not just that they hold it.
   - For MUSICAL INSTRUMENTS: Are characters PLAYING them? Describe the physical playing action. Is the music in the audio COMING FROM their playing?
   - For TOOLS/WEAPONS: Are they swinging, pointing, using them? Describe the action.
   - For FOOD/DRINKS: Are they eating, drinking, spilling? Describe the interaction.

2. SETTING & ENVIRONMENT:
   - Exact location (bathroom, kitchen counter, living room couch, outdoor garden, etc.)
   - Every visible prop and object
   - Lighting type and direction
   - Background details, wall color, floor type, decor
   - Any text, signs, or brand names visible

3. ACTION TIMELINE — THIS IS THE MOST IMPORTANT SECTION:
   You can see the FULL video motion. Describe the COMPLETE temporal progression second by second.
   - 0-2 seconds: What is the initial state?
   - 2-5 seconds: What happens next?
   - 5-8 seconds: Any escalation?
   - 8-12 seconds: Climax? Peak action?
   - 12+ seconds: Resolution or punchline?
   - CRITICAL: Most viral videos have a 2-3 phase arc
   - Describe EVERY physical action with EXACT SECOND timestamps
   - Describe what EACH character does independently at each phase

   CRITICAL — OBJECT CONSEQUENCES WITHIN EACH ACTION BEAT:
   When describing each action beat, ALSO describe what happens to NEARBY OBJECTS as a DIRECT RESULT.
   Include object displacement AS PART OF the action sentence, not as a separate section.

   MOVEMENT QUALITY CHANGES — DO NOT SKIP:
   Does the character's movement TYPE change? Does movement become RHYTHMIC or DANCE-LIKE?

   ACTION INTENSITY RATING: CALM / MODERATE / INTENSE / EXTREME/WILD

3b. AUDIO & SOUND ANALYSIS:
   - Report ONLY sounds you can actually HEAR. Do NOT infer sounds from visual cues.
   - Is there a VOICEOVER/NARRATION?
   - CRITICAL FOR ANIMALS: Only report vocalizations you can genuinely HEAR
   - Is there background music or sound effects?
   - SOUND SOURCE ATTRIBUTION for instruments

4. CAMERA & VISUAL STYLE:
   - Camera angle, movement, shot type
   - Is the camera FIXED or MOVING?
   - Visual style, color palette

5. MOOD & VIRAL FORMULA:
   - Dominant emotion
   - The exact moment/hook that makes it shareable
   - Humor type, pacing

Return your analysis as detailed text. Be EXHAUSTIVE and PRECISE. Accuracy matters more than brevity.
PROMPT
            ],

            [
                'slug' => 'concept_object_displacement',
                'name' => 'Object Displacement Verification',
                'description' => 'Targeted re-query sent to Gemini to verify whether objects on surfaces were displaced during intense action scenes. Used as a post-analysis validation step.',
                'model' => 'gemini-2.5-pro',
                'temperature' => 0.05,
                'max_tokens' => 4000,
                'variables' => ['objectList', 'specificChallenge'],
                'prompt_template' => <<<'PROMPT'
Watch this video frame by frame. The video contains INTENSE physical action.

The following objects were identified on surfaces (counters, tables, shelves, etc.): {{objectList}}
{{specificChallenge}}

YOUR TASK: For EACH of these objects, watch what happens to it during the video. Track it from start to end.

For EACH object, report ONE of:
- DISPLACED: "[object name]" — knocked off/fell/slid/scattered at approximately [timestamp] because [cause]. Landed [where].
- STAYED: "[object name]" — verified it remained in place throughout the video.
- UNCLEAR: "[object name]" — object went out of frame / could not verify.

RULES:
- Check EVERY object listed above, do not skip any.
- When characters move aggressively on or near a surface, objects almost always get displaced. Look carefully.
- Pay special attention to the moments when characters jump, lunge, swat, or land on surfaces.
- If an object disappears from view during action, it was likely displaced — report it as DISPLACED unless you can see it still in place in a later frame.
- Do NOT assume objects stayed in place. VERIFY by looking at the frames AFTER the action.
PROMPT
            ],

            [
                'slug' => 'concept_synthesize',
                'name' => 'Concept Synthesis (Clone)',
                'description' => 'Main synthesis prompt for video cloning. Combines visual analysis + audio transcript into a structured viral concept. Uses system+user message pair.',
                'model' => 'grok-4',
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'variables' => ['phaseCount', 'targetWords', 'visualAnalysis', 'transcriptSection', 'videoPromptInstruction', 'structureRules', 'technicalRules', 'chaosModeSupercharger', 'templateExample'],
                'system_message' => <<<'PROMPT'
You are a Seedance 1.5 Pro video prompt specialist. Your #1 job is generating the "videoPrompt" field — a vivid, natural narrative describing ALL actions in the video.

The analysis contains {{phaseCount}} action phases. Your videoPrompt MUST cover ALL {{phaseCount}} phases — especially the FINAL resolution/departure beat.

WRITING STYLE — NATURAL NARRATIVE:
Write as if you're vividly narrating the scene to someone who can't see it. Use natural, descriptive language.
- INCLUDE dialogue in quotes: yells "How can you ruin this?" or screams "Get off me!"
- INCLUDE character sounds: meows, yells, screams, growls, hisses — these drive accurate audio generation
- INCLUDE camera style when notable: "A chaotic, shaking handheld camera follows the action"
- INCLUDE emotional states as part of actions: "leans aggressively", "angrily points", "desperately struggles"
- INCLUDE specific body parts: "slaps the man's face with its right paw", "claws gripping wildly"
- INCLUDE object displacement as cause-and-effect: "jumps onto the counter and violently knocks over the iced coffee cup"

VIDEOPROMPT RULES:
1. ONE sentence per action phase. {{phaseCount}} phases = {{phaseCount}} sentences. Do NOT skip or merge any phase.
2. Use natural adverbs freely: rapidly, violently, aggressively, wildly, fiercely, powerfully, crazily, intensely, slowly, gently, steadily, desperately, furiously.
3. SPECIFICITY IS CRITICAL: Say "swats at his hand with right paw" not "attacks him".
4. DIALOGUE: Extract key dialogue from the audio transcript and include it in quotes.
5. SOUNDS: Include character vocalizations (meows, yells, screams, growls).
6. WORD COUNT: 120-200 words. Use the full budget.
7. LAST SENTENCE = the FINAL action (resolution/departure/exit), NOT the climax.
8. End with "Cinematic, photorealistic."
9. Every sentence describes a DIFFERENT action — no repetition.
10. BANNED: NO appearance/clothing descriptions. NO background music references. NO facial micro-expressions.

CRITICAL — OBJECT DISPLACEMENT:
If objects are knocked off, scattered, displaced, or sent flying, this MUST appear in the videoPrompt as cause-and-effect within the action sentence.
PROMPT,
                'prompt_template' => <<<'PROMPT'
You are a viral video concept cloner. Your job is to create a FAITHFUL, ACCURATE structured concept from this video analysis. The concept must precisely match what was seen in the original video.

VISUAL ANALYSIS:
{{visualAnalysis}}

{{transcriptSection}}

CRITICAL RULES:
- Use the EXACT species/animal/character type from the visual analysis.
- Use the EXACT setting described.
- Preserve the EXACT mood, humor type, and viral formula.
- Character names can be creative/fun, but species, appearance, setting, and actions must be FAITHFUL.
- ANIMAL SOUNDS — ONLY IF HEARD in the audio.
- VOICEOVER vs CHARACTER SOUNDS: The audio transcript is likely a dubbed voiceover.
- ABSOLUTELY NO background music in the videoPrompt.

{{videoPromptInstruction}}

The "cameraFixed" field MUST ALWAYS be true for social content videos.

Return ONLY a JSON object (no markdown, no explanation):
{
  "title": "Catchy title (max 6 words)",
  "concept": "One sentence describing the EXACT visual scene",
  "speechType": "monologue" or "dialogue",
  "characters": [
    {"name": "Fun Name", "description": "EXACT species + detailed visual description with SIZE/SCALE", "role": "protagonist/supporting/background", "expression": "expression", "position": "EXACT spatial position"}
  ],
  "character": "Combined description of ALL main characters with spatial relationship",
  "imageComposition": "EXACT spatial layout from the reference",
  "imageStartState": "CALM INITIAL state BEFORE action begins",
  "situation": "One concise sentence: start to finish KEY beats",
  "setting": "EXACT location with props, decor, lighting",
  "props": "Key visual props",
  "audioType": "voiceover" or "dialogue" or "sfx" or "silent",
  "audioDescription": "Brief description",
  "dialogueLines": [{"speaker": "Name", "text": "Line"}],
  "videoPrompt": "120-200 word Seedance-optimized prompt — see SYSTEM RULES",
  "cameraFixed": true,
  "mood": "funny/absurd/wholesome/chaotic/cute",
  "viralHook": "Why this would go viral",
  "source": "cloned"
}

=== SEEDANCE VIDEO PROMPT RULES ===
WORD COUNT: 120-200 words. Build as ordered action beats. End with "Cinematic, photorealistic."
NO scene descriptions, NO appearance/clothing — only actions, motions, dialogue, sounds, and camera style.

{{structureRules}}

{{technicalRules}}

{{chaosModeSupercharger}}

STEP-BY-STEP for videoPrompt:
1. List every action phase from the analysis timeline.
2. For EACH phase, write ONE sentence with WHO, WHAT body part, WHAT motion, WHAT direction/result.
3. CHECK FOR OBJECT DISPLACEMENT — include it.
4. LAST sentence = FINAL phase (resolution/departure/exit).
5. ADD DIALOGUE & SOUNDS from the transcript.
6. ADD CAMERA STYLE if notable.
7. End with "Cinematic, photorealistic."
PROMPT
            ],

            [
                'slug' => 'concept_synthesize_expand',
                'name' => 'Concept Synthesis — Expand Short Prompts',
                'description' => 'Expands short videoPrompts (under 100 words) to the target 120-150 word range by adding body parts, directions, emotional states, and dialogue.',
                'model' => 'grok-4',
                'temperature' => 0.7,
                'max_tokens' => 500,
                'variables' => ['wordCount', 'videoPrompt'],
                'system_message' => <<<'PROMPT'
You rewrite Seedance 1.5 Pro video prompts to hit 120-150 words. Keep the SAME actions in the SAME order. Expand short sentences by adding: body parts (arms, paws, chest, fingers), directions (forward, backward, upward), emotional states as part of actions (leans aggressively, angrily points), dialogue in quotes from the scene, character sounds (meows, yells, screams). Use natural adverbs freely. PRESERVE existing dialogue and sounds — never remove them. Return ONLY the rewritten prompt, nothing else.
PROMPT,
                'prompt_template' => <<<'PROMPT'
This prompt is only {{wordCount}} words. Expand each sentence to reach 120-150 total:

{{videoPrompt}}
PROMPT
            ],

            [
                'slug' => 'concept_fit_skeleton',
                'name' => 'Fit Prompt to Skeleton',
                'description' => 'Rewrites a raw videoPrompt to follow the proven Seedance skeleton structure. Detects energy type and applies matching template with chaos scaling.',
                'model' => 'grok-4',
                'temperature' => 0.7,
                'max_tokens' => 500,
                'variables' => ['example', 'skeleton', 'situation', 'dialogueContext', 'characterContext', 'rawPrompt', 'chaosScalingBlock'],
                'prompt_template' => <<<'PROMPT'
Rewrite the source material below following the MANDATORY STRUCTURE. Match the energy and beat pattern EXACTLY.

REFERENCE (match this flow and energy):
{{example}}

MANDATORY STRUCTURE — follow these beats EXACTLY:
{{skeleton}}

SOURCE MATERIAL (use characters, dialogue, objects — NOT its sentence structure):
Situation: {{situation}}{{dialogueContext}}
{{characterContext}}
Raw: "{{rawPrompt}}"

SEEDANCE TECHNICAL RULES — apply to ALL content:
- NEVER use emotional adjectives: frustrated, angry, feisty, furious, terrified, desperate, pained, mischievous, satisfied, playful, joyful, content, smug.
- NEVER use banned adverbs: tightly, briefly, crazily, precariously, fiercely, loudly, sharply, aggressively.
- ONLY use these degree words: quickly, violently, with large amplitude, at high frequency, powerfully, wildly, crazy, fast, intense, strong, greatly.
- Use "crazy" (adjective) NOT "crazily". Use "strong" NOT "strongly". Use "intense" NOT "intensely".
- Use "at high frequency" NOT "high-frequency". Use "crazy loud" NOT "high-pitched".
- NO clothing/appearance descriptions. Identify characters by type/body only.
- NO facial expression descriptions. Convey emotion through BODY ACTIONS.
- NO camera references. Describe character direction only.
- NO weak verbs: walks, goes, moves, does, gets, starts, begins, tries.
- MUST end with "Cinematic, photorealistic."
- MUST NOT include face/identity prefix text.
- MUST NOT include scene/setting descriptions.
- Start directly with the first physical action beat.
{{chaosScalingBlock}}
Output ONLY the rewritten prompt. Nothing else.
PROMPT
            ],
        ];

        foreach ($prompts as $promptData) {
            VwPrompt::updateOrCreate(
                ['slug' => $promptData['slug']],
                array_merge($promptData, ['is_active' => true])
            );
        }

        $this->command->info('Video Wizard prompts seeded successfully.');
    }
}
