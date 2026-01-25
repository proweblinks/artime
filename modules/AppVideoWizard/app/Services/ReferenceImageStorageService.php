<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * File-based storage service for reference images.
 *
 * Purpose: Move base64 image data out of Livewire component state to reduce
 * serialization payload. Images are stored as files and loaded lazily only
 * when needed for API calls.
 *
 * Storage pattern: storage/app/video-wizard/reference-images/{projectId}/{type}-{identifier}-{random}.{ext}
 *
 * @package Modules\AppVideoWizard\Services
 */
class ReferenceImageStorageService
{
    /**
     * Storage disk to use for reference images.
     * Uses 'local' disk by default (storage/app/).
     */
    protected string $disk = 'local';

    /**
     * Base path for reference images within the disk.
     */
    protected string $basePath = 'video-wizard/reference-images';

    /**
     * Store base64 image data to file.
     *
     * @param int $projectId The wizard project ID
     * @param string $type Type of reference: 'character', 'location', 'style'
     * @param string|int $identifier Character/location index or 'main' for style
     * @param string $base64Data The base64 encoded image data
     * @param string|null $mimeType The MIME type (e.g., 'image/png')
     * @return string The storage key (path) for retrieval
     */
    public function storeBase64(
        int $projectId,
        string $type,
        string|int $identifier,
        string $base64Data,
        ?string $mimeType = null
    ): string {
        // Generate unique filename
        $extension = $this->getExtensionFromMimeType($mimeType);
        $filename = "{$type}-{$identifier}-" . Str::random(8) . ".{$extension}";
        $path = "{$this->basePath}/{$projectId}/{$filename}";

        // Decode and store
        $binaryData = base64_decode($base64Data);

        if ($binaryData === false) {
            Log::warning('ReferenceImageStorage: Failed to decode base64 data', [
                'projectId' => $projectId,
                'type' => $type,
                'identifier' => $identifier,
            ]);
            throw new \InvalidArgumentException('Invalid base64 data provided');
        }

        Storage::disk($this->disk)->put($path, $binaryData);

        Log::debug('ReferenceImageStorage: Stored image', [
            'path' => $path,
            'size' => strlen($binaryData),
            'type' => $type,
            'projectId' => $projectId,
        ]);

        return $path;
    }

    /**
     * Load base64 image data from file.
     *
     * @param string $storageKey The path returned from storeBase64
     * @return string|null Base64 encoded data, or null if not found
     */
    public function loadBase64(string $storageKey): ?string
    {
        if (empty($storageKey)) {
            return null;
        }

        if (!Storage::disk($this->disk)->exists($storageKey)) {
            Log::debug('ReferenceImageStorage: File not found', ['path' => $storageKey]);
            return null;
        }

        $binaryData = Storage::disk($this->disk)->get($storageKey);

        if ($binaryData === null) {
            return null;
        }

        return base64_encode($binaryData);
    }

    /**
     * Delete stored image.
     *
     * @param string $storageKey The path to delete
     * @return bool True if deleted, false otherwise
     */
    public function deleteBase64(string $storageKey): bool
    {
        if (empty($storageKey)) {
            return false;
        }

        if (!Storage::disk($this->disk)->exists($storageKey)) {
            return false;
        }

        $deleted = Storage::disk($this->disk)->delete($storageKey);

        if ($deleted) {
            Log::debug('ReferenceImageStorage: Deleted image', ['path' => $storageKey]);
        }

        return $deleted;
    }

    /**
     * Delete all reference images for a project.
     *
     * @param int $projectId The project ID
     * @return bool True if directory deleted
     */
    public function deleteProjectImages(int $projectId): bool
    {
        $path = "{$this->basePath}/{$projectId}";

        if (!Storage::disk($this->disk)->exists($path)) {
            return false;
        }

        $deleted = Storage::disk($this->disk)->deleteDirectory($path);

        if ($deleted) {
            Log::info('ReferenceImageStorage: Deleted project images', [
                'projectId' => $projectId,
                'path' => $path,
            ]);
        }

        return $deleted;
    }

    /**
     * Check if a storage key exists.
     *
     * @param string $storageKey The path to check
     * @return bool
     */
    public function exists(string $storageKey): bool
    {
        if (empty($storageKey)) {
            return false;
        }

        return Storage::disk($this->disk)->exists($storageKey);
    }

    /**
     * Get file extension from MIME type.
     *
     * @param string|null $mimeType The MIME type
     * @return string File extension
     */
    protected function getExtensionFromMimeType(?string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'png',
        };
    }

    /**
     * Get the full storage path for debugging.
     *
     * @param string $storageKey The storage key
     * @return string The full disk path
     */
    public function getFullPath(string $storageKey): string
    {
        return Storage::disk($this->disk)->path($storageKey);
    }
}
