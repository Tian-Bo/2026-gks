<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiActivity extends Model
{
    protected $table = 'ai_activities';

    protected $fillable = [
        'source_conversation_id', 'merchant_id', 'shop_id', 'activity_model_id', 'title',
        'status', 'cover_img', 'background_color', 'components', 'meta', 'released_at',
    ];

    protected $casts = [
        'components' => 'array',
        'meta' => 'array',
        'released_at' => 'datetime',
    ];
}
