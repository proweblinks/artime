<?php

namespace Modules\AppVideoWizard\Services;

use Modules\AppVideoWizard\Models\VwGenerationLog;
use Illuminate\Support\Facades\Log;

class PromptService
{
    /**
     * Log a generation event.
     */
    public function logGeneration(
        string $promptSlug,
        array $inputData,
        array $outputData,
        string $status = 'success',
        ?string $errorMessage = null,
        ?int $tokensUsed = null,
        ?int $durationMs = null,
        ?int $projectId = null
    ): VwGenerationLog {
        if ($status === 'success') {
            return VwGenerationLog::logSuccess(
                $promptSlug,
                $inputData,
                $outputData,
                $tokensUsed,
                $durationMs,
                $projectId,
                auth()->id(),
                session('current_team_id'),
                null
            );
        }

        return VwGenerationLog::logFailure(
            $promptSlug,
            $inputData,
            $errorMessage ?? 'Unknown error',
            $durationMs,
            $projectId,
            auth()->id(),
            session('current_team_id'),
            null
        );
    }

    /**
     * Get tone guide text.
     */
    public function getToneGuide(string $tone): string
    {
        $guides = [
            'engaging' => 'conversational, energetic, keeps viewers hooked with dynamic pacing',
            'professional' => 'polished, authoritative, business-appropriate with credibility',
            'casual' => 'friendly, relaxed, like talking to a friend',
            'inspirational' => 'uplifting, motivational, emotionally resonant',
            'educational' => 'clear, structured, informative with examples',
        ];

        return $guides[$tone] ?? $guides['engaging'];
    }

    /**
     * Get content depth guide text.
     */
    public function getDepthGuide(string $depth): string
    {
        $guides = [
            'quick' => 'Focus on key points only, minimal detail',
            'standard' => 'Balanced coverage with some examples',
            'detailed' => 'Include examples, statistics, and supporting details',
            'deep' => 'Comprehensive analysis with multiple perspectives',
        ];

        return $guides[$depth] ?? $guides['detailed'];
    }
}
