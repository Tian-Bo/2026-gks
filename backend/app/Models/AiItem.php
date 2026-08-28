<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiItem extends Model
{
    protected $table = 'ai_items';

    protected $fillable = ['merchant_id', 'shop_id', 'type', 'title', 'cover', 'base_price', 'stock', 'status'];
}
