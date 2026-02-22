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
                'description' => 'Main prompt for generating video scripts with scenes, narration, and visual descriptions. Used for standard videos up to 5 minutes.',
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
      "narration": "What narrator says ({{wordsPerScene}} words)",
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
- Each scene narration MUST be approximately {{wordsPerScene}} words
- Total narration should equal approximately {{targetWords}} words
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
                'description' => 'Generates detailed scenes for a specific section of a long-form video. Creates narration and visual descriptions for each scene.',
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
      "narration": "Exactly what the narrator says (25-50 words)",
      "visualDescription": "Detailed visual description for AI image generation (describe setting, mood, colors, composition)",
      "duration": {{avgSceneDuration}}
    }
  ]
}

REQUIREMENTS:
- Each scene narration should be 25-50 words
- Visual descriptions must be detailed and specific for image generation
- Include mood, lighting, colors, and composition details in visuals
- Narration should flow naturally from scene to scene
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
- Fresh narration that fits the same duration
- New visual description for AI image generation
- Maintain connection to the overall topic

RESPOND WITH ONLY THIS JSON (no markdown):
{
  "id": "{{existingId}}",
  "title": "New scene title",
  "narration": "New narrator text (match duration)",
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
                'description' => 'Generates detailed visual prompts for AI image generation based on scene narration. Optimized for Stable Diffusion/DALL-E style prompts.',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 500,
                'variables' => ['narration', 'conceptContext', 'styleContext', 'mood', 'productionType', 'aspectRatio'],
                'prompt_template' => <<<'PROMPT'
You are a cinematographer creating a visual prompt for AI image generation.

NARRATION: {{narration}}
{{conceptContext}}
{{styleContext}}
MOOD: {{mood}}
PRODUCTION TYPE: {{productionType}}
ASPECT RATIO: {{aspectRatio}}

Create a detailed visual prompt that:
1. Captures the essence of the narration visually
2. Includes specific details: lighting, colors, composition, camera angle
3. Sets the appropriate mood and atmosphere
4. Is optimized for AI image generation (Stable Diffusion/DALL-E style)

RESPOND WITH ONLY THE PROMPT TEXT (no JSON, no explanation, just the visual description):
PROMPT
            ],

            [
                'slug' => 'voiceover_dialogue',
                'name' => 'Voiceover Dialogue Conversion',
                'description' => 'Converts narration into natural dialogue between characters. Used when the narration style is set to dialogue mode.',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'max_tokens' => 500,
                'variables' => ['narration', 'tone'],
                'prompt_template' => <<<'PROMPT'
Convert this narration into natural dialogue between characters.

NARRATION: {{narration}}
TONE: {{tone}}

Create dialogue that:
1. Conveys the same information as the narration
2. Sounds natural and conversational
3. Uses character names (SPEAKER: dialogue format)

RESPOND WITH ONLY THE DIALOGUE TEXT (no explanation):
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
      "voiceStyle": "How they speak"
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
  "voiceStyle": "Speaking pattern"
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
