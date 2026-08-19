<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Product;
use App\Services\AuctionEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuctionController extends Controller
{
    public function __construct(private readonly AuctionEngineService $auctionEngineService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;

        return response()->json(
            $vendor->auctions()->with(['product', 'bids'])->latest()->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'start_price' => 'required|numeric|min:0',
            'min_bid_increment' => 'nullable|numeric|min:0.01',
            'start_at' => 'required|date|after_or_equal:now',
            'end_at' => 'required|date|after:start_at',
        ]);

        $product = Product::findOrFail($data['product_id']);

        try {
            $auction = $this->auctionEngineService->create($vendor, $product, $data);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($auction, 201);
    }

    public function cancel(Request $request, Auction $auction): JsonResponse
    {
        $this->authorizeOwnership($request, $auction);

        try {
            $auction = $this->auctionEngineService->cancel($auction);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($auction);
    }

    private function authorizeOwnership(Request $request, Auction $auction): void
    {
        $vendor = $request->user()->vendorProfile;

        if (! $vendor || (int) $auction->vendor_id !== (int) $vendor->id) {
            abort(403);
        }
    }
}
