<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $table = 'shop_ai_messages';

    protected $fillable = [
        'conversation_record_id', 'message_id', 'merchant_id', 'shop_id', 'conversation_id',
        'role', 'status', 'content', 'attachments', 'component_result', 'meta',
        'error_code', 'error_message', 'started_at', 'completed_at', 'stopped_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'component_result' => 'array',
        'meta' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_record_id');
    }
}
