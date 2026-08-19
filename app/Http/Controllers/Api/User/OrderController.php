<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly CartService $cartService,
        private readonly PaymentGatewayService $paymentGatewayService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()
            ->with(['items', 'payment', 'shipment'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($orders);
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = $request->user()->orders()
            ->where('order_number', $orderNumber)
            ->with(['items.orderable', 'payment', 'shipment', 'address'])
            ->firstOrFail();

        return response()->json($order);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'coupon_code' => 'nullable|string',
        ]);

        $address = Address::where('user_id', $request->user()->id)->findOrFail($data['address_id']);
        $cart = $this->cartService->getOrCreateCart($request->user());

        $order = $this->orderService->checkout($request->user(), $cart, $address, $data['coupon_code'] ?? null);

        $payment = $this->paymentGatewayService->initiate($order);

        return response()->json([
            'order' => $order,
            'redirect_url' => $payment['redirect_url'],
        ], 201);
    }
}
