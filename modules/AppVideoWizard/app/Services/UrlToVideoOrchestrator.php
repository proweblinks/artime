<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\UrlToVideoProject;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\SpeechSegment;
use Modules\AppVideoWizard\Services\SpeechSegmentParser;
use Modules\AppVideoWizard\Services\VoiceRegistryService;

/**
 * UrlToVideoOrchestrator
 *
 * Pipeline for URL-to-Video mode. Reuses the same sub-services as StoryModeOrchestrator:
 * Visual Script → Voiceover → Images → Video Clips → Assembly
 */
class UrlToVideoOrchestrator
{
    protected StoryModeScriptService $scriptService;
    protected VoiceoverService $voiceoverService;
    protected ImageGenerationService $imageService;
    protected AnimationService $animationService;
    protected VideoRenderService $renderService;

    public function __construct()
    {
        $this->scriptService = new StoryModeScriptService();
        $this->voiceoverService = app(VoiceoverService::class);
        $this->imageService = app(ImageGenerationService::class);
        $this->animationService = app(AnimationService::class);
        $this->renderService = app(VideoRenderService::class);
    }

    /**
     * Run the full generation pipeline for a URL-to-Video project.
     */
    public function generate(UrlToVideoProject $project): void
    {
        Log::info('UrlToVideoOrchestrator: Starting pipeline', ['project_id' => $project->id]);

        try {
            // Step 1: Build Visual Script (15-35%)
            $this->stepBuildVisualScript($project);
            if ($this->isCancelled($project)) return;

            // Step 2: Generate Voiceover (35-50%)
            $this->stepGenerateVoiceover($project);
            if ($this->isCancelled($project)) return;

            // Step 3: Generate Images (50-70%)
            $this->stepGenerateImages($project);
            if ($this->isCancelled($project)) return;

            // Step 3.5: AI Video Prompt Refinement — Film mode only (image-aware)
            $this->stepRefineVideoPrompts($project);
            if ($this->isCancelled($project)) return;

            // Step 4: Generate Video Clips (70-85%)
            $this->stepGenerateVideoClips($project);
            if ($this->isCancelled($project)) return;

            // Step 5: Assemble Final Video (85-100%)
            $this->stepAssembleFinalVideo($project);

            Log::info('UrlToVideoOrchestrator: Pipeline completed', ['project_id' => $project->id]);
        } catch (\Exception $e) {
            Log::error('UrlToVideoOrchestrator: Pipeline failed', [
                'project_id' => $project->id,
                'stage' => $project->current_stage,
                'error' => $e->getMessage(),
            ]);
            $project->markFailed($e->getMessage());
        }
    }

    /**
     * Check if the project has been cancelled or deleted (refresh from DB).
     */
    protected function isCancelled(UrlToVideoProject $project): bool
    {
        // Re-fetch from DB — project may have been force-deleted by cancel
        $fresh = UrlToVideoProject::find($project->id);

        if (!$fresh || $fresh->status === 'cancelled') {
            Log::info('UrlToVideoOrchestrator: Project cancelled/deleted, stopping pipeline', [
                'project_id' => $project->id,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Step 1: Build visual script from transcript segments.
     */
    protected function stepBuildVisualScript(UrlToVideoProject $project): void
    {
        $project->updateProgress('generating_visual_script', 15, 'Creating visual script');

        $scenes = $project->scenes ?? [];

        // Smart-skip: If all scenes already have image_prompt from interactive AI Studio, skip generation
        $allHavePrompts = !empty($scenes) && collect($scenes)->every(fn($s) => !empty($s['image_prompt']));
        if ($allHavePrompts) {
            Log::info('UrlToVideoOrchestrator: Visual script already populated (interactive mode), skipping', [
                'project_id' => $project->id,
                'scenes' => count($scenes),
            ]);

            // Still store visual_script if not already set
            if (empty($project->visual_script)) {
                $visualScript = array_map(fn($s) => [
                    'image_prompt' => $s['image_prompt'] ?? '',
                    'video_action' => $s['video_action'] ?? '',
                    'camera_motion' => $s['camera_motion'] ?? 'slow zoom in',
                    'mood' => $s['mood'] ?? 'professional',
                    'voice_emotion' => $s['voice_emotion'] ?? 'neutral',
                    'characters_in_scene' => $s['characters_in_scene'] ?? [],
                    'transition_type' => $s['transition_type'] ?? 'fade',
                    'transition_duration' => (float) ($s['transition_duration'] ?? 0.5),
                ], $scenes);

                $project->update([
                    'visual_script' => $visualScript,
                ]);
            }
            return;
        }

        $aspectRatio = $project->aspect_ratio ?? '9:16';
        $metadata = $project->metadata ?? [];
        $isFilmMode = !empty($metadata['film_mode']);

        // Derive style instruction from visual style config (if set) or content brief
        $brief = $project->content_brief ?? [];
        $tone = $brief['tone'] ?? 'professional';
        $styleConfig = $metadata['visual_style_config'] ?? null;
        if ($styleConfig && !empty($styleConfig['imagePrefix'])) {
            $styleInstruction = "{$styleConfig['imagePrefix']}. {$tone} tone. {$styleConfig['imageSuffix']}";
        } else {
            $category = $brief['content_category'] ?? 'general';
            $styleInstruction = "Cinematic, photorealistic, {$tone} tone, {$category} content";
        }

        // Film mode: use template visual overrides for richer style instruction
        if ($isFilmMode) {
            $templateConfig = $metadata['film_template_config'] ?? [];
            $overrides = $templateConfig['visual_overrides'] ?? $styleConfig ?? [];
            if (!empty($overrides['imagePrefix'])) {
                $atmosphere = $templateConfig['atmosphere'] ?? '';
                $styleInstruction = "{$overrides['imagePrefix']}. {$overrides['imageSuffix']}. {$atmosphere}";
            }
        }

        // Film mode: build visual script deterministically from screenplay directions
        // Skips AI call — directions are already rich visual descriptions
        if ($isFilmMode) {
            $templateConfig = $metadata['film_template_config'] ?? [];
            $filmService = new FilmTemplateService();
            $visualScript = $filmService->buildFilmVisualScript($scenes, $templateConfig);
            $characterBible = $filmService->getCharacterBibleForTemplate($templateConfig);

            $updatedScenes = [];
            foreach ($scenes as $i => $scene) {
                $visual = $visualScript[$i] ?? [];
                $scene['image_prompt'] = $scene['image_prompt'] ?? $visual['image_prompt'] ?? '';
                $scene['video_action'] = $scene['video_action'] ?? $visual['video_action'] ?? '';
                $scene['characters_in_scene'] = $scene['characters_in_scene'] ?? $visual['characters_in_scene'] ?? [];
                $scene['camera_motion'] = $scene['camera_motion'] ?? $visual['camera_motion'] ?? 'slow zoom in';
                $scene['mood'] = $scene['mood'] ?? $visual['mood'] ?? 'dramatic';
                $scene['voice_emotion'] = $scene['voice_emotion'] ?? $visual['voice_emotion'] ?? 'neutral';
                $scene['transition_type'] = $scene['transition_type'] ?? $visual['transition_type'] ?? 'fadeblack';
                $scene['transition_duration'] = (float) ($scene['transition_duration'] ?? $visual['transition_duration'] ?? 0.5);

                // Film mode: keep raw video_action from buildConciseVideoAction() for readable
                // AI Studio display. Full Seedance prompt is built at video generation time
                // (or by stepRefineVideoPrompts AI refinement step).

                $updatedScenes[] = $scene;
            }

            $project->update([
                'scenes' => $updatedScenes,
                'visual_script' => $visualScript,
                'metadata' => array_merge($metadata, [
                    'character_bible' => $characterBible,
                    'style_instruction' => $styleInstruction,
                ]),
            ]);

            Log::info('UrlToVideoOrchestrator: Film visual script built (raw video actions preserved)', [
                'project_id' => $project->id,
                'scenes' => count($updatedScenes),
            ]);
            return;
        }

        $segments = array_map(fn ($s) => [
            'text' => $s['text'] ?? '',
            'estimated_duration' => $s['audio_duration'] ?? $s['estimated_duration'] ?? 6,
        ], $scenes);

        $visualScript = $this->scriptService->buildVisualScript($segments, $styleInstruction, $aspectRatio, $project->team_id);
        $characterBible = $this->scriptService->lastCharacterBible;

        $updatedScenes = [];
        foreach ($scenes as $i => $scene) {
            $visual = $visualScript[$i] ?? [];
            // Only overwrite if not already set (preserve interactive mode edits)
            $scene['image_prompt'] = $scene['image_prompt'] ?? $visual['image_prompt'] ?? "A cinematic scene: {$scene['text']}";
            $scene['video_action'] = $scene['video_action'] ?? $visual['video_action'] ?? '';
            $scene['characters_in_scene'] = $scene['characters_in_scene'] ?? $visual['characters_in_scene'] ?? [];
            $scene['camera_motion'] = $scene['camera_motion'] ?? $visual['camera_motion'] ?? 'slow zoom in';
            $scene['mood'] = $scene['mood'] ?? $visual['mood'] ?? 'professional';
            $scene['voice_emotion'] = $scene['voice_emotion'] ?? $visual['voice_emotion'] ?? 'neutral';
            $scene['transition_type'] = $scene['transition_type'] ?? $visual['transition_type'] ?? 'fade';
            $scene['transition_duration'] = (float) ($scene['transition_duration'] ?? $visual['transition_duration'] ?? 0.5);

            // Pre-build rich 100+ word Seedance video prompt and store in video_action
            $richVideoPrompt = $this->buildVideoPrompt($scene, $styleInstruction, $aspectRatio, $styleConfig, null);
            if (!empty($richVideoPrompt)) {
                $scene['video_action'] = $richVideoPrompt;
            }

            $updatedScenes[] = $scene;
        }

        $project->update([
            'scenes' => $updatedScenes,
            'visual_script' => $visualScript,
            'metadata' => array_merge($project->metadata ?? [], [
                'character_bible' => $characterBible,
                'style_instruction' => $styleInstruction,
            ]),
        ]);

        Log::info('UrlToVideoOrchestrator: Visual script built with rich video prompts', [
            'project_id' => $project->id,
            'scenes' => count($updatedScenes),
        ]);
    }

    /**
     * Step 2: Generate voiceover audio for each scene.
     */
    protected function stepGenerateVoiceover(UrlToVideoProject $project): void
    {
        $metadata = $project->metadata ?? [];
        $isFilmMode = !empty($metadata['film_mode']);

        if ($isFilmMode) {
            $this->stepGenerateFilmVoiceover($project);
            return;
        }

        $project->updateProgress('generating_voiceover', 35, 'Generating voiceover');

        $scenes = $project->scenes ?? [];
        if (empty($scenes)) {
            throw new \Exception('No scenes data available for voiceover generation');
        }

        $voiceId = $project->voice_id;
        $voiceProvider = $project->voice_provider ?: null;

        if (!$voiceId) {
            // Auto-select based on mood
            $voiceId = 'bm_lewis';
            $voiceProvider = 'kokoro';
        }

        $wizardProject = $this->createTempWizardProject($project);
        $updatedScenes = [];

        foreach ($scenes as $i => $scene) {
            if ($this->isCancelled($project)) return;

            $progress = 35 + (int) (($i / count($scenes)) * 15);
            $project->updateProgress('generating_voiceover', $progress, "Generating voiceover (" . ($i + 1) . "/" . count($scenes) . ")");

            try {
                $sceneData = [
                    'id' => $scene['id'] ?? "scene_{$i}",
                    'narration' => $scene['text'] ?? '',
                ];

                $result = $this->voiceoverService->generateSceneVoiceover($wizardProject, $sceneData, [
                    'voice' => $voiceId,
                    'provider' => $voiceProvider,
                    'sceneIndex' => $i,
                    'emotion' => $scene['voice_emotion'] ?? null,
                ]);

                $scene['audio_url'] = $result['audioUrl'] ?? $result['audio_url'] ?? null;
                $scene['audio_duration'] = $result['duration'] ?? $scene['estimated_duration'] ?? 6;

                // Fix stale video_edit.trimEnd that was set from pre-voiceover estimated_duration.
                // If trimEnd is shorter than the real audio_duration, extend it so the clip covers the full scene.
                if (!empty($scene['video_edit']) && isset($scene['video_edit']['trimEnd'])) {
                    $actualDuration = (float) $scene['audio_duration'];
                    $oldTrimEnd = (float) $scene['video_edit']['trimEnd'];
                    if ($oldTrimEnd < $actualDuration) {
                        Log::debug('UrlToVideoOrchestrator: Updated stale trimEnd', [
                            'scene_id' => $scene['id'] ?? "scene_{$i}",
                            'old_trimEnd' => $oldTrimEnd,
                            'new_trimEnd' => round($actualDuration, 2),
                        ]);
                        $scene['video_edit']['trimEnd'] = round($actualDuration, 2);
                    }
                }
            } catch (\Exception $e) {
                Log::warning("UrlToVideoOrchestrator: Voiceover failed for segment {$i}", [
                    'error' => $e->getMessage(),
                ]);
                $scene['audio_url'] = null;
                $scene['audio_duration'] = $scene['estimated_duration'] ?? 6;
            }

            $updatedScenes[] = $scene;
        }

        $project->update(['scenes' => $updatedScenes]);
        $wizardProject->delete();
    }

    /**
     * Film mode voiceover: analyse dialogue per scene for Seedance native audio.
     * No TTS calls — Seedance generates voices natively when dialogue is in the prompt.
     * Sets has_dialogue flag so buildVideoPrompt() includes dialogue in single quotes.
     */
    protected function stepGenerateFilmVoiceover(UrlToVideoProject $project): void
    {
        $project->updateProgress('generating_voiceover', 35, 'Analysing film dialogue');

        $scenes = $project->scenes ?? [];
        if (empty($scenes)) {
            throw new \Exception('No scenes data available for dialogue analysis');
        }

        $metadata = $project->metadata ?? [];
        $characterBible = $metadata['character_bible'] ?? [];

        $updatedScenes = [];
        $dialogueScenes = 0;
        $visualOnlyScenes = 0;

        foreach ($scenes as $i => $scene) {
            if ($this->isCancelled($project)) return;

            $sceneText = $scene['text'] ?? '';
            $isVisualOnly = !empty($scene['is_visual_only']);

            // Visual-only scenes: no dialogue to analyse
            if ($isVisualOnly || empty(trim($sceneText))) {
                $scene['audio_url'] = null;
                $scene['audio_duration'] = $scene['estimated_duration'] ?? 4;
                $scene['is_visual_only'] = true;
                $scene['has_dialogue'] = false;
                $visualOnlyScenes++;
                $updatedScenes[] = $scene;
                continue;
            }

            // Check for dialogue pattern (SPEAKER: text)
            $hasDialoguePattern = (bool) preg_match('/^[A-Z][A-Z0-9_\s]+:\s*.+/m', $sceneText);

            if ($hasDialoguePattern) {
                // Estimate duration from word count (~2.5 words/sec for spoken dialogue)
                $lines = preg_split('/\n+/', trim($sceneText));
                $wordCount = 0;
                foreach ($lines as $line) {
                    if (preg_match('/^[A-Z][A-Z0-9_\s]+:\s*(.+)$/s', trim($line), $m)) {
                        $wordCount += str_word_count($m[1]);
                    }
                }
                $estimatedDuration = max(4, min(10, (int) ceil($wordCount / 2.5) + 1));

                $scene['audio_url'] = null; // No TTS file — Seedance handles voice
                $scene['audio_duration'] = $estimatedDuration;
                $scene['has_dialogue'] = true;
                $dialogueScenes++;
            } else {
                // Non-dialogue text (action descriptions, etc.)
                $scene['audio_url'] = null;
                $scene['audio_duration'] = $scene['estimated_duration'] ?? 5;
                $scene['has_dialogue'] = false;
            }

            $updatedScenes[] = $scene;
        }

        $project->update(['scenes' => $updatedScenes]);

        Log::info('UrlToVideoOrchestrator: Film dialogue analysis completed', [
            'project_id' => $project->id,
            'dialogue_scenes' => $dialogueScenes,
            'visual_only_scenes' => $visualOnlyScenes,
            'total_scenes' => count($updatedScenes),
        ]);
    }

    /**
     * Concatenate multiple per-speaker audio files into a single scene audio.
     */
    protected function concatenateSceneAudio(array $segmentAudios, int $projectId, int $sceneIndex): array
    {
        $dir = $this->ensureProjectDir($projectId, 'audio');
        $outputFile = "{$dir}/scene_{$sceneIndex}_concat.mp3";

        // Build ffmpeg concat input
        $inputFiles = [];
        $totalDuration = 0;
        foreach ($segmentAudios as $seg) {
            $audioPath = $seg['audio_url'];
            // Convert URL to local path if needed
            if (str_starts_with($audioPath, 'http')) {
                $tmpPath = "{$dir}/seg_{$sceneIndex}_" . count($inputFiles) . '.mp3';
                $contents = @file_get_contents($audioPath);
                if ($contents) {
                    file_put_contents($tmpPath, $contents);
                    $audioPath = $tmpPath;
                }
            } elseif (str_starts_with($audioPath, '/')) {
                $audioPath = public_path(ltrim($audioPath, '/'));
            }
            $inputFiles[] = $audioPath;
            $totalDuration += $seg['duration'];
        }

        if (count($inputFiles) < 2) {
            return [
                'audio_url' => $segmentAudios[0]['audio_url'] ?? null,
                'duration' => $totalDuration,
            ];
        }

        // Create concat list file
        $listFile = "{$dir}/concat_list_{$sceneIndex}.txt";
        $listContent = '';
        foreach ($inputFiles as $file) {
            $escaped = str_replace("'", "'\\''", $file);
            $listContent .= "file '{$escaped}'\n";
        }
        file_put_contents($listFile, $listContent);

        // Run ffmpeg concat
        $ffmpeg = $this->getFfmpegPath();
        $cmd = "{$ffmpeg} -y -f concat -safe 0 -i " . escapeshellarg($listFile) . " -c:a libmp3lame -q:a 2 " . escapeshellarg($outputFile) . " 2>&1";

        $output = shell_exec($cmd);
        @unlink($listFile);

        if (file_exists($outputFile) && filesize($outputFile) > 0) {
            $relativePath = str_replace(public_path(), '', $outputFile);
            $relativePath = '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
            return [
                'audio_url' => $relativePath,
                'duration' => $totalDuration,
            ];
        }

        Log::warning('UrlToVideoOrchestrator: Audio concat failed, using first segment', [
            'scene_index' => $sceneIndex,
            'output' => substr($output ?? '', 0, 500),
        ]);

        return [
            'audio_url' => $segmentAudios[0]['audio_url'] ?? null,
            'duration' => $totalDuration,
        ];
    }

    /**
     * Guess a voice ID for a character name based on character bible gender.
     */
    protected function guessVoiceForCharacter(string $name, array $characterBible): string
    {
        $nameLower = strtolower($name);
        foreach ($characterBible as $char) {
            if (strtolower($char['name'] ?? '') === $nameLower) {
                $gender = $char['gender'] ?? 'male';
                return $gender === 'female' ? 'nova' : 'echo';
            }
        }
        return 'echo';
    }

    /**
     * Get ffmpeg binary path.
     */
    protected function getFfmpegPath(): string
    {
        $serverPath = '/home/artime/bin/ffmpeg';
        if (file_exists($serverPath)) {
            return $serverPath;
        }
        return 'ffmpeg';
    }

    /**
     * Step 3: Generate images for each scene.
     */
    protected function stepGenerateImages(UrlToVideoProject $project): void
    {
        $project->updateProgress('generating_images', 50, 'Generating images');

        $scenes = $project->scenes ?? [];
        $imageModel = $project->metadata['image_model'] ?? get_option('story_mode_image_model', 'nanobanana2');
        $wizardProject = $this->createTempWizardProject($project);

        $updatedScenes = [];

        foreach ($scenes as $i => $scene) {
            if ($this->isCancelled($project)) return;

            // Skip AI generation if scene already has a real image assigned
            if (!empty($scene['image_url'])) {
                Log::info('UrlToVideoOrchestrator: Using pre-assigned image for scene', [
                    'scene_id' => $scene['id'] ?? "scene_{$i}",
                    'image_url' => $scene['image_url'],
                ]);
                $updatedScenes[] = $scene;
                continue;
            }

            $progress = 50 + (int) (($i / count($scenes)) * 20);
            $project->updateProgress('generating_images', $progress, "Generating image (" . ($i + 1) . "/" . count($scenes) . ")");

            try {
                $sceneData = [
                    'id' => $scene['id'] ?? "scene_{$i}",
                    'visualDescription' => $scene['image_prompt'] ?? '',
                    'narration' => $scene['text'] ?? '',
                ];

                $result = $this->imageService->generateSceneImage($wizardProject, $sceneData, [
                    'model' => $imageModel,
                    'sceneIndex' => $i,
                ]);

                $scene['image_url'] = $result['imageUrl'] ?? $result['image_url'] ?? null;
            } catch (\Exception $e) {
                Log::warning("UrlToVideoOrchestrator: Image generation failed for segment {$i}", [
                    'error' => $e->getMessage(),
                ]);
                $scene['image_url'] = null;
            }

            $updatedScenes[] = $scene;
        }

        $project->update(['scenes' => $updatedScenes]);
        $wizardProject->delete();
    }

    /**
     * Step 3.5: AI-powered video prompt refinement (Film mode only).
     * Uses Gemini 2.5 Flash vision to analyze each generated image and write
     * a focused action-only Seedance prompt (70-100 words).
     * Called AFTER stepGenerateImages(), BEFORE stepGenerateVideoClips().
     */
    protected function stepRefineVideoPrompts(UrlToVideoProject $project): void
    {
        $scenes = $project->scenes ?? [];
        $metadata = $project->metadata ?? [];

        if (empty($metadata['film_mode'])) return; // Only for Film mode

        $templateConfig = $metadata['film_template_config'] ?? [];
        $teamId = $project->team_id ?? 0;

        Log::info("[UrlToVideo:{$project->id}] Starting video prompt refinement for " . count($scenes) . " scenes");

        $systemPrompt = $this->getVideoPromptSystemPrompt($templateConfig);
        $previousPrompt = '';

        foreach ($scenes as $i => &$scene) {
            $imageUrl = $scene['image_url'] ?? null;
            if (empty($imageUrl)) continue;

            $direction = $scene['direction'] ?? '';
            $cameraMotion = $scene['camera_motion'] ?? 'slow zoom in';
            $mood = $scene['mood'] ?? '';
            $hasDialogue = !empty($scene['has_dialogue']);
            $dialogueText = $hasDialogue ? ($scene['text'] ?? '') : '';

            $cameraPhrase = $this->mapCameraToSeedance($cameraMotion);

            $userPrompt = "Scene direction: {$direction}\n"
                . "Camera: {$cameraPhrase}\n"
                . "Mood: {$mood}\n";

            if ($hasDialogue) {
                $userPrompt .= "Dialogue in this scene:\n{$dialogueText}\n";
            }

            if (!empty($previousPrompt)) {
                $userPrompt .= "\nPrevious scene's video prompt (for continuity):\n{$previousPrompt}\n";
            }

            $userPrompt .= "\nWrite the video animation prompt for this image.";

            try {
                $imageContent = @file_get_contents($imageUrl);
                if (empty($imageContent)) {
                    Log::warning("[UrlToVideo:{$project->id}] Could not download image for scene {$i}");
                    continue;
                }
                $imageBase64 = base64_encode($imageContent);

                $response = \AI::processWithOverride(
                    $systemPrompt . "\n\n" . $userPrompt,
                    'gemini',
                    'gemini-2.5-flash-preview-05-20',
                    'vision',
                    [
                        'image_base64' => $imageBase64,
                        'mimeType' => 'image/png',
                        'max_tokens' => 200,
                    ],
                    $teamId
                );

                // Gemini vision returns raw API body in data — extract text from candidates
                $refined = '';
                if (!empty($response['data']['candidates'][0]['content']['parts'][0]['text'])) {
                    $refined = trim($response['data']['candidates'][0]['content']['parts'][0]['text']);
                } elseif (!empty($response['data'][0]) && is_string($response['data'][0])) {
                    $refined = trim($response['data'][0]);
                }

                if (!empty($refined) && str_word_count($refined) >= 30) {
                    // Sanitize for Seedance
                    if (class_exists(SeedancePromptService::class)) {
                        $refined = SeedancePromptService::sanitize($refined);
                    }
                    $scene['video_action'] = $refined;
                    $scene['refined_prompt'] = true;
                    $previousPrompt = $refined;
                    Log::info("[UrlToVideo:{$project->id}] Refined video prompt for scene {$i}: " . str_word_count($refined) . " words");
                }
            } catch (\Throwable $e) {
                Log::warning("[UrlToVideo:{$project->id}] Video prompt refinement failed for scene {$i}: " . $e->getMessage());
                // Fall through — stepGenerateVideoClips will use deterministic buildFilmVideoPrompt
            }
        }
        unset($scene); // Break reference

        $project->update(['scenes' => $scenes]);

        Log::info("[UrlToVideo:{$project->id}] Video prompt refinement complete");
    }

    /**
     * System prompt for AI video prompt refinement.
     * Includes character bible and atmosphere context from the film template.
     */
    protected function getVideoPromptSystemPrompt(array $filmTemplateConfig): string
    {
        $characters = $filmTemplateConfig['characters'] ?? [];
        $atmosphere = $filmTemplateConfig['atmosphere'] ?? '';

        $charBlock = "CHARACTER BIBLE:\n";
        foreach ($characters as $char) {
            $name = strtoupper($char['name'] ?? '');
            $gender = $char['gender'] ?? 'unknown';
            $appearance = $char['description'] ?? '';
            $charBlock .= "- {$name} ({$gender}): {$appearance}\n";
        }

        return <<<PROMPT
You are a Seedance video prompt writer. You see a still image that will be animated into an 8-second video clip.

{$charBlock}
STORY ATMOSPHERE: {$atmosphere}

Write a 70-100 word prompt. Structure: subject (by name) + actions + camera movements + dialogue/sound cues.

RULES:
- Use character NAMES (REN, KIRA) — not "the man" or "the woman". On first mention add a brief visual tag: "REN, the dark-haired man with a cybernetic implant" — then just "REN" after.
- Do NOT describe static environment (buildings, walls, furniture) — the model already sees these.
- Focus on: character actions, gestures, facial expressions, body movement, physical interactions.
- Describe dynamic elements: rain falling, smoke drifting, lights flickering.
- Weave camera direction naturally into the action.
- For dialogue scenes: INCLUDE the actual spoken words as quoted speech. Describe HOW they speak (leaning forward urgently, responding coolly) alongside WHAT they say.
- Single flowing paragraph, present tense.
- NO style tags, NO shot type labels (no "CLOSE UP -", "WIDE SHOT -").
- Output ONLY the prompt text. No preamble, no explanation.

GOOD (visual): "REN leans close to the holographic display, fingers dancing across the keyboard. The camera slowly pushes toward his face. His cybernetic implant pulses blue. He pauses mid-keystroke, brow furrowing as something unexpected flashes on screen."

GOOD (dialogue): "REN slides into the booth across from KIRA. The camera holds in a medium two-shot. He leans forward urgently: 'I know what NEXUS really is.' KIRA sets her glass down, locking eyes: 'So do I. That's why I found you.' He sits back processing. She tilts her head: 'I'm asking you to wake up.' Smoke drifts between them."

BAD: "CLOSE UP of a cyberpunk interior with neon lights and servers. Rain streaks through volumetric light beams. Neon signs flicker and pulse. The camera slowly pushes in closer, the scene conveying a feeling of mystery."
PROMPT;
    }

    /**
     * Step 4: Generate video clips from images.
     */
    protected function stepGenerateVideoClips(UrlToVideoProject $project): void
    {
        $project->updateProgress('generating_video', 70, 'Submitting video generation jobs');

        $scenes = $project->scenes ?? [];
        $wizardProject = $this->createTempWizardProject($project);
        $metadata = $project->metadata ?? [];
        $styleInstruction = $metadata['style_instruction'] ?? '';
        $styleConfig = $metadata['visual_style_config'] ?? null;
        $aspectRatio = $project->aspect_ratio ?? '9:16';
        $imageUrls = array_map(fn($s) => $s['image_url'] ?? null, $scenes);
        $filmTemplateConfig = !empty($metadata['film_mode']) ? ($metadata['film_template_config'] ?? null) : null;
        $isFilmMode = !empty($metadata['film_mode']);

        // Phase 1: Submit all jobs
        $pendingTasks = [];
        foreach ($scenes as $i => $scene) {
            // Skip AI video generation if scene already has a pre-assigned video clip
            if (!empty($scene['video_url'])) {
                Log::info('UrlToVideoOrchestrator: Using pre-assigned video clip', [
                    'scene_id' => $scene['id'] ?? "scene_{$i}",
                    'video_url' => $scene['video_url'],
                ]);
                continue;
            }

            // Skip if user chose NOT to animate this scene (will use Ken Burns instead)
            if (empty($scene['animate_with_ai'])) {
                Log::info('UrlToVideoOrchestrator: Scene uses static image (no animation)', [
                    'scene_id' => $scene['id'] ?? "scene_{$i}",
                ]);
                continue;
            }

            $imageUrl = $scene['image_url'] ?? null;
            if (empty($imageUrl)) {
                continue;
            }

            try {
                $audioDuration = isset($scene['audio_duration']) ? (float) $scene['audio_duration'] : null;
                $sceneType = $scene['scene_type'] ?? 'dialogue';
                $clipDuration = $this->calculateClipDuration($audioDuration, $sceneType);

                // Use AI-refined prompt if available, otherwise build deterministically
                if (!empty($scene['refined_prompt'])) {
                    $videoPrompt = $scene['video_action'];
                } elseif ($filmTemplateConfig) {
                    $videoPrompt = $this->buildFilmVideoPrompt($scene, $styleConfig, $filmTemplateConfig);
                } else {
                    $videoPrompt = $this->buildVideoPrompt($scene, $styleInstruction, $aspectRatio, $styleConfig, null);
                }

                $animationOptions = [
                    'imageUrl' => $imageUrl,
                    'prompt' => $videoPrompt,
                    'duration' => $clipDuration,
                    'sceneIndex' => $i,
                    'resolution' => $project->metadata['video_resolution'] ?? '480p',
                    'variant' => $project->metadata['video_quality'] ?? 'pro',
                    'generate_audio' => $isFilmMode,
                    'anti_speech' => !$isFilmMode,
                    'has_dialogue' => !empty($scene['has_dialogue']),
                ];

                if ($i < count($scenes) - 1 && !empty($imageUrls[$i + 1])) {
                    $animationOptions['end_image_url'] = $imageUrls[$i + 1];
                }

                $result = $this->animationService->generateAnimation($wizardProject, $animationOptions);

                if (!empty($result['success']) && !empty($result['taskId'])) {
                    $pendingTasks[$i] = $result['taskId'];
                    $scenes[$i]['video_task_id'] = $result['taskId'];
                } elseif (!empty($result['videoUrl'])) {
                    $scenes[$i]['video_url'] = $result['videoUrl'];
                }
            } catch (\Exception $e) {
                Log::warning("UrlToVideoOrchestrator: Video submission failed for segment {$i}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $project->update(['scenes' => $scenes]);

        // Phase 2: Poll pending tasks
        if (!empty($pendingTasks)) {
            $project->updateProgress('generating_video', 72, 'Waiting for video clips (' . count($pendingTasks) . ' jobs)');

            $maxAttempts = 96;
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                sleep(5);

                if ($this->isCancelled($project)) return;

                $stillPending = 0;
                $completedCount = 0;

                foreach ($pendingTasks as $sceneIndex => $taskId) {
                    if (!empty($scenes[$sceneIndex]['video_url'])) {
                        $completedCount++;
                        continue;
                    }

                    try {
                        $status = $this->animationService->getTaskStatus($taskId);
                        $state = $status['status'] ?? 'unknown';

                        if ($state === 'completed' && !empty($status['videoUrl'])) {
                            $scenes[$sceneIndex]['video_url'] = $status['videoUrl'];
                            $completedCount++;
                        } elseif ($state === 'failed') {
                            unset($pendingTasks[$sceneIndex]);
                        } else {
                            $stillPending++;
                        }
                    } catch (\Exception $e) {
                        $stillPending++;
                    }
                }

                $totalTasks = count($pendingTasks);
                $progress = 72 + (int) (($completedCount / max(1, $totalTasks)) * 13);
                $project->updateProgress('generating_video', min(85, $progress),
                    "Video clips: {$completedCount}/{$totalTasks} complete"
                );
                $project->update(['scenes' => $scenes]);

                if ($stillPending === 0) {
                    break;
                }
            }
        }

        $project->update(['scenes' => $scenes]);
        $wizardProject->delete();
    }

    /**
     * Step 5: Assemble the final video.
     */
    protected function stepAssembleFinalVideo(UrlToVideoProject $project): void
    {
        $project->updateProgress('assembling', 85, 'Assembling final video');

        $scenes = $project->scenes ?? [];
        $exportQuality = get_option('story_mode_export_quality', 'balanced');
        $exportResolution = get_option('story_mode_export_resolution', '1080p');
        $captionsEnabled = (bool) get_option('story_mode_captions_enabled', 1);
        $musicEnabled = (bool) get_option('story_mode_music_enabled', 1);
        $musicVolume = (float) get_option('story_mode_music_volume', 0.15);
        $crossfadeDuration = (float) get_option('story_mode_crossfade_duration', 0.5);
        $fadeOutDuration = (float) get_option('story_mode_fadeout_duration', 1.5);
        $transitionType = get_option('story_mode_transition_type', 'fade');

        $metadata = $project->metadata ?? [];
        $isFilmMode = !empty($metadata['film_mode']);

        // Film mode: hard cuts between scenes, no crossfade
        if ($isFilmMode) {
            $crossfadeDuration = 0;
            $transitionType = 'none';
        }

        // Increase crossfade for AI-heavy videos (smoother transitions between generated images)
        $aiSceneCount = collect($scenes)->filter(fn($s) =>
            !empty($s['animate_with_ai']) || (empty($s['video_url']) && empty($s['clips']))
        )->count();
        $aiRatio = count($scenes) > 0 ? $aiSceneCount / count($scenes) : 0;
        if (!$isFilmMode && $aiRatio >= 0.5 && $crossfadeDuration < 1.0) {
            $crossfadeDuration = 1.0;
        }

        $aspectRatio = $project->aspect_ratio ?? '9:16';
        $resMap = [
            '9:16' => ['width' => 1080, 'height' => 1920],
            '16:9' => ['width' => 1920, 'height' => 1080],
            '1:1' => ['width' => 1080, 'height' => 1080],
        ];
        $res = $resMap[$aspectRatio] ?? $resMap['9:16'];

        $manifestScenes = [];
        foreach ($scenes as $i => $scene) {
            $duration = $scene['audio_duration'] ?? $scene['estimated_duration'] ?? 6;

            if ($i === count($scenes) - 1) {
                $duration += 2.5;
            }

            // Use crop focal point if set, otherwise default to center
            $crop = $scene['crop'] ?? null;
            $focalX = $crop ? $crop['focalX'] : 0.5;
            $focalY = $crop ? $crop['focalY'] : 0.5;

            $manifestScenes[] = [
                'imageUrl' => $scene['image_url'] ?? null,
                'videoUrl' => $scene['video_url'] ?? null,
                'voiceoverUrl' => $scene['audio_url'] ?? null,
                'duration' => $duration,
                'narration' => $scene['text'] ?? '',
                'transition_type' => $scene['transition_type'] ?? $transitionType,
                'transition_duration' => (float) ($scene['transition_duration'] ?? $crossfadeDuration),
                'crop' => $crop,
                'video_edit' => $scene['video_edit'] ?? null,
                'clips' => $scene['clips'] ?? null,
                'kenBurns' => $isFilmMode
                    // Film mode: static hold, no zoom/pan (cinema, not slideshow)
                    ? ['startScale' => 1.0, 'endScale' => 1.0, 'startX' => 0.5, 'startY' => 0.5, 'endX' => 0.5, 'endY' => 0.5]
                    // Story/social mode: gentle Ken Burns zoom
                    : ['startScale' => 1.0, 'endScale' => 1.2, 'startX' => $focalX, 'startY' => $focalY, 'endX' => $focalX + (($i % 2 === 0) ? 0.05 : -0.05), 'endY' => $focalY + (($i % 3 === 0) ? 0.05 : -0.05)],
            ];
        }

        $manifest = [
            'scenes' => $manifestScenes,
            'output' => [
                'quality' => $exportQuality,
                'resolution' => $exportResolution,
                'width' => $res['width'],
                'height' => $res['height'],
                'aspectRatio' => $aspectRatio,
                'fps' => 30,
            ],
            'transitions' => [
                'type' => $transitionType,
                'crossfadeDuration' => $crossfadeDuration,
                'fadeOutDuration' => $fadeOutDuration,
            ],
            'music' => $musicEnabled ? ['volume' => $musicVolume] : null,
            'captions' => $captionsEnabled ? ['enabled' => true, 'style' => 'default'] : null,
            'preserve_audio' => $isFilmMode,
            'userId' => $project->user_id,
            'projectId' => $project->id,
        ];

        try {
            $project->updateProgress('assembling', 88, 'Rendering video with FFmpeg');

            Log::info('UrlToVideoOrchestrator: Starting assembly', [
                'project_id' => $project->id,
                'scene_count' => count($manifestScenes),
                'scenes_with_video' => count(array_filter($manifestScenes, fn($s) => !empty($s['videoUrl']))),
                'scenes_with_trim' => count(array_filter($manifestScenes, fn($s) => !empty($s['video_edit']))),
            ]);

            $progressCallback = function (int $progress, string $message) use ($project) {
                $mappedProgress = 88 + (int)(($progress / 100) * 11);
                $project->updateProgress('assembling', min(99, $mappedProgress), $message);
            };

            $result = $this->renderService->processStoryModeExport($manifest, $progressCallback);

            $videoUrl = $result['outputUrl'] ?? null;
            $videoPath = $result['outputPath'] ?? null;

            Log::info('UrlToVideoOrchestrator: Assembly succeeded', [
                'project_id' => $project->id,
                'output_url' => $videoUrl ? substr($videoUrl, 0, 80) : null,
            ]);
        } catch (\Exception $e) {
            Log::error('UrlToVideoOrchestrator: Assembly FAILED — falling back to raw clip', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $videoUrl = null;
            $videoPath = null;
            foreach ($scenes as $scene) {
                if (!empty($scene['video_url'])) {
                    $videoUrl = $scene['video_url'];
                    break;
                }
            }
        }

        $totalDuration = 0;
        foreach ($scenes as $scene) {
            $totalDuration += $scene['audio_duration'] ?? $scene['estimated_duration'] ?? 6;
        }
        $totalDuration -= max(0, count($scenes) - 1) * $crossfadeDuration;

        $project->update([
            'status' => 'ready',
            'progress_percent' => 100,
            'current_stage' => 'Complete',
            'video_url' => $videoUrl,
            'video_path' => $videoPath ?? null,
            'video_duration' => (int) round($totalDuration),
            'metadata' => array_merge($project->metadata ?? [], [
                'completed_at' => now()->toIso8601String(),
                'export_quality' => $exportQuality,
                'export_resolution' => $exportResolution,
                'assembly_method' => !empty($videoPath) ? 'local_ffmpeg' : 'clip_preview',
            ]),
        ]);
    }

    /**
     * Extract dialogue from scene text and format for Seedance lip-sync.
     * Returns character-described dialogue in single quotes for Seedance to vocalize.
     */
    public function extractDialogueForSeedance(array $scene, ?array $filmTemplateConfig): string
    {
        $text = $scene['text'] ?? '';
        if (empty(trim($text)) || !empty($scene['is_visual_only'])) return '';

        $characters = $filmTemplateConfig['characters'] ?? [];

        // Emotional descriptor from scene mood
        $mood = strtolower($scene['mood'] ?? '');
        $emotionMap = [
            'tense' => 'urgently', 'dramatic' => 'intensely', 'epic' => 'powerfully',
            'mysterious' => 'cryptically', 'intimate' => 'softly', 'calm' => 'calmly',
            'intense' => 'fiercely', 'reflective' => 'thoughtfully', 'hopeful' => 'warmly',
        ];
        $emotion = $emotionMap[$mood] ?? 'firmly';

        // Parse "SPEAKER: dialogue" lines from screenplay text
        $dialogueParts = [];
        $lines = preg_split('/\n+/', trim($text));
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^([A-Z][A-Z0-9_\s]+):\s*(.+)$/s', $line, $m)) {
                $speakerName = trim($m[1]);
                $spokenText = trim($m[2], '" ');
                if (empty($spokenText)) continue;

                $brief = $this->getCharacterBriefFromConfig($speakerName, $characters);
                $dialogueParts[] = "{$brief} says {$emotion}, '{$spokenText}'";
            }
        }

        if (empty($dialogueParts)) {
            // Fallback: no speaker pattern found, use raw text as monologue
            $cleanText = trim($text, '" \n');
            $words = explode(' ', $cleanText);
            if (count($words) > 30) $cleanText = implode(' ', array_slice($words, 0, 30));
            $charNames = $scene['characters_in_scene'] ?? [];
            $brief = !empty($charNames) ? $this->getCharacterBriefFromConfig($charNames[0], $characters) : 'The character';
            return "{$brief} says {$emotion}, '{$cleanText}'";
        }

        // Seedance 1.5: single speaker is best, multi-speaker degrades
        // Limit to 2 dialogue lines max, truncate each to 20 words
        $result = [];
        foreach (array_slice($dialogueParts, 0, 2) as $part) {
            if (preg_match("/^(.+says\s+\w+,\s*')(.+)(')$/s", $part, $pm)) {
                $words = explode(' ', $pm[2]);
                if (count($words) > 20) {
                    $part = $pm[1] . implode(' ', array_slice($words, 0, 20)) . $pm[3];
                }
            }
            $result[] = $part;
        }

        return implode(' ', $result);
    }

    /**
     * Get visual brief for a character name from template config.
     * "REN" + config → "A man with a cybernetic ear implant"
     */
    protected function getCharacterBriefFromConfig(string $name, array $characters): string
    {
        foreach ($characters as $char) {
            if (strtoupper(trim($char['name'])) === strtoupper(trim($name))) {
                $gender = $char['gender'] ?? 'unknown';
                $subject = match ($gender) {
                    'male' => 'A man', 'female' => 'A woman', default => 'A person',
                };
                $desc = $char['description'] ?? '';
                $parts = array_map('trim', explode(',', $desc));
                foreach ($parts as $part) {
                    if (preg_match('/\b(cybernetic|implant|scar|tattoo|visor|prosthetic|augmented|glowing|silver|chrome)\b/i', $part)) {
                        $trait = strtolower(trim($part));
                        // Remove positional phrases: "above right ear", "on forehead", etc.
                        $trait = preg_replace('/\b(above|below|on|near|behind|across|around|over|under|pushed\s+up\s+on|attached\s+to)\b.*$/i', '', $trait);
                        return $subject . ' with ' . trim($trait);
                    }
                }
                foreach ($parts as $part) {
                    if (preg_match('/\b(hair|bald|shaved|dreadlocks|braids|mohawk)\b/i', $part)) {
                        $trait = strtolower(trim($part));
                        $trait = preg_replace('/\b(above|below|on|near|behind|across|around|over|under|pushed\s+up\s+on|attached\s+to)\b.*$/i', '', $trait);
                        return $subject . ' with ' . trim($trait);
                    }
                }
                return $subject;
            }
        }
        return 'The character';
    }

    /**
     * Extract only DYNAMIC elements from source text — things that visually MOVE in the video.
     * Replaces buildRichEnvironmentalDetail() for Film mode. Does NOT describe static architecture.
     */
    protected function extractDynamicCues(string $sourceText): string
    {
        $cues = [];
        $text = strtolower($sourceText);

        if (preg_match('/\b(rain|drizzle|downpour)\b/', $text)) {
            $cues[] = 'Rain continues to fall';
        }
        if (preg_match('/\b(smoke|steam|vapor)\b/', $text)) {
            $cues[] = 'Smoke drifts lazily through the air';
        }
        if (preg_match('/\b(wind|breeze|blowing)\b/', $text)) {
            $cues[] = 'Wind tugs at clothing and hair';
        }
        if (preg_match('/\b(flicker|pulse|glow)\b/', $text)) {
            $cues[] = 'Lights flicker and pulse';
        }
        if (preg_match('/\b(snow|snowfall)\b/', $text)) {
            $cues[] = 'Snowflakes drift slowly through the air';
        }
        if (preg_match('/\b(fire|flame|ember)\b/', $text)) {
            $cues[] = 'Flames dance and flicker';
        }

        // Limit to 2 cues max to stay concise
        return implode('. ', array_slice($cues, 0, 2));
    }

    /**
     * Get mood-appropriate action padding phrases for short video prompts.
     * These describe character behavior and atmosphere — NOT static environment.
     */
    protected function getMoodActionPadding(string $mood): array
    {
        $padding = [
            'tense' => [
                'Every motion is deliberate, tension visible in each gesture.',
                'The air feels charged with anticipation, breaths shallow and controlled.',
            ],
            'dramatic' => [
                'Each movement carries weight and purpose, emotions visible in every gesture.',
                'The scene unfolds with cinematic intensity, every detail heightened.',
            ],
            'mysterious' => [
                'Shadows shift subtly across the face, hinting at hidden depths.',
                'Each glance reveals layers of unspoken meaning and quiet calculation.',
            ],
            'calm' => [
                'Movements are unhurried, carrying a quiet confidence and ease.',
                'The scene breathes with natural, effortless rhythm and composure.',
            ],
            'intense' => [
                'Energy pulses through every action and reaction, urgency building.',
                'The pace quickens with each passing moment, stakes rising visibly.',
            ],
            'intimate' => [
                'Micro-expressions reveal deep, unspoken emotion between characters.',
                'The space narrows with shared vulnerability, each look meaningful.',
            ],
            'reflective' => [
                'The gaze drifts inward, lost in thought, features softening with memory.',
                'A quiet stillness settles, movements slowing as realization takes hold.',
            ],
            'hopeful' => [
                'The expression brightens subtly, a warmth spreading across the features.',
                'Body language opens, tension releasing as possibility takes shape.',
            ],
        ];

        return $padding[$mood] ?? $padding['dramatic'];
    }

    /**
     * Build physical speaking description for dialogue scenes.
     * Maps mood to body language and identifies speakers from screenplay text.
     */
    protected function buildDialogueAction(array $scene, array $filmTemplateConfig): string
    {
        $text = $scene['text'] ?? '';
        if (empty(trim($text))) return '';

        $characters = $filmTemplateConfig['characters'] ?? [];
        $mood = strtolower($scene['mood'] ?? '');

        // Map mood to physical speaking manner
        $mannerMap = [
            'tense' => 'leans forward, speaking with urgency, jaw tight',
            'dramatic' => 'speaks with intensity, gesturing emphatically',
            'mysterious' => 'speaks in low tones, barely moving their lips',
            'intimate' => 'speaks softly, leaning close',
            'calm' => 'speaks evenly, relaxed posture',
            'intense' => 'speaks forcefully, body tense',
            'reflective' => 'speaks thoughtfully, gaze distant',
            'hopeful' => 'speaks warmly, expression brightening',
        ];
        $manner = $mannerMap[$mood] ?? 'speaks, lips moving clearly';

        // Parse dialogue lines to identify speakers
        $speakers = [];
        foreach (preg_split('/\n+/', trim($text)) as $line) {
            if (preg_match('/^([A-Z][A-Z0-9_\s]+):\s*(.+)$/s', trim($line), $m)) {
                $name = trim($m[1]);
                if (!in_array($name, $speakers)) $speakers[] = $name;
            }
        }

        if (count($speakers) === 0) return '';

        if (count($speakers) === 1) {
            $brief = $this->getCharacterBriefFromConfig($speakers[0], $characters);
            return "{$brief} {$manner}.";
        } else {
            $brief1 = $this->getCharacterBriefFromConfig($speakers[0], $characters);
            $brief2 = $this->getCharacterBriefFromConfig($speakers[1], $characters);
            return "{$brief1} and {$brief2} face each other, exchanging words with visible emotion.";
        }
    }

    /**
     * Build a Seedance video prompt for standard/creative mode scenes.
     * Produces a 100-130 word flowing narrative that weaves setting, camera,
     * subject action, environmental physics, and mood together.
     */
    public function buildVideoPrompt(array $scene, string $styleInstruction, string $aspectRatio, ?array $styleConfig = null, ?array $filmTemplateConfig = null): string
    {
        $videoAction = trim($scene['video_action'] ?? '');
        $direction = trim($scene['direction'] ?? '');
        $cameraMotion = $scene['camera_motion'] ?? 'slow zoom in';
        $mood = strtolower(trim($scene['mood'] ?? ''));
        $videoAnchor = ($styleConfig['videoAnchor'] ?? '');

        $sourceText = !empty($videoAction) ? $videoAction : $direction;
        if (empty($sourceText)) {
            $sourceText = trim($scene['text'] ?? '');
        }

        if (empty($sourceText)) {
            return $this->mapCameraToSeedance($cameraMotion);
        }

        // Build flowing narrative in layers
        $narrative = '';

        // Layer 1: Scene setting opener
        $narrative .= $this->extractSettingOpener($sourceText);

        // Layer 2: First camera movement woven in
        $cameraPhrase = $this->mapCameraToSeedance($cameraMotion);
        $narrative .= ' ' . $cameraPhrase . '.';

        // Layer 3: Remaining action content
        $actionContent = $this->extractActionContent($sourceText);
        if (!empty($actionContent)) {
            $narrative .= ' ' . $actionContent;
        }

        // Layer 4: Environmental physics (rich detail)
        $envDetail = $this->buildRichEnvironmentalDetail('', $mood, $sourceText, $videoAnchor);
        if (!empty($envDetail)) {
            $narrative .= ' ' . $envDetail;
        }

        // Layer 5: Second camera reference + mood closer
        $narrative .= ' ' . $this->buildMoodCloser($mood, $cameraMotion);

        // Enforce 100-130 word range and clean up
        $narrative = $this->enforceWordRange($narrative, 100, 130);

        if (class_exists(SeedancePromptService::class)) {
            $narrative = SeedancePromptService::sanitize($narrative);
        }

        return trim($narrative);
    }

    /**
     * Build a Seedance video prompt for film mode scenes.
     * Lean 70-100 word action-focused prompt: character actions + camera + dynamic elements + dialogue.
     * Does NOT describe static environment (buildings, walls, furniture) — Seedance already sees the image.
     */
    public function buildFilmVideoPrompt(array $scene, ?array $styleConfig, array $filmTemplateConfig): string
    {
        $videoAction = trim($scene['video_action'] ?? '');
        $direction = trim($scene['direction'] ?? '');
        $cameraMotion = $scene['camera_motion'] ?? 'slow zoom in';

        // Source text = video_action (character actions) or direction fallback
        $sourceText = !empty($videoAction) ? $videoAction : $direction;
        if (empty($sourceText)) {
            $sourceText = trim($scene['text'] ?? '');
        }
        if (empty($sourceText)) {
            return $this->mapCameraToSeedance($cameraMotion);
        }

        // 1. Core action — the full source text (already has character descriptions)
        $narrative = trim($sourceText);

        // 2. Camera motion — weave in after first sentence (not repeated at end)
        $cameraPhrase = $this->mapCameraToSeedance($cameraMotion);
        $sentences = preg_split('/(?<=[.!?])\s+/', $narrative, 3, PREG_SPLIT_NO_EMPTY);
        if (count($sentences) >= 2) {
            $narrative = $sentences[0] . '. ' . $cameraPhrase . '. ' . implode(' ', array_slice($sentences, 1));
        } else {
            $narrative .= ' ' . $cameraPhrase . '.';
        }

        // 3. Dynamic elements only (things that MOVE, not static environment)
        $dynamicCues = $this->extractDynamicCues($sourceText);
        if (!empty($dynamicCues)) {
            $narrative .= ' ' . $dynamicCues;
        }

        // 4. Dialogue action (physical speaking description if applicable)
        // Skip if buildConciseVideoAction() already appended a dialogue hint ("speaks firmly/urgently/...")
        if (!empty($scene['has_dialogue']) && !preg_match('/speaks\s+\w+ly\b/', $narrative)) {
            $dialogueAction = $this->buildDialogueAction($scene, $filmTemplateConfig);
            if (!empty($dialogueAction)) {
                $narrative .= ' ' . $dialogueAction;
            }
        }

        // Enforce 55-100 word range — pad with mood-appropriate action phrases if too short
        $words = explode(' ', trim($narrative));
        $count = count($words);

        if ($count < 55) {
            $mood = strtolower($scene['mood'] ?? 'dramatic');
            $padding = $this->getMoodActionPadding($mood);
            $idx = 0;
            while (count(explode(' ', $narrative)) < 55 && $idx < count($padding)) {
                $narrative .= ' ' . $padding[$idx];
                $idx++;
            }
        }

        $words = explode(' ', trim($narrative));
        if (count($words) > 100) {
            $narrative = implode(' ', array_slice($words, 0, 100));
            $narrative = rtrim($narrative, ' ,;') . '.';
        }

        if (class_exists(SeedancePromptService::class)) {
            $narrative = SeedancePromptService::sanitize($narrative);
        }

        return trim($narrative);
    }

    /**
     * Extract the first 1-2 sentences from source text as the scene setting opener.
     */
    public function extractSettingOpener(string $sourceText): string
    {
        if (empty($sourceText)) return '';

        $sentences = preg_split('/(?<=[.!?])\s+/', trim($sourceText), -1, PREG_SPLIT_NO_EMPTY);

        if (count($sentences) <= 2) {
            return rtrim(implode(' ', $sentences), '. ') . '.';
        }

        // Take first 2 sentences as the setting foundation
        return rtrim(implode(' ', array_slice($sentences, 0, 2)), '. ') . '.';
    }

    /**
     * Extract action content from source text (sentences after the setting opener).
     */
    public function extractActionContent(string $sourceText): string
    {
        if (empty($sourceText)) return '';

        $sentences = preg_split('/(?<=[.!?])\s+/', trim($sourceText), -1, PREG_SPLIT_NO_EMPTY);

        if (count($sentences) <= 2) {
            return ''; // All used by setting opener
        }

        // Return remaining sentences as the action content
        return rtrim(implode(' ', array_slice($sentences, 2)), '. ') . '.';
    }

    /**
     * Build rich environmental detail (3-4 cues) from atmosphere, mood, source text, and style anchor.
     */
    public function buildRichEnvironmentalDetail(string $atmosphere, string $mood, string $sourceText, string $videoAnchor, bool $isInterior = false): string
    {
        $cues = [];
        // For interior scenes, only scan the sourceText (scene direction) — NOT the template
        // atmosphere/videoAnchor, which contain outdoor keywords like "rain", "neon", "streets"
        $combined = $isInterior
            ? strtolower($sourceText)
            : strtolower($atmosphere . ' ' . $sourceText . ' ' . $videoAnchor);

        if ($isInterior) {
            // === INTERIOR environmental cues — indoor-appropriate only ===

            // Indoor tech/screens
            if (preg_match('/\b(screen|monitor|terminal|holograph|display|interface|data|digital|server)\b/', $combined)) {
                $cues[] = 'Screens and displays cast a flickering ambient glow, data readouts scrolling with soft luminescence';
            }
            // Indoor lighting
            if (preg_match('/\b(fluorescent|lamp|overhead|dim|lit|light|flicker)\b/', $combined)) {
                $cues[] = 'Overhead lights hum faintly, casting pools of artificial light that leave deep shadows in corners';
            }
            // Machinery/industrial
            if (preg_match('/\b(server|machine|engine|generator|wire|cable|rack|vent|pipe)\b/', $combined)) {
                $cues[] = 'Machinery hums with a low resonant vibration, indicator lights blinking in rhythmic patterns';
            }
            // Steam/atmosphere
            if (preg_match('/\b(steam|smoke|haze|fog|vapor|vent)\b/', $combined)) {
                $cues[] = 'Wisps of steam curl from vents, drifting through the artificial light in lazy spirals';
            }
            // Sparks/electrical
            if (preg_match('/\b(spark|electri|arc|surge|power|energy)\b/', $combined)) {
                $cues[] = 'Electrical sparks dance briefly in the air, casting sharp fleeting shadows on nearby walls';
            }
            // Dust/particles in indoor space
            if (preg_match('/\b(dust|particle|debris|ash)\b/', $combined)) {
                $cues[] = 'Dust motes drift through shafts of artificial light, suspended weightlessly in the still air';
            }

            // Style anchor color enrichment still applies indoors
            if (!empty($videoAnchor) && str_word_count($videoAnchor) <= 12) {
                $anchorLower = strtolower($videoAnchor);
                if (preg_match('/\b(teal|orange|amber|blue|cyan|magenta|gold|crimson|violet)\b/', $anchorLower, $colorMatch)) {
                    $color = $colorMatch[1];
                    $cues[] = ucfirst($color) . ' light spills across surfaces, tinting the interior with a ' . $color . ' undertone';
                }
            }

            if (empty($cues)) {
                return match ($mood) {
                    'tense', 'intense' => 'The enclosed space feels charged with barely contained energy, shadows pooling in corners. Faint mechanical hums resonate through the walls.',
                    'mysterious' => 'Shadows gather in the corners of the room, faint light sources flickering at the edges. The air hangs heavy and still.',
                    'dramatic', 'epic' => 'The interior atmosphere pulses with dramatic tension, artificial light and deep shadow playing across every surface.',
                    default => 'The enclosed space breathes with subtle ambient motion, artificial light casting soft gradients across walls and surfaces.',
                };
            }
        } else {
            // === EXTERIOR environmental cues — original behavior ===

            // Rain/water
            if (preg_match('/\b(rain|wet|storm|drizzle|downpour)\b/', $combined)) {
                $cues[] = 'Rain streaks diagonally through volumetric light beams, wet surfaces catching long shimmering reflections';
            }
            // Wind
            if (preg_match('/\b(wind|breeze|gust|blowing|billowing)\b/', $combined)) {
                $cues[] = 'Wind tugs at clothing and hair, sending loose fabric rippling in slow arcs';
            }
            // Neon/lights
            if (preg_match('/\b(neon|holograph|flicker|glow|pulse|electric)\b/', $combined)) {
                $cues[] = 'Neon signs flicker and pulse with shifting color, casting teal and orange pools of light across nearby surfaces';
            }
            // Fire/flames
            if (preg_match('/\b(fire|flame|burn|ember|torch|candle)\b/', $combined)) {
                $cues[] = 'Flames lick upward in dancing arcs, glowing embers drifting lazily into the darkness above';
            }
            // Smoke/fog/mist
            if (preg_match('/\b(smoke|fog|mist|haze|steam|vapor)\b/', $combined)) {
                $cues[] = 'Wisps of atmospheric haze curl and drift through the scene in lazy spirals';
            }
            // Nature
            if (preg_match('/\b(forest|trees?|leaves?|grass|plant|foliage)\b/', $combined)) {
                $cues[] = 'Foliage sways in a gentle rhythm, scattered leaves drifting slowly through shafts of light';
            }
            // Crowd/traffic
            if (preg_match('/\b(crowd|people|traffic|vehicles?|cars?|pedestrian)\b/', $combined)) {
                $cues[] = 'Figures move through the background, their silhouettes blurring with distance and atmospheric depth';
            }
            // Particles/dust
            if (preg_match('/\b(dust|particle|debris|sand|snow|ash)\b/', $combined)) {
                $cues[] = 'Tiny particles catch the light as they drift weightlessly through the air';
            }
            // Water/ocean
            if (preg_match('/\b(ocean|sea|water|waves?|river|lake)\b/', $combined)) {
                $cues[] = 'Water undulates with gentle rippling movement, light dancing across the liquid surface';
            }
            // City/urban
            if (preg_match('/\b(city|urban|street|alley|rooftop|skyline|skyscraper|building)\b/', $combined)) {
                $cues[] = 'Distant city lights twinkle through atmospheric haze, the urban landscape breathing with subtle motion';
            }
            // Tech/cyber
            if (preg_match('/\b(cyber|tech|digital|hologram|screen|data|interface)\b/', $combined)) {
                $cues[] = 'Digital readouts flicker with scrolling data, holographic displays casting a soft ambient glow';
            }

            // Style anchor enrichment
            if (!empty($videoAnchor) && str_word_count($videoAnchor) <= 12) {
                $anchorLower = strtolower($videoAnchor);
                if (preg_match('/\b(teal|orange|amber|blue|cyan|magenta|gold|crimson|violet)\b/', $anchorLower, $colorMatch)) {
                    $color = $colorMatch[1];
                    $cues[] = ucfirst($color) . ' light spills across surfaces, tinting the atmosphere with a ' . $color . ' undertone';
                }
            }

            if (empty($cues)) {
                return match ($mood) {
                    'tense', 'intense' => 'The air feels charged with barely contained energy, shadows shifting with subtle menace. Dust motes hang suspended in angled beams of harsh light.',
                    'calm', 'reflective' => 'Soft ambient light filters through the space, gentle atmospheric particles drifting weightlessly. The environment breathes with a quiet, meditative stillness.',
                    'dramatic', 'epic' => 'The atmosphere pulses with dramatic energy, light and shadow playing across every surface. Wind stirs the environment, carrying particles that catch the light.',
                    'mysterious' => 'Shadows pool in unexpected corners, atmospheric haze obscuring distant details. Faint light sources flicker at the edges of perception.',
                    'hopeful', 'warm' => 'Warm golden light suffuses the scene, dust motes floating lazily through sunbeams. A gentle warmth radiates through the atmosphere.',
                    default => 'Subtle ambient motion fills the environment, atmospheric particles drifting through gentle currents of air. Light plays softly across textures and surfaces.',
                };
            }
        }

        return implode('. ', array_slice($cues, 0, 4)) . '.';
    }

    /**
     * Build a mood-based closer with a second camera reference.
     */
    public function buildMoodCloser(string $mood, string $cameraMotion): string
    {
        // Second camera reference — continuation phrasing
        $cameraVerb = match (true) {
            str_contains($cameraMotion, 'zoom in'), str_contains($cameraMotion, 'push') => 'continues its steady advance forward',
            str_contains($cameraMotion, 'zoom out'), str_contains($cameraMotion, 'pull') => 'continues pulling back to reveal the wider scene',
            str_contains($cameraMotion, 'pan left') => 'continues its smooth lateral drift to the left',
            str_contains($cameraMotion, 'pan right') => 'continues its smooth lateral drift to the right',
            str_contains($cameraMotion, 'tilt up'), str_contains($cameraMotion, 'rise') => 'continues its smooth upward ascent',
            str_contains($cameraMotion, 'tilt down') => 'continues its slow downward gaze',
            str_contains($cameraMotion, 'diagonal') => 'continues its floating diagonal path',
            str_contains($cameraMotion, 'breathe'), str_contains($cameraMotion, 'settle') => 'holds with a gentle breathing motion',
            default => 'continues its smooth, deliberate movement',
        };

        // Mood-based emotional closer
        $moodPhrase = match ($mood) {
            'tense', 'intense' => 'charged with barely contained energy',
            'mysterious' => 'shrouded in enigma and quiet menace',
            'calm', 'reflective' => 'suffused with a serene, contemplative stillness',
            'dramatic', 'epic' => 'resonating with sweeping grandeur',
            'hopeful', 'warm' => 'glowing with quiet optimism',
            'intimate' => 'wrapped in a close, personal warmth',
            'melancholic', 'sad' => 'heavy with unspoken sorrow',
            'triumphant' => 'surging with victorious energy',
            default => 'permeating the atmosphere with a tangible sense of presence',
        };

        return "The camera {$cameraVerb}, the scene conveying a feeling {$moodPhrase}.";
    }

    /**
     * Enforce a word count range on the narrative.
     * Under minimum: pad with atmospheric detail. Over maximum: trim middle while keeping opener + closer.
     */
    public function enforceWordRange(string $text, int $min, int $max): string
    {
        // Clean up first
        $text = preg_replace('/\.\s*\./', '.', $text);
        $text = preg_replace('/,\s*,/', ',', $text);
        $text = preg_replace('/\s{2,}/', ' ', trim($text));

        $words = explode(' ', $text);
        $count = count($words);

        // Under minimum — pad with atmospheric filler
        if ($count < $min) {
            $padding = [
                'Every surface holds a subtle interplay of light and texture.',
                'The atmosphere carries a palpable weight, rich with sensory detail.',
                'Small environmental details come alive with organic, naturalistic motion.',
                'The interplay of foreground and background creates a layered sense of depth and scale.',
            ];
            $idx = 0;
            while (count(explode(' ', $text)) < $min && $idx < count($padding)) {
                // Insert padding before the last sentence (the mood closer)
                $lastPeriod = strrpos($text, '. The camera');
                if ($lastPeriod !== false) {
                    $text = substr($text, 0, $lastPeriod) . '. ' . $padding[$idx] . substr($text, $lastPeriod);
                } else {
                    $text .= ' ' . $padding[$idx];
                }
                $idx++;
            }
        }

        // Over maximum — trim from the middle, keeping opener + closer
        $words = explode(' ', $text);
        if (count($words) > $max) {
            // Keep first 40 words and last 40 words, drop the middle
            $keepStart = 40;
            $keepEnd = 40;
            if ($keepStart + $keepEnd >= $max) {
                $keepStart = (int) floor($max * 0.5);
                $keepEnd = $max - $keepStart;
            }
            $start = array_slice($words, 0, $keepStart);
            $end = array_slice($words, -$keepEnd);
            $text = implode(' ', $start) . '. ' . implode(' ', $end);
        }

        // Final cleanup
        $text = preg_replace('/\.\s*\./', '.', $text);
        $text = preg_replace('/\s{2,}/', ' ', trim($text));

        return $text;
    }

    public function mapCameraToSeedance(string $cameraMotion): string
    {
        $map = [
            'slow zoom in'      => 'The camera slowly pushes in closer',
            'slow zoom out'     => 'The camera gradually pulls back to reveal more',
            'dramatic zoom in'  => 'The camera rapidly pushes in',
            'pan left'          => 'The camera pans slowly to the left',
            'pan right'         => 'The camera pans slowly to the right',
            'pan left slow'     => 'The camera drifts gently to the left',
            'pan right slow'    => 'The camera drifts gently to the right',
            'tilt up'           => 'The camera tilts slowly upward',
            'tilt down'         => 'The camera tilts slowly downward',
            'push to subject'   => 'The camera pushes steadily toward the subject',
            'rise and reveal'   => 'The camera rises upward in a crane shot, revealing the scene',
            'settle in'         => 'The camera settles with a subtle, nearly locked-off motion',
            'breathe'           => 'The camera holds with a very subtle breathing motion',
            'zoom in pan right' => 'The camera pushes in while panning right',
            'zoom out pan left' => 'The camera pulls back while panning left',
            'diagonal drift'    => 'The camera drifts diagonally in a floating motion',
        ];

        return $map[strtolower(trim($cameraMotion))] ?? 'The camera slowly pushes in';
    }

    protected function calculateClipDuration(?float $audioDuration, string $sceneType = 'dialogue'): int
    {
        // Only tension/quick-cut scenes get 5 seconds; everything else defaults to 10
        $isShort = in_array($sceneType, ['tension']);

        if ($audioDuration === null || $audioDuration <= 0) {
            return $isShort ? 5 : 10;
        }

        $withPadding = $audioDuration + 2.0;

        if ($isShort) {
            return (int) min(5, ceil($withPadding));
        }

        // Long scenes: minimum 10 seconds, snap to 10
        return (int) max(10, min(10, ceil($withPadding)));
    }

    /**
     * Create a temporary WizardProject for sub-service compatibility.
     */
    protected function createTempWizardProject(UrlToVideoProject $project): WizardProject
    {
        return WizardProject::create([
            'user_id' => $project->user_id,
            'team_id' => $project->team_id,
            'name' => "[UrlToVideo] {$project->title}",
            'status' => 'processing',
            'aspect_ratio' => $project->aspect_ratio ?? '9:16',
            'platform' => 'multi-platform',
        ]);
    }

    protected function ensureProjectDir(int $projectId, string $subDir = ''): string
    {
        $path = public_path("url-to-video/{$projectId}");
        if ($subDir) {
            $path .= "/{$subDir}";
        }
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
        return $path;
    }
}
