<?php

namespace Modules\AppVideoWizard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WizardProject extends Model
{
    protected $table = 'wizard_projects';

    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'status',
        'current_step',
        'max_reached_step',
        'platform',
        'aspect_ratio',
        'target_duration',
        'format',
        'production_type',
        'production_subtype',
        'concept',
        'character_intelligence',
        'content_config',
        'story_bible',
        'script',
        'script_generation_config',
        'storyboard',
        'animation',
        'assembly',
        'export_config',
        'output_url',
        'thumbnail_url',
    ];

    protected $casts = [
        'concept' => 'array',
        'character_intelligence' => 'array',
        'content_config' => 'array',
        'story_bible' => 'array',
        'script' => 'array',
        'script_generation_config' => 'array',
        'storyboard' => 'array',
        'animation' => 'array',
        'assembly' => 'array',
        'export_config' => 'array',
        'current_step' => 'integer',
        'max_reached_step' => 'integer',
        'target_duration' => 'integer',
    ];

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that owns the project.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Modules\AppTeams\Models\Team::class);
    }

    /**
     * Get the processing jobs for the project.
     */
    public function processingJobs(): HasMany
    {
        return $this->hasMany(WizardProcessingJob::class, 'project_id');
    }

    /**
     * Get the assets for the project.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(WizardAsset::class, 'project_id');
    }

    /**
     * Get platform configuration.
     */
    public function getPlatformConfig(): ?array
    {
        if (!$this->platform) {
            return null;
        }
        return config("appvideowizard.platforms.{$this->platform}");
    }

    /**
     * Get format configuration.
     */
    public function getFormatConfig(): ?array
    {
        if (!$this->format) {
            return null;
        }
        return config("appvideowizard.formats.{$this->format}");
    }

    /**
     * Get production type configuration.
     */
    public function getProductionTypeConfig(): ?array
    {
        if (!$this->production_type) {
            return null;
        }
        return config("appvideowizard.production_types.{$this->production_type}");
    }

    // =========================================================================
    // SCENE RELATIONSHIPS (Normalized Data - Phase 21)
    // =========================================================================

    /**
     * Get the scenes for this project (normalized data).
     * Ordered by position within the project.
     */
    public function scenes(): HasMany
    {
        return $this->hasMany(WizardScene::class, 'project_id')
            ->orderBy('order');
    }

    /**
     * Check if project uses normalized data (scenes table) vs JSON arrays.
     * Used for backward compatibility during transition.
     */
    public function usesNormalizedData(): bool
    {
        return $this->scenes()->exists();
    }

    // =========================================================================
    // SCENE HELPERS (Support both JSON and normalized data)
    // =========================================================================

    /**
     * Get scenes from script (JSON data - legacy).
     */
    public function getScenes(): array
    {
        return $this->script['scenes'] ?? [];
    }

    /**
     * Get scene count (checks normalized first, falls back to JSON).
     */
    public function getSceneCount(): int
    {
        // Try normalized data first
        if ($this->usesNormalizedData()) {
            return $this->scenes()->count();
        }

        // Fallback to JSON
        return count($this->getScenes());
    }

    /**
     * Check if project has completed script.
     */
    public function hasScript(): bool
    {
        return !empty($this->script['scenes']);
    }

    /**
     * Check if project has storyboard images.
     */
    public function hasStoryboard(): bool
    {
        $storyboard = $this->storyboard ?? [];
        foreach ($storyboard['scenes'] ?? [] as $scene) {
            if (!empty($scene['imageUrl'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if project is ready for export.
     */
    public function isReadyForExport(): bool
    {
        return $this->hasScript() && $this->hasStoryboard();
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // =========================================================================
    // STORY BIBLE HELPERS
    // =========================================================================

    /**
     * Check if project has a generated Story Bible.
     */
    public function hasStoryBible(): bool
    {
        $bible = $this->story_bible ?? [];
        return !empty($bible['enabled']) && ($bible['status'] ?? '') === 'ready';
    }

    /**
     * Check if Story Bible is currently generating.
     */
    public function isStoryBibleGenerating(): bool
    {
        $bible = $this->story_bible ?? [];
        return ($bible['status'] ?? '') === 'generating';
    }

    /**
     * Get Story Bible characters.
     */
    public function getStoryBibleCharacters(): array
    {
        return $this->story_bible['characters'] ?? [];
    }

    /**
     * Get Story Bible locations.
     */
    public function getStoryBibleLocations(): array
    {
        return $this->story_bible['locations'] ?? [];
    }

    /**
     * Get Story Bible acts/structure.
     */
    public function getStoryBibleActs(): array
    {
        return $this->story_bible['acts'] ?? [];
    }

    /**
     * Get Story Bible visual style.
     */
    public function getStoryBibleVisualStyle(): array
    {
        return $this->story_bible['visualStyle'] ?? [];
    }

    /**
     * Get the complete Story Bible constraint string for script generation.
     * This compiles the Bible into a format that can be injected into prompts.
     */
    public function getStoryBibleConstraint(): string
    {
        if (!$this->hasStoryBible()) {
            return '';
        }

        $bible = $this->story_bible;
        $constraint = "=== STORY BIBLE (MANDATORY CONSTRAINTS) ===\n\n";

        // Title and Logline
        if (!empty($bible['title'])) {
            $constraint .= "TITLE: {$bible['title']}\n";
        }
        if (!empty($bible['logline'])) {
            $constraint .= "LOGLINE: {$bible['logline']}\n";
        }
        if (!empty($bible['theme'])) {
            $constraint .= "THEME: {$bible['theme']}\n";
        }
        if (!empty($bible['tone'])) {
            $constraint .= "TONE: {$bible['tone']}\n";
        }
        $constraint .= "\n";

        // Act Structure
        $acts = $bible['acts'] ?? [];
        if (!empty($acts)) {
            $constraint .= "=== THREE-ACT STRUCTURE ===\n";
            foreach ($acts as $act) {
                $actNum = $act['actNumber'] ?? '?';
                $actName = $act['name'] ?? 'Act';
                $actDesc = $act['description'] ?? '';
                $turning = $act['turningPoint'] ?? '';
                $percent = $act['percentage'] ?? 0;
                $constraint .= "ACT {$actNum} - {$actName} ({$percent}%): {$actDesc}\n";
                if ($turning) {
                    $constraint .= "  Turning Point: {$turning}\n";
                }
            }
            $constraint .= "\n";
        }

        // Characters
        $characters = $bible['characters'] ?? [];
        if (!empty($characters)) {
            $constraint .= "=== CHARACTER PROFILES (USE EXACT DESCRIPTIONS) ===\n";
            foreach ($characters as $char) {
                $name = $char['name'] ?? 'Unknown';
                $role = $char['role'] ?? 'supporting';
                $desc = $char['description'] ?? '';
                $arc = $char['arc'] ?? '';
                $constraint .= "• {$name} ({$role}): {$desc}\n";
                if ($arc) {
                    $constraint .= "  Arc: {$arc}\n";
                }
            }
            $constraint .= "\n";
        }

        // Locations
        $locations = $bible['locations'] ?? [];
        if (!empty($locations)) {
            $constraint .= "=== LOCATION INDEX (USE EXACT DESCRIPTIONS) ===\n";
            foreach ($locations as $loc) {
                $name = $loc['name'] ?? 'Unknown';
                $type = $loc['type'] ?? 'exterior';
                $desc = $loc['description'] ?? '';
                $time = $loc['timeOfDay'] ?? 'day';
                $constraint .= "• {$name} ({$type}, {$time}): {$desc}\n";
            }
            $constraint .= "\n";
        }

        // Visual Style
        $style = $bible['visualStyle'] ?? [];
        if (!empty($style)) {
            $constraint .= "=== VISUAL STYLE GUIDE ===\n";
            if (!empty($style['mode'])) {
                $constraint .= "Mode: {$style['mode']}\n";
            }
            if (!empty($style['colorPalette'])) {
                $constraint .= "Color Palette: {$style['colorPalette']}\n";
            }
            if (!empty($style['lighting'])) {
                $constraint .= "Lighting: {$style['lighting']}\n";
            }
            if (!empty($style['cameraLanguage'])) {
                $constraint .= "Camera Language: {$style['cameraLanguage']}\n";
            }
            if (!empty($style['references'])) {
                $constraint .= "References: {$style['references']}\n";
            }
            $constraint .= "\n";
        }

        // Pacing
        $pacing = $bible['pacing'] ?? [];
        if (!empty($pacing)) {
            $constraint .= "=== PACING & RHYTHM ===\n";
            if (!empty($pacing['overall'])) {
                $constraint .= "Overall Pacing: {$pacing['overall']}\n";
            }
            if (!empty($pacing['emotionalBeats'])) {
                $beats = implode(' → ', $pacing['emotionalBeats']);
                $constraint .= "Emotional Journey: {$beats}\n";
            }
        }

        $constraint .= "\n=== END STORY BIBLE ===\n";
        $constraint .= "CRITICAL: All scene descriptions, character references, and locations MUST match the Story Bible exactly.\n";

        return $constraint;
    }

    // =========================================================================
    // COMPLETE PROJECT DELETION (DB + Files)
    // =========================================================================

    /**
     * Delete project with all associated files on disk.
     * Cleans up: wizard-videos, wizard-audio, reference images, and asset files.
     */
    public function deleteWithFiles(): void
    {
        $projectId = $this->id;
        $userId = $this->user_id;

        // 1. Delete WizardAsset files (iterate models so deleting event fires)
        foreach ($this->assets as $asset) {
            $asset->delete();
        }

        // 2. Delete wizard-videos directory (scene/shot videos, pipeline logs, diagnostics)
        //    Pattern: public/wizard-videos/{userId}/{projectId}/
        $videoDir = public_path("wizard-videos/{$userId}/{$projectId}");
        if (File::isDirectory($videoDir)) {
            File::deleteDirectory($videoDir);
        }

        // 3. Delete wizard-audio directory (TTS audio, timeline-synced audio)
        //    Pattern: public/wizard-audio/{projectId}/
        $audioDir = public_path("wizard-audio/{$projectId}");
        if (File::isDirectory($audioDir)) {
            File::deleteDirectory($audioDir);
        }

        // 4. Delete reference images (character/location/style reference images)
        //    Pattern: storage/app/video-wizard/reference-images/{projectId}/
        $refPath = "video-wizard/reference-images/{$projectId}";
        if (Storage::disk('local')->exists($refPath)) {
            Storage::disk('local')->deleteDirectory($refPath);
        }

        // 5. Delete processing jobs
        $this->processingJobs()->delete();

        // 6. Delete the project (cascade handles scenes, shots, speech_segments)
        $this->delete();

        Log::info("WizardProject::deleteWithFiles - Cleaned up project {$projectId}");
    }
}
