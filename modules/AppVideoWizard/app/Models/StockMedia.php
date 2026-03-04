<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMedia extends Model
{
    protected $table = 'wizard_stock_media';

    protected static function booted(): void
    {
        static::deleting(function (StockMedia $media) {
            // Always clean up files from disk when model is deleted
            $filePath = public_path('stock-media/' . $media->path);
            if ($media->path && file_exists($filePath)) {
                @unlink($filePath);
            }

            if ($media->thumbnail_path) {
                $thumbPath = public_path('stock-media/' . $media->thumbnail_path);
                if (file_exists($thumbPath)) {
                    @unlink($thumbPath);
                }
            }
        });
    }

    protected $fillable = [
        'filename',
        'path',
        'disk_path',
        'checksum',
        'type',
        'mime_type',
        'file_size',
        'width',
        'height',
        'duration',
        'fps',
        'category',
        'title',
        'tags',
        'description',
        'thumbnail_path',
        'orientation',
        'is_active',
        'report_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'float',
        'fps' => 'float',
        'is_active' => 'boolean',
        'report_count' => 'integer',
    ];

    /**
     * Get the public URL for this stock media file.
     */
    public function getPublicUrl(): string
    {
        return url('/public/stock-media/' . $this->path);
    }

    /**
     * Get the thumbnail URL.
     * Videos use generated thumbnails; images serve themselves.
     */
    public function getThumbnailUrl(): string
    {
        if ($this->type === 'video' && $this->thumbnail_path) {
            return url('/public/stock-media/' . $this->thumbnail_path);
        }

        return $this->getPublicUrl();
    }

    /**
     * Convert to candidate array format compatible with Pexels/Pixabay results.
     */
    public function toCandidate(float $score = 3.0): array
    {
        $candidate = [
            'url' => $this->getPublicUrl(),
            'thumbnail' => $this->getThumbnailUrl(),
            'title' => $this->title,
            'width' => $this->width,
            'height' => $this->height,
            'source' => 'artime_stock',
            'stock_id' => $this->id,
            'score' => $score,
        ];

        if ($this->type === 'video') {
            $candidate['type'] = 'video';
            $candidate['duration'] = $this->duration ?? 0;
        }

        return $candidate;
    }

    /**
     * Scope: only active media.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter by type (image/video).
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: filter by category.
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: filter by orientation.
     */
    public function scopeOrientation(Builder $query, string $orientation): Builder
    {
        return $query->where('orientation', $orientation);
    }

    /**
     * Reports for this media item.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(StockMediaReport::class);
    }

    /**
     * Scope: only reported media (report_count > 0).
     */
    public function scopeReported(Builder $query): Builder
    {
        return $query->where('report_count', '>', 0);
    }

    /**
     * Scope: keyword search using FULLTEXT (MySQL) or LIKE fallback (SQLite).
     * Uses OR logic so matching ANY word returns results, ranked by relevance.
     */
    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return $query;
        }

        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $ftQuery = $this->buildFulltextQuery($keyword);
            // Use MATCH for both filtering (WHERE) and ranking (ORDER BY relevance)
            return $query->whereRaw(
                'MATCH(title, tags, description) AGAINST(? IN BOOLEAN MODE)',
                [$ftQuery]
            )->orderByRaw(
                'MATCH(title, tags, description) AGAINST(? IN BOOLEAN MODE) DESC',
                [$ftQuery]
            );
        }

        // SQLite / fallback: LIKE-based search (OR across words, OR across columns)
        $words = preg_split('/\s+/', $keyword);
        return $query->where(function (Builder $q) use ($words) {
            foreach ($words as $word) {
                $word = trim($word);
                if (strlen($word) < 2) continue;
                $like = '%' . $word . '%';
                $q->orWhere('title', 'LIKE', $like)
                  ->orWhere('tags', 'LIKE', $like)
                  ->orWhere('description', 'LIKE', $like);
            }
        });
    }

    /**
     * Build a MySQL FULLTEXT boolean mode query string.
     * Words are optional (OR logic), ranked by how many match.
     * Only uses wildcard (*) for words >= 5 chars to prevent false positives
     * (e.g., "fun*" matching "funny-cat" when searching for travel content).
     */
    protected function buildFulltextQuery(string $keyword): string
    {
        $words = preg_split('/\s+/', $keyword);
        $parts = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) < 2) continue;
            // Only wildcard longer words — short words cause false prefix matches
            $parts[] = strlen($word) >= 5 ? $word . '*' : $word;
        }
        return implode(' ', $parts);
    }
}
