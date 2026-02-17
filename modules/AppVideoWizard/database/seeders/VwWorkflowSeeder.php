<?php

namespace Modules\AppVideoWizard\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppVideoWizard\Models\VwWorkflow;

class VwWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSeedanceAdaptive();
        $this->seedSeedanceAnimalChaos();
        $this->seedCloneVideo();
    }

    /**
     * Seed the Seedance Adaptive workflow.
     * Extracts the current pipeline from ConceptService + VideoWizard.
     */
    protected function seedSeedanceAdaptive(): void
    {
        VwWorkflow::updateOrCreate(
            ['slug' => 'seedance-adaptive'],
            [
                'name' => 'Seedance Adaptive',
                'description' => 'Matches any style faithfully. Generates viral ideas, builds image, sanitizes prompt, animates with Seedance.',
                'category' => 'system',
                'video_engine' => 'seedance',
                'is_active' => true,
                'version' => 1,
                'nodes' => $this->getAdaptiveNodes(),
                'edges' => $this->getAdaptiveEdges(),
                'defaults' => [
                    'mode' => 'generate',
                    'chaos_level' => 50,
                    'camera_move' => 'none',
                    'image_model' => 'nanobanana-pro',
                    'ai_tier' => 'economy',
                ],
            ]
        );
    }

    /**
     * Seed the Seedance Animal Chaos Attack workflow.
     */
    protected function seedSeedanceAnimalChaos(): void
    {
        VwWorkflow::updateOrCreate(
            ['slug' => 'seedance-animal-chaos'],
            [
                'name' => 'Animal Chaos Attack',
                'description' => 'Dialogue trigger → animal explosion → destruction. Maximum chaos energy with animal characters.',
                'category' => 'system',
                'video_engine' => 'seedance',
                'is_active' => true,
                'version' => 1,
                'nodes' => $this->getAnimalChaosNodes(),
                'edges' => $this->getAdaptiveEdges(), // Same pipeline structure
                'defaults' => [
                    'mode' => 'generate',
                    'chaos_level' => 85,
                    'camera_move' => 'handheld',
                    'image_model' => 'nanobanana-pro',
                    'ai_tier' => 'economy',
                ],
            ]
        );
    }

    /**
     * The Adaptive pipeline nodes.
     */
    protected function getAdaptiveNodes(): array
    {
        return [
            // Step 1: Generate viral ideas via AI
            [
                'id' => 'viral_ideas',
                'type' => 'ai_text',
                'name' => 'Generate Viral Ideas',
                'description' => 'AI generates 6 viral concept cards using Seedance-optimized prompts',
                'config' => [
                    'service' => 'ConceptService',
                    'method' => 'generateViralIdeas',
                    'ai_tier' => 'from_project',
                    'max_tokens' => 4000,
                    'temperature' => 0.85,
                    'count' => 6,
                    'prompt_template' => $this->getAdaptivePromptTemplate(),
                    'rules' => $this->getSeedanceTechnicalRules(),
                    'example' => $this->getAdaptiveExample(),
                ],
                'inputs' => [
                    'theme' => 'data_bus.user_input.theme',
                    'chaosLevel' => 'data_bus.user_input.chaos_level',
                    'productionSubtype' => 'data_bus.user_input.production_subtype',
                    'videoEngine' => 'seedance',
                    'template' => 'adaptive',
                ],
                'outputs' => [
                    'ideas' => 'data_bus.viral_ideas',
                ],
            ],

            // Step 2: User selects one idea
            [
                'id' => 'user_select',
                'type' => 'user_input',
                'name' => 'Select Concept',
                'description' => 'User picks one of the 6 generated viral ideas',
                'config' => [
                    'input_type' => 'select_card',
                    'source' => 'data_bus.viral_ideas',
                    'label' => 'Select the idea you want to create',
                ],
                'inputs' => [
                    'source' => 'data_bus.viral_ideas',
                ],
                'outputs' => [
                    'selected_idea' => 'data_bus.selected_idea',
                ],
            ],

            // Step 3: Auto-generate script from selected idea
            [
                'id' => 'build_script',
                'type' => 'transform',
                'name' => 'Build Script',
                'description' => 'Constructs script structure from the selected concept',
                'config' => [
                    'transform' => 'php_method',
                    'class' => 'ConceptService',
                    'method' => 'buildScriptFromIdea',
                ],
                'inputs' => [
                    'idea' => 'data_bus.selected_idea',
                ],
                'outputs' => [
                    'script' => 'data_bus.script',
                ],
            ],

            // Step 4: Build image prompt
            [
                'id' => 'build_image_prompt',
                'type' => 'transform',
                'name' => 'Build Image Prompt',
                'description' => 'Construct Seedance-optimized image generation prompt from concept',
                'config' => [
                    'transform' => 'compose_string',
                    'template' => '{character}. {setting}. {imageStartState}',
                ],
                'inputs' => [
                    'character' => 'data_bus.selected_idea.character',
                    'setting' => 'data_bus.selected_idea.setting',
                    'imageStartState' => 'data_bus.selected_idea.imageStartState',
                ],
                'outputs' => [
                    'result' => 'data_bus.image_prompt',
                ],
            ],

            // Step 5: Generate scene image
            [
                'id' => 'generate_image',
                'type' => 'ai_image',
                'name' => 'Generate Scene Image',
                'description' => 'AI generates the starting frame image for the video',
                'config' => [
                    'service' => 'ImageGenerationService',
                    'method' => 'generateSceneImage',
                    'model' => 'nanobanana-pro',
                    'aspect_ratio' => 'from_project',
                ],
                'inputs' => [
                    'prompt' => 'data_bus.image_prompt',
                ],
                'outputs' => [
                    'image_url' => 'data_bus.scene_image_url',
                    'job_id' => 'data_bus.image_job_id',
                ],
            ],

            // Step 6: Sanitize the video prompt
            [
                'id' => 'sanitize_prompt',
                'type' => 'transform',
                'name' => 'Sanitize Video Prompt',
                'description' => 'Fix banned words, enforce Seedance degree word compliance',
                'config' => [
                    'transform' => 'php_method',
                    'class' => 'ConceptService',
                    'method' => 'sanitizeSeedancePrompt',
                    'rules' => $this->getSanitizationRules(),
                ],
                'inputs' => [
                    'text' => 'data_bus.selected_idea.videoPrompt',
                ],
                'outputs' => [
                    'result' => 'data_bus.sanitized_video_prompt',
                ],
            ],

            // Step 7: Assemble final video prompt with camera + style
            [
                'id' => 'assemble_prompt',
                'type' => 'transform',
                'name' => 'Assemble Final Prompt',
                'description' => 'Add camera movement instruction and style anchor',
                'config' => [
                    'transform' => 'seedance_assemble',
                    'camera_move' => 'from_project',
                    'style_anchor' => 'Cinematic, photorealistic.',
                ],
                'inputs' => [
                    'base_prompt' => 'data_bus.sanitized_video_prompt',
                ],
                'outputs' => [
                    'result' => 'data_bus.final_video_prompt',
                ],
            ],

            // Step 8: Generate Seedance video
            [
                'id' => 'generate_video',
                'type' => 'ai_video',
                'name' => 'Generate Seedance Video',
                'description' => 'Animate the scene image with Seedance using the assembled prompt',
                'config' => [
                    'service' => 'AnimationService',
                    'method' => 'generateAnimation',
                    'model' => 'seedance',
                    'quality' => 'pro',
                    'duration' => 8,
                    'async' => true,
                ],
                'inputs' => [
                    'image_url' => 'data_bus.scene_image_url',
                    'prompt' => 'data_bus.final_video_prompt',
                ],
                'outputs' => [
                    'video_url' => 'data_bus.video_url',
                    'job_id' => 'data_bus.video_job_id',
                ],
            ],

            // Step 9: Poll for video completion
            [
                'id' => 'poll_video',
                'type' => 'poll_wait',
                'name' => 'Wait for Video',
                'description' => 'Poll until Seedance video generation is complete',
                'config' => [
                    'poll_interval' => 5,
                    'max_wait' => 300,
                    'status_method' => 'checkVideoJobStatus',
                ],
                'inputs' => [
                    'job_id' => 'data_bus.video_job_id',
                ],
                'outputs' => [
                    'video_url' => 'data_bus.final_video_url',
                ],
            ],
        ];
    }

    /**
     * Animal Chaos Attack nodes — same structure as Adaptive but with different prompts/rules.
     */
    protected function getAnimalChaosNodes(): array
    {
        $nodes = $this->getAdaptiveNodes();

        // Override the viral_ideas node with Animal Chaos template
        $nodes[0]['config']['prompt_template'] = $this->getAnimalChaosPromptTemplate();
        $nodes[0]['config']['rules'] = $this->getAnimalChaosRules();
        $nodes[0]['config']['example'] = $this->getAnimalChaosExample();
        $nodes[0]['config']['template'] = 'animal-chaos';
        $nodes[0]['inputs']['template'] = 'animal-chaos';

        return $nodes;
    }

    /**
     * Edge connections for the Adaptive/Animal Chaos pipeline.
     */
    protected function getAdaptiveEdges(): array
    {
        return [
            ['from' => 'viral_ideas', 'to' => 'user_select'],
            ['from' => 'user_select', 'to' => 'build_script'],
            ['from' => 'user_select', 'to' => 'build_image_prompt'],
            ['from' => 'user_select', 'to' => 'sanitize_prompt'],
            ['from' => 'build_image_prompt', 'to' => 'generate_image'],
            ['from' => 'sanitize_prompt', 'to' => 'assemble_prompt'],
            ['from' => 'generate_image', 'to' => 'generate_video'],
            ['from' => 'assemble_prompt', 'to' => 'generate_video'],
            ['from' => 'generate_video', 'to' => 'poll_video'],
        ];
    }

    // =========================================================================
    // PROMPT TEMPLATES
    // =========================================================================

    protected function getAdaptivePromptTemplate(): string
    {
        return <<<'PROMPT'
You are a viral content specialist who creates massively shareable short-form video concepts.

{themeContext}

{styleModifier}

{chaosModifier}

IMPORTANT: These ideas will be animated using Seedance — an AI model that generates
video + voice + sound effects ALL FROM A TEXT PROMPT. There is no separate audio recording.
The model auto-generates dialogue, environmental sounds, and sound effects from the prompt.

CRITICAL: ABSOLUTELY NO background music in the videoPrompt. NEVER write "music plays",
"upbeat music", "beat drops", "soundtrack", or any music reference.

Generate exactly {count} unique viral video concepts as a JSON array. Each concept must include:
- title: Catchy title (max 6 words)
- concept: One sentence describing the full visual scene
- speechType: "monologue" or "dialogue"
- characters: Array of character objects with name, description, role, expression, position
- character: Combined description of ALL main visible characters with spatial relationship
- imageStartState: CALM initial state for starting image (NO action, NO chaos)
- situation: One concise sentence of what happens start to finish
- setting: Detailed location with specific props, brand elements, decor, lighting
- props: Key visual props in the scene
- audioType: "voiceover"
- audioDescription: Brief audio description
- dialogueLines: Array of {speaker, text} objects (1-2 lines max)
- videoPrompt: 150-180 word Seedance-optimized prompt following ALL technical rules
- cameraFixed: true
- mood: "funny"|"absurd"|"wholesome"|"chaotic"|"cute"
- viralHook: Why this would go viral (one sentence)

RESPONSE FORMAT: Valid JSON array only. No markdown, no code fences, no commentary.
PROMPT;
    }

    protected function getAnimalChaosPromptTemplate(): string
    {
        return <<<'PROMPT'
You are a viral content specialist creating ANIMAL CHAOS ATTACK videos.

{themeContext}

{styleModifier}

{chaosModifier}

FORMULA: A human character says a trigger line → an animal IMMEDIATELY EXPLODES into
chaotic physical comedy → escalating destruction of the environment.

IMPORTANT: Seedance generates video + voice + sound effects ALL FROM TEXT. No separate audio.
CRITICAL: ABSOLUTELY NO background music references.

The animal must be the STAR and DRIVER of all chaos. Every piece of destruction must be
CAUSED by the animal's physical actions. Never write passive destruction.

Generate exactly {count} unique viral video concepts as a JSON array with the same structure
as Adaptive (title, concept, speechType, characters, character, imageStartState, situation,
setting, props, audioType, audioDescription, dialogueLines, videoPrompt, cameraFixed, mood, viralHook).

KEY RULES:
- The human's dialogue is SHORT (1 line trigger)
- Animal reacts INSTANTLY after the trigger
- videoPrompt focuses 80% on ANIMAL ACTIONS, 20% on human reaction
- 3-4 mega-beats of escalating destruction
- "crazy" appears 3+ times in videoPrompt
- Every action has 2+ combined degree words

RESPONSE FORMAT: Valid JSON array only. No markdown, no code fences, no commentary.
PROMPT;
    }

    protected function getSeedanceTechnicalRules(): string
    {
        return <<<'RULES'
SEEDANCE TECHNICAL RULES (MANDATORY):

OFFICIAL DEGREE WORDS (use ONLY these):
quickly, violently, with large amplitude, at high frequency, powerfully,
wildly, crazy, fast, intense, strong, greatly

WORD COUNT: 150-180 words (optimal: 160-175)

FACE PRESERVATION (include in every videoPrompt):
"Maintain face and clothing consistency, no distortion, high detail."
"Character face stable without deformation, normal human structure, natural and smooth movements."

STYLE ANCHOR: Always end with "Cinematic, photorealistic."

CHARACTER SOUNDS: Continuous throughout, varied (screeching, yowling, hissing, shrieking, growling, wailing)

EVERY MOVEMENT EXPLICITLY DESCRIBED: No implied or assumed motion.

BANNED:
- Semicolons
- Camera descriptions (handled separately)
- Appearance/clothing in videoPrompt (belongs in imageStartState)
- Passive voice, weak verbs
- Literary adverbs (intensely, sharply, fiercely, deafening, etc.)
- Emotional state adjectives (enraged, furious, terrified, etc.)
- ALL background music references

DEGREE WORD SCALING BY ENERGY:
- CALM (0-20): None or 1-2 Tier 1 only (quickly, fast)
- MODERATE (21-45): 1-2 per beat, Tier 1 (quickly, fast)
- HIGH (46-65): 2-3 per beat, Tier 1-2 (powerfully, strong, intense)
- PEAK (66-85): 3+ combined per beat, Tier 1-3 (crazy, wildly, violently, with large amplitude, at high frequency)
- MAX (86-100): Multiple combined per action, all tiers
RULES;
    }

    protected function getAnimalChaosRules(): string
    {
        return $this->getSeedanceTechnicalRules() . <<<'RULES'


ANIMAL CHAOS ATTACK ADDITIONAL RULES:

STRUCTURE: Dialogue trigger → INSTANT MEGA-STRIKE → escalation → peak destruction

PATTERN:
"The man says '...' Instantly the [animal] reacts at high frequency and crazy intensity,
lunging forward fast and violently... [2-3 sentences]. The man crashes fast into...
[2-3 sentences]. The [animal]'s hind legs kick at high frequency... [2-3 sentences].
The man collapses powerfully... [final beat]."

REQUIREMENTS:
- FEWER MEGA-BEATS: 3-4 maximum (not 6-8 micro-actions)
- Each beat: 2-3 full sentences of detailed physical description
- FRONT-LOAD THE IMPACT: Biggest action happens IMMEDIATELY after "Instantly"
- "crazy" appears 3+ times
- Every action has 2+ combined degree words
- Body Part Decomposition: 4-7 body parts with distinct simultaneous actions
- Chain Reactions: Minimum 2 per prompt
- Sound Descriptions: 3-5 per prompt with degree words applied
RULES;
    }

    protected function getAdaptiveExample(): string
    {
        return <<<'EXAMPLE'
"The cat stands on the kitchen counter next to a wooden spoon rack and a row of glass jars, head bobbing quickly to the rhythm, front paws stepping in precise marching formation with quick small steps. Its tail sways powerfully left and right like a metronome, whiskers twitching fast with each beat. The cat's mouth opens wide letting out a sustained crazy meow in time with the tempo, ears perked forward and vibrating. Its hind legs stamp the counter surface at high frequency, creating a steady rhythmic tapping that rattles nearby utensils wildly against each other. A wooden spoon slides off the rack and clatters to the floor with a sharp crack. The cat pauses, looks directly at the camera with wide intense eyes, then resumes marching with large amplitude, front paws lifting high and stomping down powerfully, sending a glass jar tipping over the counter edge and shattering on the floor. Continuous crazy cat vocalizing throughout. Cinematic, photorealistic."
EXAMPLE;
    }

    protected function getAnimalChaosExample(): string
    {
        return <<<'EXAMPLE'
"The man says 'Who knocked over my coffee?' Instantly the cat launches with crazy intensity from the shelf, front paws slamming fast into the man's chest powerfully, claws gripping the shirt fabric and pulling violently downward. The man staggers back at high frequency, arms flailing wildly as the cat's hind legs kick with large amplitude against his stomach. Coffee cups crash to the floor with sharp cracks. The cat springs off the man's shoulder with crazy force, landing on the kitchen counter and sliding fast through a stack of plates, sending them flying violently in all directions. The man's elbow smashes into the refrigerator door powerfully, swinging it open with large amplitude. The cat leaps from the counter with wild abandon, tail whipping at high frequency, crashing into the hanging pots that swing and clang together powerfully. The man collapses fast into the scattered debris. Continuous crazy cat screeching and hissing throughout. Cinematic, photorealistic."
EXAMPLE;
    }

    protected function getSanitizationRules(): string
    {
        return <<<'RULES'
SANITIZATION REPLACEMENTS:
Phase 1 - Compound phrases:
  "crazy intensely" → "with crazy intensity"
  "razor-sharp" → "sharp"
  "loud crash" → "sharp crack"

Phase 2 - Banned -ly adverbs:
  "intensely" → "violently"
  "loudly" → "powerfully"
  "sharply" → "fast"
  "fiercely" → "wildly"
  "explosively" → "violently"
  "deafening" → "crazy loud"

Phase 3 - Emotional adjectives (stripped entirely):
  "enraged", "furious", "terrified", "horrified", "shocked", "stunned"

Phase 4 - Standalone intensity words:
  "loud" (not "crazy loud") → "intense"
  "ferociously" → "wildly"
  "frantically" → "at high frequency"
  "rapidly" → "fast"
  "tremendously" → "with large amplitude"
RULES;
    }

    /**
     * Seed the Clone Video workflow.
     */
    protected function seedCloneVideo(): void
    {
        VwWorkflow::updateOrCreate(
            ['slug' => 'clone-video'],
            [
                'name' => 'Clone Video',
                'description' => 'Analyze an existing video to extract its style, characters, and action — then recreate it with AI.',
                'category' => 'system',
                'video_engine' => 'seedance',
                'is_active' => true,
                'version' => 1,
                'nodes' => $this->getCloneVideoNodes(),
                'edges' => $this->getCloneVideoEdges(),
                'defaults' => [
                    'mode' => 'clone',
                    'camera_move' => 'none',
                    'image_model' => 'nanobanana-pro',
                    'ai_tier' => 'economy',
                ],
            ]
        );
    }

    /**
     * Clone Video pipeline nodes.
     */
    protected function getCloneVideoNodes(): array
    {
        return [
            [
                'id' => 'input_video',
                'type' => 'user_input',
                'name' => 'Provide Source Video',
                'description' => 'Upload a video file or paste a URL from YouTube, Instagram, TikTok, etc.',
                'config' => [
                    'input_type' => 'video_upload_or_url',
                    'max_size' => '100MB',
                    'accepted_formats' => 'MP4, MOV, WebM',
                ],
                'inputs' => [],
                'outputs' => [
                    'video_path' => 'data_bus.source_video_path',
                ],
            ],
            [
                'id' => 'download_video',
                'type' => 'transform',
                'name' => 'Download Video',
                'description' => 'Download the video from URL using yt-dlp or RapidAPI fallback. Skipped for file uploads.',
                'config' => [
                    'transform' => 'php_method',
                    'class' => 'VideoWizard',
                    'method' => 'downloadVideoWithYtDlp',
                ],
                'inputs' => [
                    'url' => 'data_bus.source_video_url',
                ],
                'outputs' => [
                    'local_path' => 'data_bus.source_video_path',
                ],
            ],
            [
                'id' => 'extract_frame',
                'type' => 'transform',
                'name' => 'Extract First Frame',
                'description' => 'Extract the first frame from the video using ffmpeg for use as the base image.',
                'config' => [
                    'transform' => 'php_method',
                    'class' => 'VideoWizard',
                    'method' => 'extractFirstFrame',
                ],
                'inputs' => [
                    'video_path' => 'data_bus.source_video_path',
                ],
                'outputs' => [
                    'frame_url' => 'data_bus.first_frame_url',
                ],
            ],
            [
                'id' => 'analyze_video',
                'type' => 'ai_text',
                'name' => 'Analyze Video',
                'description' => 'AI vision analyzes the video for characters, setting, actions, mood, and style.',
                'config' => [
                    'service' => 'ConceptService',
                    'method' => 'analyzeVideoForConcept',
                    'ai_tier' => 'from_project',
                    'prompt_template' => 'Analyze the video for: characters (species, appearance, position), setting & environment, action timeline, audio & sound, camera style, mood & viral formula.',
                    'rules' => 'Identify EVERY character with 100% accuracy. SIZE/SCALE analysis required. CHARACTER-OBJECT INTERACTIONS must describe HOW they USE objects.',
                ],
                'inputs' => [
                    'video_path' => 'data_bus.source_video_path',
                ],
                'outputs' => [
                    'visual_analysis' => 'data_bus.visual_analysis',
                ],
            ],
            [
                'id' => 'transcribe_audio',
                'type' => 'transform',
                'name' => 'Transcribe Audio',
                'description' => 'Extract audio from video and transcribe with OpenAI Whisper.',
                'config' => [
                    'transform' => 'php_method',
                    'class' => 'ConceptService',
                    'method' => 'extractAndTranscribeAudio',
                ],
                'inputs' => [
                    'video_path' => 'data_bus.source_video_path',
                ],
                'outputs' => [
                    'transcript' => 'data_bus.transcript',
                ],
            ],
            [
                'id' => 'synthesize_concept',
                'type' => 'ai_text',
                'name' => 'Synthesize Concept',
                'description' => 'AI combines visual analysis + transcript into a structured viral concept with Seedance-optimized videoPrompt.',
                'config' => [
                    'service' => 'ConceptService',
                    'method' => 'synthesizeConcept',
                    'ai_tier' => 'from_project',
                    'rules' => 'Use EXACT species/character type from visual analysis. Preserve mood, setting, viral formula. cameraFixed must be true. Apply Seedance degree words.',
                ],
                'inputs' => [
                    'visual_analysis' => 'data_bus.visual_analysis',
                    'transcript' => 'data_bus.transcript',
                ],
                'outputs' => [
                    'concept' => 'data_bus.cloned_concept',
                ],
            ],
            [
                'id' => 'fit_to_skeleton',
                'type' => 'ai_text',
                'name' => 'Fit to Skeleton',
                'description' => 'Rewrites the raw videoPrompt to follow the proven Seedance skeleton structure. Detects energy type (Gentle/Physical Comedy/Chaotic/Rhythmic/Dramatic) from mood and applies the matching sentence-by-sentence template.',
                'config' => [
                    'service' => 'ConceptService',
                    'method' => 'fitPromptToSkeleton',
                    'ai_tier' => 'from_project',
                    'rules' => 'Follow skeleton sentence-by-sentence. Condense into 3-4 mega-beats. 150-180 words. End with "Cinematic, photorealistic." Preserve content but restructure.',
                    'skeleton_types' => ['GENTLE', 'PHYSICAL COMEDY', 'CHAOTIC', 'RHYTHMIC', 'DRAMATIC'],
                ],
                'inputs' => [
                    'raw_prompt' => 'data_bus.cloned_concept.videoPrompt',
                    'concept' => 'data_bus.cloned_concept',
                ],
                'outputs' => [
                    'fitted_prompt' => 'data_bus.fitted_video_prompt',
                    'skeleton_type' => 'data_bus.skeleton_type',
                    'original_prompt' => 'data_bus.original_video_prompt',
                ],
            ],
            [
                'id' => 'seedance_compliance',
                'type' => 'ai_validation',
                'name' => 'Seedance Compliance Check',
                'description' => 'AI-powered validation that scans the videoPrompt against all Seedance 1.5 rules, reports violations, and auto-fixes them.',
                'config' => [
                    'service' => 'ConceptService',
                    'method' => 'validateSeedanceCompliance',
                    'ai_tier' => 'economy',
                ],
                'inputs' => [
                    'prompt' => 'data_bus.fitted_video_prompt',
                ],
                'outputs' => [
                    'score' => 'data_bus.compliance_score',
                    'violations' => 'data_bus.compliance_violations',
                    'fixed_prompt' => 'data_bus.compliant_video_prompt',
                ],
            ],
            [
                'id' => 'user_approve',
                'type' => 'user_input',
                'name' => 'Approve Concept',
                'description' => 'Review the cloned concept (with skeleton-fitted videoPrompt) and confirm to proceed with video creation.',
                'config' => [
                    'input_type' => 'approve_card',
                    'source' => 'data_bus.cloned_concept',
                ],
                'outputs' => [
                    'approved_concept' => 'data_bus.selected_idea',
                ],
            ],
            [
                'id' => 'build_script',
                'type' => 'transform',
                'name' => 'Build Script',
                'description' => 'Constructs script structure from the approved concept.',
                'config' => [
                    'transform' => 'compose_string',
                    'template' => '{concept}',
                ],
                'inputs' => [
                    'concept' => 'data_bus.selected_idea',
                ],
                'outputs' => [
                    'script' => 'data_bus.script',
                ],
            ],
            [
                'id' => 'build_image_prompt',
                'type' => 'transform',
                'name' => 'Build Image Prompt',
                'description' => 'Construct image generation prompt from the cloned concept (or use first frame if available).',
                'config' => [
                    'transform' => 'compose_string',
                    'template' => '{character}. {setting}. {imageStartState}',
                ],
                'inputs' => [
                    'character' => 'data_bus.selected_idea.character',
                    'setting' => 'data_bus.selected_idea.setting',
                    'imageStartState' => 'data_bus.selected_idea.imageStartState',
                ],
                'outputs' => [
                    'image_prompt' => 'data_bus.image_prompt',
                ],
            ],
            [
                'id' => 'generate_image',
                'type' => 'ai_image',
                'name' => 'Generate Scene Image',
                'description' => 'AI generates the starting frame image (or uses extracted first frame from source video).',
                'config' => [
                    'service' => 'ImageGenerationService',
                    'method' => 'generateSceneImage',
                    'model' => 'nanobanana-pro',
                    'aspect_ratio' => 'from_project',
                ],
                'inputs' => [
                    'prompt' => 'data_bus.image_prompt',
                ],
                'outputs' => [
                    'image_url' => 'data_bus.scene_image_url',
                ],
            ],
            [
                'id' => 'sanitize_prompt',
                'type' => 'transform',
                'name' => 'Sanitize Video Prompt',
                'description' => 'Fix banned words, enforce Seedance degree word compliance.',
                'config' => [
                    'transform' => 'php_method',
                    'class' => 'ConceptService',
                    'method' => 'sanitizeSeedancePrompt',
                ],
                'inputs' => [
                    'text' => 'data_bus.selected_idea.videoPrompt',
                ],
                'outputs' => [
                    'video_prompt' => 'data_bus.sanitized_video_prompt',
                ],
            ],
            [
                'id' => 'assemble_prompt',
                'type' => 'transform',
                'name' => 'Assemble Final Prompt',
                'description' => 'Add camera movement instruction and style anchor to the sanitized prompt.',
                'config' => [
                    'transform' => 'seedance_assemble',
                    'camera_move' => 'from_project',
                    'style_anchor' => 'Cinematic, photorealistic.',
                ],
                'inputs' => [
                    'base_prompt' => 'data_bus.sanitized_video_prompt',
                ],
                'outputs' => [
                    'final_prompt' => 'data_bus.final_video_prompt',
                ],
            ],
            [
                'id' => 'generate_video',
                'type' => 'ai_video',
                'name' => 'Generate Seedance Video',
                'description' => 'Animate the scene image with Seedance using the final assembled prompt.',
                'config' => [
                    'service' => 'AnimationService',
                    'method' => 'generateAnimation',
                    'model' => 'seedance',
                    'quality' => 'pro',
                    'duration' => 8,
                    'async' => true,
                ],
                'inputs' => [
                    'image_url' => 'data_bus.scene_image_url',
                    'prompt' => 'data_bus.final_video_prompt',
                ],
                'outputs' => [
                    'video_url' => 'data_bus.video_url',
                    'job_id' => 'data_bus.video_job_id',
                ],
            ],
            [
                'id' => 'poll_video',
                'type' => 'poll_wait',
                'name' => 'Wait for Video',
                'description' => 'Poll until Seedance video generation is complete.',
                'config' => [
                    'poll_interval' => 5,
                    'max_wait' => 300,
                    'status_method' => 'checkVideoJobStatus',
                ],
                'inputs' => [
                    'job_id' => 'data_bus.video_job_id',
                ],
                'outputs' => [
                    'video_url' => 'data_bus.final_video_url',
                ],
            ],
        ];
    }

    /**
     * Clone Video pipeline edges.
     */
    protected function getCloneVideoEdges(): array
    {
        return [
            ['from' => 'input_video', 'to' => 'download_video'],
            ['from' => 'download_video', 'to' => 'extract_frame'],
            ['from' => 'download_video', 'to' => 'analyze_video'],
            ['from' => 'download_video', 'to' => 'transcribe_audio'],
            ['from' => 'extract_frame', 'to' => 'synthesize_concept'],
            ['from' => 'analyze_video', 'to' => 'synthesize_concept'],
            ['from' => 'transcribe_audio', 'to' => 'synthesize_concept'],
            ['from' => 'synthesize_concept', 'to' => 'fit_to_skeleton'],
            ['from' => 'fit_to_skeleton', 'to' => 'seedance_compliance'],
            ['from' => 'seedance_compliance', 'to' => 'user_approve'],
            ['from' => 'user_approve', 'to' => 'build_script'],
            ['from' => 'user_approve', 'to' => 'build_image_prompt'],
            ['from' => 'user_approve', 'to' => 'sanitize_prompt'],
            ['from' => 'build_image_prompt', 'to' => 'generate_image'],
            ['from' => 'sanitize_prompt', 'to' => 'assemble_prompt'],
            ['from' => 'generate_image', 'to' => 'generate_video'],
            ['from' => 'assemble_prompt', 'to' => 'generate_video'],
            ['from' => 'generate_video', 'to' => 'poll_video'],
        ];
    }
}
