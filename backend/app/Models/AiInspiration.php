<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiInspiration extends Model
{
    use SoftDeletes;

    protected $table = 'ai_inspirations';

    protected $fillable = [
        'type', 'activity_id', 'shop_id', 'title', 'image_url', 'prompt',
        'quick_prompt', 'sort', 'is_online',
    ];

    protected $casts = [
        'activity_id' => 'integer', 'shop_id' => 'integer', 'sort' => 'integer', 'is_online' => 'integer',
    ];
}
