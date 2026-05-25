<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    public function getWishlist(User $user): Collection
    {
        return Wishlist::with('product')
            ->where('user_uuid', $user->uuid)
            ->latest()
            ->get();
    }

    public function getWishlistProducts(User $user): Collection
    {
        return $user->wishlistProducts()->get();
    }

    public function addToWishlist(User $user, int $productId): Wishlist
    {
        // Check if product exists
        $product = Product::findOrFail($productId);

        // Check if already in wishlist
        if ($user->isInWishlist($productId)) {
            throw new \Exception('Product already in wishlist');
        }

        return Wishlist::create([
            'user_uuid' => $user->uuid,
            'product_id' => $productId
        ]);
    }

    public function removeFromWishlist(User $user, int $productId): bool
    {
        $deleted = Wishlist::where('user_uuid', $user->uuid)
            ->where('product_id', $productId)
            ->delete();

        if (!$deleted) {
            throw new \Exception('Product not found in wishlist');
        }

        return true;
    }

    public function toggleWishlist(User $user, int $productId): array
    {
        $isInWishlist = $user->isInWishlist($productId);

        if ($isInWishlist) {
            $this->removeFromWishlist($user, $productId);
            return [
                'status' => 'removed',
                'message' => 'Product removed from wishlist'
            ];
        } else {
            $this->addToWishlist($user, $productId);
            return [
                'status' => 'added',
                'message' => 'Product added to wishlist'
            ];
        }
    }

    public function getWishlistCount(User $user): int
    {
        return $user->wishlist_count;
    }

    public function checkInWishlist(User $user, int $productId): bool
    {
        return $user->isInWishlist($productId);
    }

    public function clearWishlist(User $user): bool
    {
        return Wishlist::where('user_uuid', $user->uuid)->delete();
    }

    public function getWishlistWithPagination(User $user, int $perPage = 15)
    {
        return Wishlist::with('product')
            ->where('user_uuid', $user->uuid)
            ->latest()
            ->paginate($perPage);
    }
}
