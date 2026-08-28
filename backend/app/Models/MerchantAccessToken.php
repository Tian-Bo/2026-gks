<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantAccessToken extends Model
{
    protected $table = 'ai_merchant_access_tokens';

    protected $fillable = ['merchant_id', 'shop_id', 'token_hash', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];
}
