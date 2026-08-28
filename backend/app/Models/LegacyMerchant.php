<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyMerchant extends Model
{
    protected $table = 'merchants';

    protected $fillable = ['last_login_at'];

    protected $casts = ['last_login_at' => 'datetime'];
}
