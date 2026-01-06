<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class RunPodService
{
    protected Client $client;
    protected string $apiKey;

    public function __construct()
    {
        // Always get fresh API key to handle updates
        $this->apiKey = (string) get_option("runpod_api_key", "");
        $this->initClient();
    }

    /**
     * Initialize HTTP client with current API key.
     */
    protected function initClient(): void
    {
        $this->client = new Client([
            'base_uri' => 'https://api.runpod.ai/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Refresh API key from settings (useful if changed after service instantiation).
     */
    public function refreshApiKey(): self
    {
        $this->apiKey = (string) get_option("runpod_api_key", "");
        $this->initClient();
        return $this;
    }

    /**
     * Check if RunPod is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Run a synchronous job on a serverless endpoint.
     */
    public function runSync(string $endpointId, array $input, int $timeout = 300): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('RunPod API not configured');
        }

        try {
            $response = $this->client->request('POST', "{$endpointId}/runsync", [
                'json' => ['input' => $input],
                'timeout' => $timeout,
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['error'])) {
                return $this->errorResponse($body['error']);
            }

            return [
                'success' => true,
                'id' => $body['id'] ?? null,
                'status' => $body['status'] ?? 'COMPLETED',
                'output' => $body['output'] ?? null,
                'executionTime' => $body['executionTime'] ?? null,
            ];

        } catch (ClientException $e) {
            Log::error("RunPod runsync failed: " . $e->getMessage());
            return $this->errorResponse($this->parseError($e));
        } catch (\Throwable $e) {
            Log::error("RunPod runsync error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Run an asynchronous job on a serverless endpoint.
     */
    public function runAsync(string $endpointId, array $input, ?string $webhook = null): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('RunPod API not configured');
        }

        try {
            $payload = ['input' => $input];
            if ($webhook) {
                $payload['webhook'] = $webhook;
            }

            $response = $this->client->request('POST', "{$endpointId}/run", [
                'json' => $payload,
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['error'])) {
                return $this->errorResponse($body['error']);
            }

            return [
                'success' => true,
                'id' => $body['id'] ?? null,
                'status' => $body['status'] ?? 'IN_QUEUE',
            ];

        } catch (ClientException $e) {
            Log::error("RunPod run failed: " . $e->getMessage());
            return $this->errorResponse($this->parseError($e));
        } catch (\Throwable $e) {
            Log::error("RunPod run error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get the status of a job.
     */
    public function getStatus(string $endpointId, string $jobId): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('RunPod API not configured');
        }

        try {
            $response = $this->client->request('GET', "{$endpointId}/status/{$jobId}", [
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'id' => $body['id'] ?? $jobId,
                'status' => $body['status'] ?? 'UNKNOWN',
                'output' => $body['output'] ?? null,
                'error' => $body['error'] ?? null,
                'executionTime' => $body['executionTime'] ?? null,
                'delayTime' => $body['delayTime'] ?? null,
            ];

        } catch (ClientException $e) {
            Log::error("RunPod status check failed: " . $e->getMessage());
            return $this->errorResponse($this->parseError($e));
        } catch (\Throwable $e) {
            Log::error("RunPod status error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Cancel a job.
     */
    public function cancel(string $endpointId, string $jobId): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('RunPod API not configured');
        }

        try {
            $response = $this->client->request('POST', "{$endpointId}/cancel/{$jobId}", [
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'id' => $body['id'] ?? $jobId,
                'status' => $body['status'] ?? 'CANCELLED',
            ];

        } catch (ClientException $e) {
            Log::error("RunPod cancel failed: " . $e->getMessage());
            return $this->errorResponse($this->parseError($e));
        } catch (\Throwable $e) {
            Log::error("RunPod cancel error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Purge the queue for an endpoint.
     */
    public function purgeQueue(string $endpointId): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('RunPod API not configured');
        }

        try {
            $response = $this->client->request('POST', "{$endpointId}/purge-queue", [
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'removed' => $body['removed'] ?? 0,
            ];

        } catch (ClientException $e) {
            Log::error("RunPod purge queue failed: " . $e->getMessage());
            return $this->errorResponse($this->parseError($e));
        } catch (\Throwable $e) {
            Log::error("RunPod purge error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get endpoint health status.
     */
    public function getHealth(string $endpointId): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('RunPod API not configured');
        }

        try {
            $response = $this->client->request('GET', "{$endpointId}/health", [
                'timeout' => 30,
            ]);

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'workers' => $body['workers'] ?? [],
                'jobs' => [
                    'completed' => $body['jobs']['completed'] ?? 0,
                    'failed' => $body['jobs']['failed'] ?? 0,
                    'inProgress' => $body['jobs']['inProgress'] ?? 0,
                    'inQueue' => $body['jobs']['inQueue'] ?? 0,
                    'retried' => $body['jobs']['retried'] ?? 0,
                ],
            ];

        } catch (ClientException $e) {
            Log::error("RunPod health check failed: " . $e->getMessage());
            return $this->errorResponse($this->parseError($e));
        } catch (\Throwable $e) {
            Log::error("RunPod health error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Poll for job completion with timeout.
     */
    public function waitForCompletion(string $endpointId, string $jobId, int $maxWaitSeconds = 300, int $pollInterval = 2): array
    {
        $startTime = time();

        while ((time() - $startTime) < $maxWaitSeconds) {
            $status = $this->getStatus($endpointId, $jobId);

            if (!$status['success']) {
                return $status;
            }

            $currentStatus = $status['status'];

            if ($currentStatus === 'COMPLETED') {
                return $status;
            }

            if (in_array($currentStatus, ['FAILED', 'CANCELLED', 'TIMED_OUT'])) {
                return [
                    'success' => false,
                    'status' => $currentStatus,
                    'error' => $status['error'] ?? "Job {$currentStatus}",
                    'output' => $status['output'] ?? null,
                ];
            }

            sleep($pollInterval);
        }

        return [
            'success' => false,
            'status' => 'TIMEOUT',
            'error' => "Job did not complete within {$maxWaitSeconds} seconds",
        ];
    }

    /**
     * Parse error from ClientException.
     */
    protected function parseError(ClientException $e): string
    {
        $response = $e->getResponse();
        if ($response) {
            $body = json_decode($response->getBody(), true);
            if (isset($body['error'])) {
                return is_string($body['error']) ? $body['error'] : json_encode($body['error']);
            }
        }
        return $e->getMessage();
    }

    /**
     * Error response format.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }
}
