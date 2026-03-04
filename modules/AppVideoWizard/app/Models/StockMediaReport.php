<?php

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMediaReport extends Model
{
    protected $table = 'wizard_stock_media_reports';

    public $timestamps = false;

    protected $fillable = [
        'stock_media_id',
        'ip_address',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function stockMedia(): BelongsTo
    {
        return $this->belongsTo(StockMedia::class);
    }
}
