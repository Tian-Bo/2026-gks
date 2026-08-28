<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPointAccount extends Model
{
    protected $table = 'shop_ai_point_accounts';

    protected $fillable = [
        'shop_id', 'merchant_id', 'balance', 'monthly_grant_remaining',
        'trial_activity_remaining', 'trial_poster_remaining',
    ];

    protected $casts = [
        'shop_id' => 'integer', 'merchant_id' => 'integer', 'balance' => 'integer',
        'monthly_grant_remaining' => 'integer', 'trial_activity_remaining' => 'integer',
        'trial_poster_remaining' => 'integer',
    ];
}
