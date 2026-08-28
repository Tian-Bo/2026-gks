<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPointLedger extends Model
{
    protected $table = 'shop_ai_point_ledgers';

    protected $casts = ['meta' => 'array'];
}
