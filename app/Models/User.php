<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'company_tax_number',
        'company_registration_number'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $with = ['translations'];

    protected $appends = ['full_name', 'company_info'];

    public function translations(): HasMany
    {
        return $this->hasMany(UserTranslation::class);
    }

    // Get translated user
    public function getTranslation($locale = null)
    {
        if ($locale === null) {
            $locale = app()->getLocale();
        }
        
        return $this->translations()->where('locale', $locale)->first();
    }

    // Get bio in specific locale
    public function getBioAttribute($value)
    {
        $translation = $this->getTranslation();
        return $translation ? $translation->bio : $value;
    }

    // Get address in specific locale
    public function getAddressAttribute($value)
    {
        $translation = $this->getTranslation();
        return $translation ? $translation->address : $value;
    }

    // Combine first and last name
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Check if user is seller
    public function isSeller()
    {
        return $this->role === 'seller';
    }

    // Get company info if seller
    public function getCompanyInfoAttribute()
    {
        if (!$this->isSeller()) {
            return null;
        }

        return [
            'name' => $this->company_name,
            'address' => $this->company_address,
            'phone' => $this->company_phone,
            'email' => $this->company_email,
            'tax_number' => $this->company_tax_number,
            'registration_number' => $this->company_registration_number
        ];
    }

    // Scope for active users
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope for sellers
    public function scopeSellers($query)
    {
        return $query->where('role', 'seller');
    }

    // Products relationship
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    // Orders relationship
    public function orders()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    // Reviews relationship
    public function reviews()
    {
        return $this->hasMany(Review::class, 'seller_id');
    }

    // Favorites relationship
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    // Compare lists relationship
    public function compareLists()
    {
        return $this->hasMany(CompareList::class);
    }

    // Check if user is admin
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Check if user is active
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // Check if user is customer
    public function isCustomer()
    {
        return $this->role === 'customer';
    }
}
