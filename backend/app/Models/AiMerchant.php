<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiMerchant extends Model
{
    protected $table = 'ai_merchants';

    protected $fillable = ['phone', 'name', 'password', 'last_shop_id', 'last_login_at'];

    protected $casts = ['last_login_at' => 'datetime'];

    public function shops(): HasMany
    {
        return $this->hasMany(AiShop::class, 'merchant_id');
    }
}
