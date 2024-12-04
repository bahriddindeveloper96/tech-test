<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeGroup extends Model
{
    protected $fillable = ['name'];

    protected $with = ['translations', 'attributes'];

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeGroupTranslation::class);
    }

    public function getNameAttribute()
    {
        $locale = request()->header('Accept-Language', 'uz');
        return $this->translations->where('locale', $locale)->first()?->name ?? $this->attributes['name'];
    }
}
