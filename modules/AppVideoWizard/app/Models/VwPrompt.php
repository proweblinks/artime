<?php

namespace Modules\AppVideoWizard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class VwPrompt extends Model
{
    protected $table = 'vw_prompts';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'prompt_template',
        'variables',
        'model',
        'temperature',
        'max_tokens',
        'is_active',
        'version',
    ];

    protected $casts = [
        'variables' => 'array',
        'temperature' => 'decimal:1',
        'max_tokens' => 'integer',
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    /**
     * Cache key prefix for prompts.
     */
    const CACHE_PREFIX = 'vw_prompt_';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the history records for this prompt.
     */
    public function history(): HasMany
    {
        return $this->hasMany(VwPromptHistory::class, 'prompt_id')->orderBy('version', 'desc');
    }

    /**
     * Get prompt by slug with caching.
     */
    public static function getBySlug(string $slug): ?self
    {
        $cacheKey = self::CACHE_PREFIX . $slug;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            return self::where('slug', $slug)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Compile the prompt template with variables.
     */
    public function compile(array $variables = []): string
    {
        $template = $this->prompt_template;

        foreach ($variables as $key => $value) {
            // Support both {{variable}} and {$variable} syntax
            if (is_scalar($value)) {
                $template = str_replace(['{{' . $key . '}}', '{$' . $key . '}'], $value, $template);
            }
        }

        return $template;
    }

    /**
     * Get available variable placeholders from the template.
     */
    public function getPlaceholders(): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->prompt_template, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Create a new version of this prompt.
     */
    public function createVersion(array $data, ?int $userId = null, ?string $notes = null): self
    {
        // Save current state to history
        VwPromptHistory::create([
            'prompt_id' => $this->id,
            'version' => $this->version,
            'prompt_template' => $this->prompt_template,
            'variables' => $this->variables,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
            'changed_by' => $userId,
            'change_notes' => $notes,
        ]);

        // Update current prompt
        $this->update(array_merge($data, [
            'version' => $this->version + 1,
        ]));

        // Clear cache
        $this->clearCache();

        return $this->fresh();
    }

    /**
     * Rollback to a specific version.
     */
    public function rollbackToVersion(int $version, ?int $userId = null): bool
    {
        $history = $this->history()->where('version', $version)->first();

        if (!$history) {
            return false;
        }

        return $this->createVersion([
            'prompt_template' => $history->prompt_template,
            'variables' => $history->variables,
            'model' => $history->model ?? $this->model,
            'temperature' => $history->temperature ?? $this->temperature,
            'max_tokens' => $history->max_tokens ?? $this->max_tokens,
        ], $userId, "Rollback to version {$version}") !== null;
    }

    /**
     * Clear the cache for this prompt.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . $this->slug);
    }

    /**
     * Clear all prompt caches.
     */
    public static function clearAllCache(): void
    {
        $prompts = self::all();
        foreach ($prompts as $prompt) {
            $prompt->clearCache();
        }
    }

    /**
     * Scope to filter active prompts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($prompt) {
            $prompt->clearCache();
        });

        static::deleted(function ($prompt) {
            $prompt->clearCache();
        });
    }
}
