<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('books.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('books.view') && $this->ownsProductOrIsNotVendor($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->can('books.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('books.edit') && $this->ownsProductOrIsNotVendor($user, $product);
    }

    public function updateInventory(User $user, Product $product): bool
    {
        return $user->can('inventory.update') && $this->ownsProductOrIsNotVendor($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('books.delete') && $this->ownsProductOrIsNotVendor($user, $product);
    }

    public function restore(User $user, Product $product): bool
    {
        return false;
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }

    private function ownsProductOrIsNotVendor(User $user, Product $product): bool
    {
        return ! $user->hasActiveRole('vendor')
            || ($product->vendor_id !== null && (string) $product->vendor_id === (string) $user->getKey());
    }
}
