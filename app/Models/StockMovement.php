<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'previous_stock',
        'new_stock',
        'change',
        'reason',
        'created_by'
    ];

    protected $casts = [
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
        'change' => 'integer'
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(StockMovementTranslation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Get translated movement
    public function getTranslation($locale = null)
    {
        if ($locale === null) {
            $locale = app()->getLocale();
        }
        
        return $this->translations()->where('locale', $locale)->first();
    }

    // Get note in specific locale
    public function getNoteAttribute($value)
    {
        $translation = $this->getTranslation();
        return $translation ? $translation->note : $value;
    }

    // Get reason text in specific locale
    public function getReasonTextAttribute()
    {
        $translation = $this->getTranslation();
        if ($translation && $translation->reason_text) {
            return $translation->reason_text;
        }

        // Default reason texts in different languages
        $reasonTexts = [
            'purchase' => [
                'uz' => 'Sotib olish',
                'ru' => 'Покупка',
                'en' => 'Purchase'
            ],
            'return' => [
                'uz' => 'Qaytarish',
                'ru' => 'Возврат',
                'en' => 'Return'
            ],
            'adjustment' => [
                'uz' => 'Tuzatish',
                'ru' => 'Корректировка',
                'en' => 'Adjustment'
            ],
            'sale' => [
                'uz' => 'Sotish',
                'ru' => 'Продажа',
                'en' => 'Sale'
            ],
            'damage' => [
                'uz' => 'Zarar',
                'ru' => 'Повреждение',
                'en' => 'Damage'
            ]
        ];

        $locale = app()->getLocale();
        return $reasonTexts[$this->reason][$locale] ?? $this->reason;
    }
}
