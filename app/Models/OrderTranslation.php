<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTranslation extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'locale',
        'order_id',
        'status_message',
        'delivery_notes'
    ];
}
