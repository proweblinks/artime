<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VwSetting extends Model
{
    protected $table = 'vw_settings';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'value_type',
        'value',
        'default_value',
        'min_value',
        'max_value',
        'allowed_values',
        'input_type',
        'input_placeholder',
        'input_help',
        'icon',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected $casts = [
        'allowed_values' => 'array',
        'min_value' => 'integer',
        'max_value' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CACHE_KEY = 'vw_settings';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Category constants for type safety.
     */
    const CATEGORY_SHOT_INTELLIGENCE = 'shot_intelligence';
    const CATEGORY_ANIMATION = 'animation';
    const CATEGORY_DURATION = 'duration';
    const CATEGORY_SCENE = 'scene';
    const CATEGORY_EXPORT = 'export';
    const CATEGORY_GENERAL = 'general';

    /**
     * Get all active settings with caching.
     */
    public static function getAllCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->map(fn($setting) => $setting->toConfigArray())
                ->toArray();
        });
    }

    /**
     * Get a setting value by slug with default fallback.
     */
    public static function getValue(string $slug, mixed $default = null): mixed
    {
        $settings = self::getAllCached();

        if (!isset($settings[$slug])) {
            return $default;
        }

        $setting = $settings[$slug];
        $value = $setting['value'] ?? $setting['defaultValue'] ?? $default;

        // Cast value based on type
        return self::castValue($value, $setting['valueType'] ?? 'string');
    }

    /**
     * Get multiple setting values by slugs.
     */
    public static function getValues(array $slugs): array
    {
        $result = [];
        foreach ($slugs as $slug => $default) {
            if (is_int($slug)) {
                // Array without defaults: ['slug1', 'slug2']
                $result[$default] = self::getValue($default);
            } else {
                // Array with defaults: ['slug1' => 'default1']
                $result[$slug] = self::getValue($slug, $default);
            }
        }
        return $result;
    }

    /**
     * Get all settings for a category.
     */
    public static function getByCategory(string $category): array
    {
        $settings = self::getAllCached();

        return array_filter($settings, fn($s) => ($s['category'] ?? '') === $category);
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $slug, mixed $value): bool
    {
        $setting = self::where('slug', $slug)->first();

        if (!$setting) {
            return false;
        }

        // Convert value to string for storage
        $stringValue = is_array($value) || is_object($value)
            ? json_encode($value)
            : (string) $value;

        $setting->update(['value' => $stringValue]);

        return true;
    }

    /**
     * Cast value to appropriate type.
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => is_array($value) ? $value : json_decode($value, true),
            default => (string) $value,
        };
    }

    /**
     * Convert to config array format.
     */
    public function toConfigArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'valueType' => $this->value_type,
            'value' => $this->getTypedValue(),
            'defaultValue' => $this->getTypedDefaultValue(),
            'minValue' => $this->min_value,
            'maxValue' => $this->max_value,
            'allowedValues' => $this->allowed_values,
            'inputType' => $this->input_type,
            'inputPlaceholder' => $this->input_placeholder,
            'inputHelp' => $this->input_help,
            'icon' => $this->icon,
            'isSystem' => $this->is_system,
        ];
    }

    /**
     * Get typed value.
     */
    public function getTypedValue(): mixed
    {
        return self::castValue($this->value, $this->value_type);
    }

    /**
     * Get typed default value.
     */
    public function getTypedDefaultValue(): mixed
    {
        return self::castValue($this->default_value, $this->value_type);
    }

    /**
     * Clear cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Scope to filter active settings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(fn() => self::clearCache());
        static::deleted(fn() => self::clearCache());
    }

    /**
     * Get category labels for admin UI.
     */
    public static function getCategoryLabels(): array
    {
        return [
            self::CATEGORY_SHOT_INTELLIGENCE => 'Shot Intelligence',
            self::CATEGORY_ANIMATION => 'Animation Models',
            self::CATEGORY_DURATION => 'Duration Settings',
            self::CATEGORY_SCENE => 'Scene Processing',
            self::CATEGORY_EXPORT => 'Export Settings',
            self::CATEGORY_GENERAL => 'General',
        ];
    }

    /**
     * Get category icons for admin UI.
     */
    public static function getCategoryIcons(): array
    {
        return [
            self::CATEGORY_SHOT_INTELLIGENCE => 'fa-solid fa-brain',
            self::CATEGORY_ANIMATION => 'fa-solid fa-film',
            self::CATEGORY_DURATION => 'fa-solid fa-clock',
            self::CATEGORY_SCENE => 'fa-solid fa-clapperboard',
            self::CATEGORY_EXPORT => 'fa-solid fa-download',
            self::CATEGORY_GENERAL => 'fa-solid fa-cog',
        ];
    }
}
