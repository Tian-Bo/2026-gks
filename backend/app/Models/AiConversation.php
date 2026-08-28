<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $table = 'shop_ai_conversations';

    protected $fillable = [
        'conversation_id', 'merchant_id', 'shop_id', 'scene', 'title',
        'status', 'meta', 'latest_message_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'latest_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_record_id');
    }
}
