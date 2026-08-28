<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiShop extends Model
{
    protected $table = 'ai_shops';

    protected $fillable = ['merchant_id', 'name', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(AiMerchant::class, 'merchant_id');
    }
}
