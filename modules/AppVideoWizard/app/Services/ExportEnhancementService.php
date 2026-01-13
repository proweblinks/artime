<?php

namespace Modules\AppVideoWizard\Services;

use Modules\AppVideoWizard\Models\WizardProject;
use Illuminate\Support\Facades\Log;

/**
 * Export Enhancement Service - Phase 5: Export & Assembly Enhancement
 *
 * This service enhances the video export pipeline by leveraging Story Bible data:
 * - Character voice mapping (assign consistent voices to characters)
 * - Visual style presets for export (color grading, transitions)
 * - Metadata embedding (title, description, tags from Bible)
 * - Scene transition recommendations based on pacing
 * - Audio mood suggestions for background music
 */
class ExportEnhancementService
{
    /**
     * Voice presets for different character archetypes.
     * Maps character roles/traits to recommended voice settings.
     */
    public const VOICE_PRESETS = [
        'protagonist' => [
            'pitch' => 'medium',
            'speed' => 'normal',
            'style' => 'confident',
            'description' => 'Clear, engaging, relatable voice',
        ],
        'antagonist' => [
            'pitch' => 'low',
            'speed' => 'slow',
            'style' => 'intense',
            'description' => 'Deep, measured, commanding voice',
        ],
        'mentor' => [
            'pitch' => 'medium-low',
            'speed' => 'slow',
            'style' => 'wise',
            'description' => 'Warm, thoughtful, experienced voice',
        ],
        'sidekick' => [
            'pitch' => 'medium-high',
            'speed' => 'fast',
            'style' => 'energetic',
            'description' => 'Lively, supportive, enthusiastic voice',
        ],
        'narrator' => [
            'pitch' => 'medium',
            'speed' => 'measured',
            'style' => 'authoritative',
            'description' => 'Professional, clear, trustworthy voice',
        ],
        'child' => [
            'pitch' => 'high',
            'speed' => 'fast',
            'style' => 'innocent',
            'description' => 'Light, curious, youthful voice',
        ],
        'elder' => [
            'pitch' => 'low',
            'speed' => 'very-slow',
            'style' => 'weathered',
            'description' => 'Gravelly, wise, nostalgic voice',
        ],
    ];

    /**
     * Transition presets based on pacing.
     */
    public const TRANSITION_PRESETS = [
        'fast' => [
            'default' => 'cut',
            'scene_change' => 'quick-fade',
            'duration' => 0.3,
            'options' => ['cut', 'quick-fade', 'swipe', 'zoom'],
        ],
        'medium' => [
            'default' => 'crossfade',
            'scene_change' => 'fade',
            'duration' => 0.5,
            'options' => ['crossfade', 'fade', 'dissolve', 'wipe'],
        ],
        'slow' => [
            'default' => 'slow-dissolve',
            'scene_change' => 'long-fade',
            'duration' => 1.0,
            'options' => ['slow-dissolve', 'long-fade', 'blur-transition', 'morph'],
        ],
    ];

    /**
     * Color grading presets based on visual style modes.
     */
    public const COLOR_GRADE_PRESETS = [
        'cinematic' => [
            'contrast' => 1.1,
            'saturation' => 0.95,
            'shadows' => '#1a1a2e',
            'highlights' => '#ffeaa7',
            'lut' => 'cinematic-teal-orange',
            'description' => 'Film-like color grading with teal shadows and warm highlights',
        ],
        'documentary' => [
            'contrast' => 1.0,
            'saturation' => 1.0,
            'shadows' => '#2d2d2d',
            'highlights' => '#ffffff',
            'lut' => 'documentary-natural',
            'description' => 'Natural, authentic colors with minimal grading',
        ],
        'animated' => [
            'contrast' => 1.2,
            'saturation' => 1.3,
            'shadows' => '#2c3e50',
            'highlights' => '#ecf0f1',
            'lut' => 'vibrant-animation',
            'description' => 'Vibrant, punchy colors with high saturation',
        ],
        'stock' => [
            'contrast' => 1.05,
            'saturation' => 1.1,
            'shadows' => '#333333',
            'highlights' => '#f5f5f5',
            'lut' => 'professional-clean',
            'description' => 'Clean, professional look suitable for business content',
        ],
    ];

    /**
     * Build export configuration from Story Bible.
     */
    public function buildExportConfig(WizardProject $project): array
    {
        $bible = $project->story_bible ?? [];
        $config = [
            'metadata' => $this->buildMetadata($bible, $project),
            'voiceMapping' => $this->buildVoiceMapping($bible),
            'transitions' => $this->buildTransitionConfig($bible),
            'colorGrading' => $this->buildColorGradingConfig($bible),
            'audio' => $this->buildAudioConfig($bible),
            'subtitles' => $this->buildSubtitleConfig($bible),
        ];

        Log::info('ExportEnhancement: Built export config from Bible', [
            'hasMetadata' => !empty($config['metadata']['title']),
            'voiceCount' => count($config['voiceMapping']),
            'transitionStyle' => $config['transitions']['default'] ?? 'cut',
            'colorGrade' => $config['colorGrading']['preset'] ?? 'none',
        ]);

        return $config;
    }

    /**
     * Build video metadata from Story Bible.
     */
    protected function buildMetadata(array $bible, WizardProject $project): array
    {
        $title = $bible['title'] ?? $project->name ?? 'Untitled Video';
        $logline = $bible['logline'] ?? '';
        $theme = $bible['theme'] ?? '';
        $genre = $bible['genre'] ?? '';

        // Build description from Bible elements
        $description = $logline;
        if (!empty($theme)) {
            $description .= "\n\nTheme: {$theme}";
        }

        // Generate tags from Bible
        $tags = [];
        if (!empty($genre)) {
            $tags[] = strtolower($genre);
        }
        if (!empty($bible['tone'])) {
            $tags[] = strtolower($bible['tone']);
        }
        // Add character names as potential tags
        foreach ($bible['characters'] ?? [] as $char) {
            if (!empty($char['name'])) {
                $tags[] = strtolower($char['name']);
            }
        }

        return [
            'title' => $title,
            'description' => $description,
            'tags' => array_unique($tags),
            'genre' => $genre,
            'duration' => $project->target_duration ?? 60,
            'aspectRatio' => $project->aspect_ratio ?? '16:9',
            'platform' => $project->platform ?? 'youtube',
        ];
    }

    /**
     * Build voice mapping for characters.
     */
    protected function buildVoiceMapping(array $bible): array
    {
        $mapping = [];

        foreach ($bible['characters'] ?? [] as $character) {
            $name = $character['name'] ?? 'Unknown';
            $role = strtolower($character['role'] ?? 'supporting');
            $voiceStyle = $character['voiceStyle'] ?? '';

            // Get preset based on role
            $preset = self::VOICE_PRESETS[$role] ?? self::VOICE_PRESETS['narrator'];

            // Analyze voice style for additional hints
            $voiceConfig = $this->analyzeVoiceStyle($voiceStyle, $preset);

            $mapping[$name] = [
                'role' => $role,
                'preset' => $preset,
                'voiceStyle' => $voiceStyle,
                'recommendedSettings' => $voiceConfig,
                'suggestedVoiceId' => $this->suggestVoiceId($role, $voiceStyle),
            ];
        }

        // Add narrator if no protagonist
        if (!isset($mapping['Narrator']) && empty($bible['characters'])) {
            $mapping['Narrator'] = [
                'role' => 'narrator',
                'preset' => self::VOICE_PRESETS['narrator'],
                'voiceStyle' => 'Professional narrator',
                'recommendedSettings' => self::VOICE_PRESETS['narrator'],
                'suggestedVoiceId' => null,
            ];
        }

        return $mapping;
    }

    /**
     * Analyze voice style text for additional configuration.
     */
    protected function analyzeVoiceStyle(string $voiceStyle, array $preset): array
    {
        $config = $preset;
        $styleLower = strtolower($voiceStyle);

        // Adjust based on keywords in voice style
        if (strpos($styleLower, 'deep') !== false || strpos($styleLower, 'bass') !== false) {
            $config['pitch'] = 'low';
        }
        if (strpos($styleLower, 'high') !== false || strpos($styleLower, 'light') !== false) {
            $config['pitch'] = 'high';
        }
        if (strpos($styleLower, 'fast') !== false || strpos($styleLower, 'rapid') !== false) {
            $config['speed'] = 'fast';
        }
        if (strpos($styleLower, 'slow') !== false || strpos($styleLower, 'deliberate') !== false) {
            $config['speed'] = 'slow';
        }
        if (strpos($styleLower, 'accent') !== false) {
            $config['accent'] = true;
        }

        return $config;
    }

    /**
     * Suggest a voice ID based on role and style.
     */
    protected function suggestVoiceId(string $role, string $voiceStyle): ?string
    {
        // This would map to actual TTS voice IDs (ElevenLabs, etc.)
        // Returns null to let the system choose, or could return specific IDs
        return null;
    }

    /**
     * Build transition configuration from pacing.
     */
    protected function buildTransitionConfig(array $bible): array
    {
        $pacing = $bible['pacing'] ?? [];
        $overallPace = strtolower($pacing['overallPace'] ?? 'medium');
        $transitionStyle = $pacing['transitionStyle'] ?? '';

        // Get preset based on pace
        $preset = self::TRANSITION_PRESETS[$overallPace] ?? self::TRANSITION_PRESETS['medium'];

        // Analyze transition style for custom settings
        $styleLower = strtolower($transitionStyle);
        if (strpos($styleLower, 'cut') !== false) {
            $preset['default'] = 'cut';
        }
        if (strpos($styleLower, 'fade') !== false) {
            $preset['default'] = 'fade';
        }
        if (strpos($styleLower, 'dissolve') !== false) {
            $preset['default'] = 'dissolve';
        }

        return [
            'pace' => $overallPace,
            'default' => $preset['default'],
            'sceneChange' => $preset['scene_change'],
            'duration' => $preset['duration'],
            'availableOptions' => $preset['options'],
            'customStyle' => $transitionStyle,
        ];
    }

    /**
     * Build color grading configuration from visual style.
     */
    protected function buildColorGradingConfig(array $bible): array
    {
        $visualStyle = $bible['visualStyle'] ?? [];
        $mode = strtolower($visualStyle['mode'] ?? 'cinematic');
        $colorPalette = $visualStyle['colorPalette'] ?? [];
        $lighting = $visualStyle['lighting'] ?? '';

        // Get preset based on mode
        $preset = self::COLOR_GRADE_PRESETS[$mode] ?? self::COLOR_GRADE_PRESETS['cinematic'];

        // Customize based on Bible color palette
        if (!empty($colorPalette) && count($colorPalette) >= 2) {
            $preset['shadows'] = $colorPalette[0] ?? $preset['shadows'];
            $preset['highlights'] = $colorPalette[count($colorPalette) - 1] ?? $preset['highlights'];
        }

        // Adjust based on lighting description
        $lightingLower = strtolower($lighting);
        if (strpos($lightingLower, 'high contrast') !== false || strpos($lightingLower, 'dramatic') !== false) {
            $preset['contrast'] = 1.3;
        }
        if (strpos($lightingLower, 'soft') !== false || strpos($lightingLower, 'natural') !== false) {
            $preset['contrast'] = 0.95;
        }

        return [
            'preset' => $mode,
            'contrast' => $preset['contrast'],
            'saturation' => $preset['saturation'],
            'shadows' => $preset['shadows'],
            'highlights' => $preset['highlights'],
            'lut' => $preset['lut'],
            'description' => $preset['description'],
            'customPalette' => $colorPalette,
            'lighting' => $lighting,
        ];
    }

    /**
     * Build audio configuration from pacing and mood.
     */
    protected function buildAudioConfig(array $bible): array
    {
        $pacing = $bible['pacing'] ?? [];
        $musicMood = $pacing['musicMood'] ?? '';
        $tone = $bible['tone'] ?? '';
        $genre = $bible['genre'] ?? '';

        // Determine music style from Bible elements
        $musicStyle = $this->determineMusicStyle($musicMood, $tone, $genre);

        // Get engagement hooks for audio emphasis points
        $hooks = $pacing['engagementHooks'] ?? [];
        $emphasisPoints = [];
        foreach ($hooks as $hook) {
            if (!empty($hook['position'])) {
                $emphasisPoints[] = [
                    'time' => $hook['position'],
                    'type' => $hook['type'] ?? 'transition',
                    'audioAction' => $this->getAudioActionForHook($hook['type'] ?? ''),
                ];
            }
        }

        return [
            'musicMood' => $musicMood,
            'musicStyle' => $musicStyle,
            'suggestedGenres' => $this->suggestMusicGenres($tone, $genre),
            'emphasisPoints' => $emphasisPoints,
            'volumeProfile' => $this->getVolumeProfile($pacing['overallPace'] ?? 'medium'),
        ];
    }

    /**
     * Determine music style from Bible elements.
     */
    protected function determineMusicStyle(string $musicMood, string $tone, string $genre): string
    {
        $combined = strtolower($musicMood . ' ' . $tone . ' ' . $genre);

        if (strpos($combined, 'epic') !== false || strpos($combined, 'dramatic') !== false) {
            return 'orchestral-dramatic';
        }
        if (strpos($combined, 'upbeat') !== false || strpos($combined, 'energetic') !== false) {
            return 'electronic-upbeat';
        }
        if (strpos($combined, 'calm') !== false || strpos($combined, 'peaceful') !== false) {
            return 'ambient-calm';
        }
        if (strpos($combined, 'suspense') !== false || strpos($combined, 'thriller') !== false) {
            return 'tension-building';
        }
        if (strpos($combined, 'corporate') !== false || strpos($combined, 'professional') !== false) {
            return 'corporate-motivational';
        }
        if (strpos($combined, 'documentary') !== false) {
            return 'documentary-ambient';
        }

        return 'neutral-background';
    }

    /**
     * Suggest music genres based on tone and genre.
     */
    protected function suggestMusicGenres(string $tone, string $genre): array
    {
        $suggestions = [];
        $combined = strtolower($tone . ' ' . $genre);

        if (strpos($combined, 'dramatic') !== false) {
            $suggestions[] = 'cinematic';
            $suggestions[] = 'orchestral';
        }
        if (strpos($combined, 'documentary') !== false) {
            $suggestions[] = 'ambient';
            $suggestions[] = 'piano';
        }
        if (strpos($combined, 'educational') !== false) {
            $suggestions[] = 'corporate';
            $suggestions[] = 'acoustic';
        }
        if (strpos($combined, 'inspirational') !== false) {
            $suggestions[] = 'uplifting';
            $suggestions[] = 'motivational';
        }
        if (strpos($combined, 'comedic') !== false) {
            $suggestions[] = 'quirky';
            $suggestions[] = 'playful';
        }

        return array_unique($suggestions) ?: ['neutral', 'background'];
    }

    /**
     * Get audio action for engagement hook type.
     */
    protected function getAudioActionForHook(string $hookType): string
    {
        return match (strtolower($hookType)) {
            'question' => 'subtle-rise',
            'reveal' => 'impact-hit',
            'cliffhanger' => 'tension-build',
            'transition' => 'crossfade',
            default => 'none',
        };
    }

    /**
     * Get volume profile based on pacing.
     */
    protected function getVolumeProfile(string $pace): array
    {
        return match (strtolower($pace)) {
            'fast' => ['music' => 0.3, 'voice' => 0.9, 'sfx' => 0.4],
            'slow' => ['music' => 0.5, 'voice' => 0.8, 'sfx' => 0.3],
            default => ['music' => 0.4, 'voice' => 0.85, 'sfx' => 0.35],
        };
    }

    /**
     * Build subtitle configuration.
     */
    protected function buildSubtitleConfig(array $bible): array
    {
        $visualStyle = $bible['visualStyle'] ?? [];
        $colorPalette = $visualStyle['colorPalette'] ?? [];
        $mode = strtolower($visualStyle['mode'] ?? 'cinematic');

        // Determine subtitle style based on visual mode
        $subtitleStyle = match ($mode) {
            'cinematic' => [
                'font' => 'Inter',
                'size' => 'medium',
                'color' => '#ffffff',
                'background' => 'rgba(0,0,0,0.6)',
                'position' => 'bottom',
                'animation' => 'fade',
            ],
            'documentary' => [
                'font' => 'Roboto',
                'size' => 'medium',
                'color' => '#ffffff',
                'background' => 'rgba(0,0,0,0.7)',
                'position' => 'bottom',
                'animation' => 'none',
            ],
            'animated' => [
                'font' => 'Comic Neue',
                'size' => 'large',
                'color' => '#ffffff',
                'background' => 'rgba(0,0,0,0.5)',
                'position' => 'bottom',
                'animation' => 'bounce',
            ],
            default => [
                'font' => 'Open Sans',
                'size' => 'medium',
                'color' => '#ffffff',
                'background' => 'rgba(0,0,0,0.65)',
                'position' => 'bottom',
                'animation' => 'fade',
            ],
        };

        // Apply accent color from palette if available
        if (!empty($colorPalette[0])) {
            $subtitleStyle['accentColor'] = $colorPalette[0];
        }

        return $subtitleStyle;
    }

    /**
     * Generate scene-by-scene export configuration.
     */
    public function buildSceneExportConfigs(WizardProject $project): array
    {
        $bible = $project->story_bible ?? [];
        $scenes = $project->script['scenes'] ?? [];
        $baseConfig = $this->buildExportConfig($project);

        $sceneConfigs = [];

        foreach ($scenes as $index => $scene) {
            $sceneConfig = [
                'sceneIndex' => $index,
                'sceneId' => $scene['id'] ?? "scene-{$index}",
                'title' => $scene['title'] ?? "Scene " . ($index + 1),
                'duration' => $scene['duration'] ?? 10,
            ];

            // Determine transition to next scene
            $isLastScene = $index === count($scenes) - 1;
            if (!$isLastScene) {
                $sceneConfig['transitionOut'] = $baseConfig['transitions']['default'];
                $sceneConfig['transitionDuration'] = $baseConfig['transitions']['duration'];
            } else {
                $sceneConfig['transitionOut'] = 'fade-to-black';
                $sceneConfig['transitionDuration'] = 1.0;
            }

            // Check for engagement hooks at this scene's timestamp
            $sceneStart = array_sum(array_column(array_slice($scenes, 0, $index), 'duration'));
            $sceneEnd = $sceneStart + ($scene['duration'] ?? 10);

            foreach ($baseConfig['audio']['emphasisPoints'] ?? [] as $hook) {
                if ($hook['time'] >= $sceneStart && $hook['time'] < $sceneEnd) {
                    $sceneConfig['audioEmphasis'] = $hook;
                    break;
                }
            }

            // Character voice for this scene
            $narration = $scene['narration'] ?? '';
            foreach ($baseConfig['voiceMapping'] as $charName => $voiceConfig) {
                if (stripos($narration, $charName) !== false) {
                    $sceneConfig['speakingCharacter'] = $charName;
                    $sceneConfig['voiceConfig'] = $voiceConfig;
                    break;
                }
            }

            $sceneConfigs[] = $sceneConfig;
        }

        return [
            'baseConfig' => $baseConfig,
            'scenes' => $sceneConfigs,
            'totalDuration' => array_sum(array_column($scenes, 'duration')),
            'sceneCount' => count($scenes),
        ];
    }
}
