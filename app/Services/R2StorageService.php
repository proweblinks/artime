<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class R2StorageService
{
    protected ?S3Client $client = null;
    protected string $bucket;
    protected string $accountId;
    protected bool $isConfigured = false;

    public function __construct()
    {
        $this->accountId = (string) get_option("r2_account_id", "");
        $accessKeyId = (string) get_option("r2_access_key_id", "");
        $accessKeySecret = (string) get_option("r2_access_key_secret", "");
        $this->bucket = (string) get_option("r2_bucket_name", "");

        if (!empty($this->accountId) && !empty($accessKeyId) && !empty($accessKeySecret) && !empty($this->bucket)) {
            try {
                $this->client = new S3Client([
                    'region' => 'auto',
                    'version' => 'latest',
                    'endpoint' => "https://{$this->accountId}.r2.cloudflarestorage.com",
                    'credentials' => [
                        'key' => $accessKeyId,
                        'secret' => $accessKeySecret,
                    ],
                    'use_path_style_endpoint' => true,
                ]);
                $this->isConfigured = true;
            } catch (\Exception $e) {
                Log::error("R2 Storage initialization failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Check if R2 is configured.
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured && $this->client !== null;
    }

    /**
     * Upload a file to R2.
     */
    public function upload(string $path, $content, ?string $contentType = null, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('R2 Storage not configured');
        }

        try {
            $params = [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $content,
                'ACL' => $options['acl'] ?? 'public-read',
            ];

            if ($contentType) {
                $params['ContentType'] = $contentType;
            }

            // Add cache control if specified
            if (!empty($options['cache_control'])) {
                $params['CacheControl'] = $options['cache_control'];
            }

            // Add metadata if specified
            if (!empty($options['metadata'])) {
                $params['Metadata'] = $options['metadata'];
            }

            $result = $this->client->putObject($params);

            return [
                'success' => true,
                'path' => $path,
                'url' => $this->getPublicUrl($path),
                'etag' => $result['ETag'] ?? null,
            ];

        } catch (AwsException $e) {
            Log::error("R2 upload failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Upload a file from local path.
     */
    public function uploadFile(string $localPath, string $remotePath, ?string $contentType = null, array $options = []): array
    {
        if (!file_exists($localPath)) {
            return $this->errorResponse("Local file not found: {$localPath}");
        }

        $content = fopen($localPath, 'rb');
        $contentType = $contentType ?? mime_content_type($localPath);

        $result = $this->upload($remotePath, $content, $contentType, $options);

        if (is_resource($content)) {
            fclose($content);
        }

        return $result;
    }

    /**
     * Download a file from R2.
     */
    public function download(string $path): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('R2 Storage not configured');
        }

        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return [
                'success' => true,
                'content' => (string) $result['Body'],
                'content_type' => $result['ContentType'] ?? null,
                'content_length' => $result['ContentLength'] ?? null,
                'etag' => $result['ETag'] ?? null,
            ];

        } catch (AwsException $e) {
            Log::error("R2 download failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Delete a file from R2.
     */
    public function delete(string $path): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('R2 Storage not configured');
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return [
                'success' => true,
                'path' => $path,
            ];

        } catch (AwsException $e) {
            Log::error("R2 delete failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Delete multiple files from R2.
     */
    public function deleteMultiple(array $paths): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('R2 Storage not configured');
        }

        try {
            $objects = array_map(function ($path) {
                return ['Key' => $path];
            }, $paths);

            $result = $this->client->deleteObjects([
                'Bucket' => $this->bucket,
                'Delete' => [
                    'Objects' => $objects,
                ],
            ]);

            return [
                'success' => true,
                'deleted' => $result['Deleted'] ?? [],
                'errors' => $result['Errors'] ?? [],
            ];

        } catch (AwsException $e) {
            Log::error("R2 bulk delete failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    /**
     * Get file info.
     */
    public function getInfo(string $path): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('R2 Storage not configured');
        }

        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return [
                'success' => true,
                'path' => $path,
                'content_type' => $result['ContentType'] ?? null,
                'content_length' => $result['ContentLength'] ?? null,
                'last_modified' => $result['LastModified'] ?? null,
                'etag' => $result['ETag'] ?? null,
                'metadata' => $result['Metadata'] ?? [],
            ];

        } catch (AwsException $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get a signed URL for temporary access.
     */
    public function getSignedUrl(string $path, int $expiry = 3600): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $cmd = $this->client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            $request = $this->client->createPresignedRequest($cmd, "+{$expiry} seconds");
            return (string) $request->getUri();

        } catch (AwsException $e) {
            Log::error("R2 signed URL generation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get public URL for a file.
     * Note: R2 requires a custom domain or workers for public access.
     */
    public function getPublicUrl(string $path): string
    {
        $publicDomain = get_option('r2_public_domain', '');

        if (!empty($publicDomain)) {
            return rtrim($publicDomain, '/') . '/' . ltrim($path, '/');
        }

        // Fallback to R2 dev URL (requires public bucket access)
        return "https://{$this->bucket}.{$this->accountId}.r2.dev/{$path}";
    }

    /**
     * List files in a directory.
     */
    public function listFiles(string $prefix = '', int $maxKeys = 1000): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('R2 Storage not configured');
        }

        try {
            $result = $this->client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
                'MaxKeys' => $maxKeys,
            ]);

            $files = [];
            foreach ($result['Contents'] ?? [] as $object) {
                $files[] = [
                    'key' => $object['Key'],
                    'size' => $object['Size'],
                    'last_modified' => $object['LastModified'],
                    'etag' => $object['ETag'],
                ];
            }

            return [
                'success' => true,
                'files' => $files,
                'truncated' => $result['IsTruncated'] ?? false,
                'next_continuation_token' => $result['NextContinuationToken'] ?? null,
            ];

        } catch (AwsException $e) {
            Log::error("R2 list files failed: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
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
