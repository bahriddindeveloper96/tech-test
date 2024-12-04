<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovementTranslation extends Model
{
    protected $fillable = [
        'stock_movement_id',
        'locale',
        'note',
        'reason_text'
    ];

    public function stockMovement()
    {
        return $this->belongsTo(StockMovement::class);
    }
}
