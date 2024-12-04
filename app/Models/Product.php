<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class Product extends Model implements TranslatableContract
{
    use Translatable;

    public array $translatedAttributes = [
        'name',
        'description'
    ];

    protected $fillable = [
        'seller_id',
        'category_id',
        'slug',
        'status',
        'featured',
        'price',
        'old_price',
        'stock'
    ];

    protected $casts = [
        'featured' => 'boolean',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'stock' => 'integer'
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function skuAttributes(): HasMany
    {
        return $this->hasMany(SkuAttribute::class);
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
