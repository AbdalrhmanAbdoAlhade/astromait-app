<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\AuctionWinner;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Support\Facades\DB;

class AuctionEngineService
{
    public function __construct(
        private readonly CommissionService $commissionService,
    ) {
    }

    public function create(VendorProfile $vendor, Product $product, array $data): Auction
    {
        if ((int) $product->vendor_id !== (int) $vendor->id) {
            throw new \RuntimeException('مينفعش تعمل مزاد لمنتج مش ملكك.');
        }

        if ($product->activeAuction()->exists()) {
            throw new \RuntimeException('يوجد مزاد نشط بالفعل على هذا المنتج.');
        }

        return Auction::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'start_price' => $data['start_price'],
            'current_price' => $data['start_price'],
            'min_bid_increment' => $data['min_bid_increment'] ?? 10,
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'status' => 'scheduled',
        ]);
    }

    /**
     * Place a bid on a live auction. Wrapped in a transaction with row
     * locking to avoid race conditions between concurrent bidders.
     */
    public function placeBid(Auction $auction, User $bidder, float $amount): Auction
    {
        return DB::transaction(function () use ($auction, $bidder, $amount) {
            /** @var Auction $locked */
            $locked = Auction::where('id', $auction->id)->lockForUpdate()->first();

            if ($locked->status !== 'live') {
                throw new \RuntimeException('المزاد مش شغال دلوقتي.');
            }

            if (now()->greaterThan($locked->end_at)) {
                throw new \RuntimeException('المزاد انتهى.');
            }

            $minAllowed = $locked->current_price + $locked->min_bid_increment;

            if ($amount < $minAllowed) {
                throw new \RuntimeException("أقل مزايدة مسموحة هي {$minAllowed}");
            }

            $locked->bids()->create([
                'user_id' => $bidder->id,
                'amount' => $amount,
            ]);

            $locked->update(['current_price' => $amount]);

            return $locked->fresh(['bids']);
        });
    }

    /**
     * Scheduled job: move scheduled auctions whose start_at has passed into "live".
     */
    public function activateScheduled(): int
    {
        return Auction::where('status', 'scheduled')
            ->where('start_at', '<=', now())
            ->update(['status' => 'live']);
    }

    /**
     * Scheduled job: close auctions whose end_at has passed, determine the
     * winner (highest bid), and create the AuctionWinner record.
     */
    public function closeExpired(): int
    {
        $expired = Auction::where('status', 'live')
            ->where('end_at', '<=', now())
            ->get();

        foreach ($expired as $auction) {
            $this->closeAuction($auction);
        }

        return $expired->count();
    }

    public function closeAuction(Auction $auction): ?AuctionWinner
    {
        return DB::transaction(function () use ($auction) {
            $auction->update(['status' => 'closed']);

            $topBid = $auction->bids()->orderByDesc('amount')->first();

            if (! $topBid) {
                return null;
            }

            return AuctionWinner::create([
                'auction_id' => $auction->id,
                'user_id' => $topBid->user_id,
                'final_amount' => $topBid->amount,
            ]);
        });
    }

    public function cancel(Auction $auction): Auction
    {
        if ($auction->status === 'closed') {
            throw new \RuntimeException('مينفعش تلغي مزاد اتقفل بالفعل.');
        }

        $auction->update(['status' => 'cancelled']);

        return $auction->fresh();
    }
}
