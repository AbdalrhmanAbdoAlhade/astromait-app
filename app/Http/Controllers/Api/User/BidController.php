<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Services\AuctionEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BidController extends Controller
{
    public function __construct(private readonly AuctionEngineService $auctionEngineService)
    {
    }

    public function store(Request $request, Auction $auction): JsonResponse
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0']);

        try {
            $auction = $this->auctionEngineService->placeBid($auction, $request->user(), $data['amount']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Broadcast event for live bidding UI, e.g.:
        // broadcast(new BidPlaced($auction))->toOthers();

        return response()->json($auction);
    }
}
