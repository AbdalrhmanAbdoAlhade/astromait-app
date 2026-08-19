<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuctionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Auction::with(['product.primaryImage', 'vendor'])
            ->whereIn('status', ['scheduled', 'live']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->orderBy('end_at')->paginate($request->integer('per_page', 20)));
    }

    public function show(Auction $auction): JsonResponse
    {
        $auction->load(['product.images', 'vendor', 'bids.user:id,name']);

        return response()->json($auction);
    }
}
