<?php

namespace Modules\AppAIContents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentBusinessDna extends Model
{
    protected $table = 'content_business_dna';

    protected $fillable = [
        'team_id',
        'website_url',
        'brand_name',
        'logo_path',
        'colors',
        'fonts',
        'tagline',
        'brand_values',
        'brand_aesthetic',
        'brand_tone',
        'business_overview',
        'images',
        'raw_scrape_data',
        'status',
    ];

    protected $casts = [
        'colors' => 'array',
        'fonts' => 'array',
        'brand_values' => 'array',
        'brand_aesthetic' => 'array',
        'brand_tone' => 'array',
        'images' => 'array',
        'raw_scrape_data' => 'array',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(ContentCampaign::class, 'dna_id');
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(ContentCampaignIdea::class, 'dna_id');
    }

    public function photoshoots(): HasMany
    {
        return $this->hasMany(ContentPhotoshoot::class, 'dna_id');
    }
}
