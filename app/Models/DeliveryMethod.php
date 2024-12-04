<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryMethod extends Model
{
    protected $fillable = [
        'code',
        'base_cost',
        'estimated_days',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'base_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'settings' => 'json',
    ];

    protected $with = ['translations'];

    public function translations(): HasMany
    {
        return $this->hasMany(DeliveryMethodTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations()->where('locale', $locale)->first();
    }

    public function getNameAttribute()
    {
        return $this->translation()->name ?? null;
    }

    public function getDescriptionAttribute()
    {
        return $this->translation()->description ?? null;
    }
}