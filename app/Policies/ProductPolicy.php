<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->isSeller() && $product->seller_id === $user->id;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isSeller() && $product->seller_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isSeller() && $product->seller_id === $user->id;
    }
}