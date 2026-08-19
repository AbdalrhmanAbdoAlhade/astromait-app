<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Service;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $cart->load('items.itemable');

        return response()->json([
            'cart' => $cart,
            'total' => $cart->total(),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'itemable_type' => 'required|in:product,service',
            'itemable_id' => 'required|integer',
            'quantity' => 'nullable|integer|min:1',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $itemable = $data['itemable_type'] === 'product'
            ? Product::where('status', 'active')->findOrFail($data['itemable_id'])
            : Service::where('status', 'active')->findOrFail($data['itemable_id']);

        $variant = isset($data['product_variant_id'])
            ? ProductVariant::findOrFail($data['product_variant_id'])
            : null;

        $item = $this->cartService->addItem(
            $request->user(),
            $itemable,
            $data['quantity'] ?? 1,
            $variant
        );

        return response()->json($item, 201);
    }

    public function updateQuantity(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeOwnership($request, $cartItem);

        $data = $request->validate(['quantity' => 'required|integer|min:1']);

        return response()->json($this->cartService->updateQuantity($cartItem, $data['quantity']));
    }

    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeOwnership($request, $cartItem);

        $this->cartService->removeItem($cartItem);

        return response()->json(['message' => 'تم حذف العنصر من السلة.']);
    }

    private function authorizeOwnership(Request $request, CartItem $cartItem): void
    {
        if ($cartItem->cart->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
