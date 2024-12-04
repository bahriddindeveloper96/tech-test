<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    protected $fillable = [
        'attribute_id',
        'position'
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeValueTranslation::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    // Get translated value
    public function getTranslation($locale = null)
    {
        if ($locale === null) {
            $locale = app()->getLocale();
        }
        
        return $this->translations()->where('locale', $locale)->first();
    }

    // Get value in specific locale
    public function getValueAttribute($value)
    {
        $translation = $this->getTranslation();
        return $translation ? $translation->value : $value;
    }

    // Get description in specific locale
    public function getDescriptionAttribute($value)
    {
        $translation = $this->getTranslation();
        return $translation ? $translation->description : $value;
    }
}
