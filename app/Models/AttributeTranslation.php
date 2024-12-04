<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeTranslation extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'attribute_id',
        'locale',
        'name',
        'description'
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
