<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class StockMedia extends Model
{
    protected $table = 'wizard_stock_media';

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
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'float',
        'fps' => 'float',
        'is_active' => 'boolean',
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
     * Words are optional (OR logic) with wildcard for prefix matching.
     * This returns results matching ANY word, ranked by how many match.
     */
    protected function buildFulltextQuery(string $keyword): string
    {
        $words = preg_split('/\s+/', $keyword);
        $parts = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) < 2) continue;
            // No + prefix = optional (OR). Wildcard * for prefix matching.
            $parts[] = $word . '*';
        }
        return implode(' ', $parts);
    }
}
