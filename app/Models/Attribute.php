<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attribute extends Model
{
    protected $fillable = [
        'group_id',
        'type',
        'is_required',
        'is_filterable',
        'position'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_filterable' => 'boolean'
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeTranslation::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class);
    }

    // Get translated attribute
    public function getTranslation($locale = null)
    {
        if ($locale === null) {
            $locale = app()->getLocale();
        }
        
        return $this->translations()->where('locale', $locale)->first();
    }

    // Get name in specific locale
    public function getNameAttribute($value)
    {
        $translation = $this->getTranslation();
        return $translation ? $translation->name : $value;
    }

    // Get description in specific locale
    public function getDescriptionAttribute($value)
    {
        $translation = $this->getTranslation();
        return $translation ? $translation->description : $value;
    }
}
