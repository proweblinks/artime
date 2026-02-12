<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\RunPodService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Http\Controllers\AppVideoWizardController;

class KokoroTtsService
{
    protected RunPodService $runPodService;
    protected ?string $endpointId = null;

    /**
     * Available Kokoro TTS speakers/voices.
     * Pattern: {language}_{gender}_{name}
     * - af = Afrikaans
     * - am = American
     * - bf = British Female
     * - bm = British Male
     */
    protected array $voices = [
        // American voices
        'am_adam' => ['name' => 'Adam', 'gender' => 'male', 'accent' => 'American', 'style' => 'professional'],
        'am_michael' => ['name' => 'Michael', 'gender' => 'male', 'accent' => 'American', 'style' => 'natural'],
        'af_bella' => ['name' => 'Bella', 'gender' => 'female', 'accent' => 'American', 'style' => 'warm'],
        'af_nicole' => ['name' => 'Nicole', 'gender' => 'female', 'accent' => 'American', 'style' => 'friendly'],
        'af_sarah' => ['name' => 'Sarah', 'gender' => 'female', 'accent' => 'American', 'style' => 'professional'],
        'af_sky' => ['name' => 'Sky', 'gender' => 'female', 'accent' => 'American', 'style' => 'youthful'],

        // British voices
        'bm_george' => ['name' => 'George', 'gender' => 'male', 'accent' => 'British', 'style' => 'sophisticated'],
        'bm_lewis' => ['name' => 'Lewis', 'gender' => 'male', 'accent' => 'British', 'style' => 'warm'],
        'bf_emma' => ['name' => 'Emma', 'gender' => 'female', 'accent' => 'British', 'style' => 'elegant'],
        'bf_isabella' => ['name' => 'Isabella', 'gender' => 'female', 'accent' => 'British', 'style' => 'professional'],
    ];

    public function __construct()
    {
        $this->runPodService = app(RunPodService::class);
        $this->loadEndpoint();
    }

    /**
     * Load the Kokoro TTS endpoint from settings.
     */
    protected function loadEndpoint(): void
    {
        // Use get_option() to read from main admin settings (options table)
        $endpointUrl = get_option('runpod_kokoro_tts_endpoint', '');

        if (!empty($endpointUrl)) {
            // Extract endpoint ID from URL like "https://api.runpod.ai/v2/{id}" or just "{id}"
            if (preg_match('/\/v2\/([a-z0-9]+)/i', $endpointUrl, $matches)) {
                $this->endpointId = $matches[1];
            } else {
                // Assume it's just the endpoint ID
                $this->endpointId = trim($endpointUrl);
            }
        }
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->endpointId) && $this->runPodService->isConfigured();
    }

    /**
     * Get available voices.
     */
    public function getAvailableVoices(): array
    {
        return $this->voices;
    }

    /**
     * Get voice configuration by ID.
     */
    public function getVoice(string $voiceId): ?array
    {
        return $this->voices[$voiceId] ?? null;
    }

    /**
     * Get default voice by gender.
     */
    public function getDefaultVoiceByGender(string $gender): string
    {
        $gender = strtolower($gender);

        if (str_contains($gender, 'female') || str_contains($gender, 'woman')) {
            return 'af_nicole';
        } elseif (str_contains($gender, 'male') || str_contains($gender, 'man')) {
            return 'am_michael';
        }

        // Default to female voice
        return 'af_nicole';
    }

    /**
     * Map OpenAI voice to Kokoro voice for compatibility.
     */
    public function mapOpenAIVoice(string $openaiVoice): string
    {
        return match ($openaiVoice) {
            'alloy' => 'am_michael',    // Neutral -> American male
            'echo' => 'bm_lewis',       // Male warm -> British male warm
            'fable' => 'bm_george',     // Storytelling -> British sophisticated
            'onyx' => 'am_adam',        // Deep male -> American professional male
            'nova' => 'af_nicole',      // Female friendly -> American friendly female
            'shimmer' => 'af_sky',      // Female bright -> American youthful female
            default => 'af_nicole',
        };
    }

    /**
     * Generate speech using Kokoro TTS.
     *
     * @param string $text The text to speak
     * @param string $voice Voice ID (e.g., 'am_michael', 'af_nicole')
     * @param int $projectId Project ID for file storage
     * @param array $options Additional options
     * @return array Result with audioUrl or error
     */
    public function generateSpeech(string $text, string $voice, int $projectId, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Kokoro TTS is not configured. Please set the endpoint in admin settings.',
            ];
        }

        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'No text provided for speech generation',
            ];
        }

        // Validate voice or map from OpenAI voice
        if (!isset($this->voices[$voice])) {
            // Try to map from OpenAI voice
            $voice = $this->mapOpenAIVoice($voice);
        }

        Log::info('KokoroTTS: Starting speech generation', [
            'project_id' => $projectId,
            'voice' => $voice,
            'text_length' => strlen($text),
        ]);

        try {
            // Generate upload URL for the audio
            $filename = 'kokoro_' . time() . '_' . Str::random(8) . '.wav';
            $uploadData = AppVideoWizardController::generateAudioUploadUrl($projectId, $filename);

            Log::info('KokoroTTS: Generated upload URL', [
                'upload_url' => $uploadData['upload_url'],
                'audio_url' => $uploadData['audio_url'],
            ]);

            // Prepare input for Kokoro TTS
            $input = [
                'prompt' => $text,
                'speaker' => $voice,
                'audio_upload_url' => $uploadData['upload_url'],
            ];

            // Submit job to RunPod
            $result = $this->runPodService->runAsync($this->endpointId, $input);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to submit Kokoro TTS job');
            }

            $jobId = $result['id'];
            Log::info('KokoroTTS: Job submitted', ['job_id' => $jobId]);

            // Wait for completion (max 2 minutes for audio)
            $maxWait = $options['max_wait'] ?? 120;
            $pollInterval = $options['poll_interval'] ?? 2;

            $completionResult = $this->runPodService->waitForCompletion(
                $this->endpointId,
                $jobId,
                $maxWait,
                $pollInterval
            );

            if (!$completionResult['success']) {
                throw new \Exception($completionResult['error'] ?? 'Kokoro TTS job failed');
            }

            Log::info('KokoroTTS: Job completed', [
                'job_id' => $jobId,
                'status' => $completionResult['status'],
            ]);

            // Verify the audio file was uploaded
            $audioFilePath = public_path("wizard-audio/{$projectId}/{$filename}");
            if (!file_exists($audioFilePath)) {
                throw new \Exception('Audio file was not uploaded by the worker');
            }

            $fileSize = filesize($audioFilePath);
            Log::info('KokoroTTS: Audio file verified', [
                'path' => $audioFilePath,
                'size' => $fileSize,
            ]);

            // Estimate duration based on text (Kokoro tends to be natural speed)
            $wordCount = str_word_count($text);
            $estimatedDuration = ($wordCount / 150) * 60; // 150 words per minute

            return [
                'success' => true,
                'audioUrl' => $uploadData['audio_url'],
                'audioPath' => "wizard-audio/{$projectId}/{$filename}",
                'voice' => $voice,
                'voiceConfig' => $this->voices[$voice] ?? null,
                'duration' => $estimatedDuration,
                'fileSize' => $fileSize,
                'jobId' => $jobId,
                'provider' => 'kokoro',
            ];

        } catch (\Exception $e) {
            Log::error('KokoroTTS: Speech generation failed', [
                'error' => $e->getMessage(),
                'project_id' => $projectId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate speech asynchronously (returns job ID for polling).
     */
    public function generateSpeechAsync(string $text, string $voice, int $projectId, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Kokoro TTS is not configured',
            ];
        }

        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'No text provided',
            ];
        }

        // Validate or map voice
        if (!isset($this->voices[$voice])) {
            $voice = $this->mapOpenAIVoice($voice);
        }

        try {
            // Generate upload URL
            $filename = 'kokoro_' . time() . '_' . Str::random(8) . '.wav';
            $uploadData = AppVideoWizardController::generateAudioUploadUrl($projectId, $filename);

            // Submit job
            $input = [
                'prompt' => $text,
                'speaker' => $voice,
                'audio_upload_url' => $uploadData['upload_url'],
            ];

            $result = $this->runPodService->runAsync($this->endpointId, $input);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to submit job');
            }

            return [
                'success' => true,
                'jobId' => $result['id'],
                'endpointId' => $this->endpointId,
                'audioUrl' => $uploadData['audio_url'],
                'audioPath' => "wizard-audio/{$projectId}/{$filename}",
                'voice' => $voice,
                'status' => 'submitted',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check job status.
     */
    public function getJobStatus(string $jobId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Not configured'];
        }

        return $this->runPodService->getStatus($this->endpointId, $jobId);
    }
}
