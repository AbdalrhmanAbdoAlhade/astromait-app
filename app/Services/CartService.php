<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CartService
{
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(User $user, Model $itemable, int $quantity = 1, ?ProductVariant $variant = null): CartItem
    {
        $this->assertItemableType($itemable);

        $cart = $this->getOrCreateCart($user);
        $price = $this->resolvePrice($itemable, $variant);
        $vendorId = $itemable->vendor_id;

        $existing = CartItem::where('cart_id', $cart->id)
            ->where('itemable_type', $itemable::class)
            ->where('itemable_id', $itemable->id)
            ->where('product_variant_id', $variant?->id)
            ->first();

        if ($existing) {
            $existing->update(['quantity' => $existing->quantity + $quantity]);

            return $existing->fresh();
        }

        return CartItem::create([
            'cart_id' => $cart->id,
            'itemable_type' => $itemable::class,
            'itemable_id' => $itemable->id,
            'vendor_id' => $vendorId,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'price' => $price,
        ]);
    }

    public function updateQuantity(CartItem $item, int $quantity): CartItem
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('الكمية لازم تكون 1 على الأقل.');
        }

        if ($item->itemable instanceof Product) {
            $availableStock = $item->variant?->stock ?? $item->itemable->stock;

            if ($quantity > $availableStock) {
                throw new \RuntimeException('الكمية المطلوبة أكبر من المتاح في المخزون.');
            }
        }

        $item->update(['quantity' => $quantity]);

        return $item->fresh();
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(User $user): void
    {
        $cart = $this->getOrCreateCart($user);
        $cart->items()->delete();
    }

    private function assertItemableType(Model $itemable): void
    {
        if (! $itemable instanceof Product && ! $itemable instanceof Service) {
            throw new \InvalidArgumentException('السلة بتقبل منتجات أو خدمات بس.');
        }
    }

    private function resolvePrice(Model $itemable, ?ProductVariant $variant): float
    {
        $basePrice = (float) $itemable->price;

        if ($variant) {
            $basePrice += (float) $variant->extra_price;
        }

        return $basePrice;
    }
}
