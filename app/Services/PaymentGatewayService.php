<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EdfaPay integration (single platform merchant account — the platform
 * itself is the merchant, not per-vendor).
 *
 * Config expected in config/services.php:
 *   'edfapay' => [
 *       'base_url'    => env('EDFAPAY_BASE_URL'),
 *       'merchant_key'=> env('EDFAPAY_MERCHANT_KEY'),
 *       'password'    => env('EDFAPAY_PASSWORD'),
 *       'return_url'  => env('EDFAPAY_RETURN_URL'),
 *   ]
 *
 * NOTE: field names/endpoints below follow the same shape used in the
 * marine-services-platform EdfaPay integration — double-check against
 * the EdfaPay docs/credentials for this project before going live,
 * since exact request/response fields can differ per merchant setup.
 */
class PaymentGatewayService
{
    private string $baseUrl;
    private string $merchantKey;
    private string $password;
    private string $returnUrl;

    public function __construct(private readonly WalletService $walletService)
    {
        $this->baseUrl = config('services.edfapay.base_url');
        $this->merchantKey = config('services.edfapay.merchant_key');
        $this->password = config('services.edfapay.password');
        $this->returnUrl = config('services.edfapay.return_url');
    }

    /**
     * Create a pending Payment record for the order and initiate the
     * EdfaPay payment session, returning the redirect URL for the client.
     */
    public function initiate(Order $order): array
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'edfapay',
            'status' => 'pending',
            'amount' => $order->total,
            'currency' => 'SAR',
        ]);

        $response = Http::asForm()->post("{$this->baseUrl}/payment/create", [
            'merchant_key' => $this->merchantKey,
            'password' => $this->password,
            'order_id' => $order->order_number,
            'amount' => number_format((float) $order->total, 2, '.', ''),
            'currency' => 'SAR',
            'return_url' => $this->returnUrl,
        ]);

        if (! $response->successful()) {
            $payment->update([
                'status' => 'failed',
                'gateway_response' => $response->json() ?? ['raw' => $response->body()],
            ]);

            throw new \RuntimeException('تعذر إنشاء عملية الدفع مع إدفع باي.');
        }

        $data = $response->json();

        $payment->update([
            'transaction_id' => $data['transaction_id'] ?? null,
            'gateway_response' => $data,
        ]);

        return [
            'payment' => $payment->fresh(),
            'redirect_url' => $data['payment_url'] ?? null,
        ];
    }

    /**
     * Handle EdfaPay's server-to-server webhook / callback.
     */
    public function handleWebhook(array $payload): Payment
    {
        $transactionId = $payload['transaction_id'] ?? null;

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (! $payment) {
            Log::warning('EdfaPay webhook: payment not found', $payload);
            throw new \RuntimeException('عملية الدفع غير موجودة.');
        }

        $status = $this->mapGatewayStatus($payload['status'] ?? '');
        $wasPaid = $payment->status === 'paid';

        $payment->update([
            'status' => $status,
            'gateway_response' => array_merge($payment->gateway_response ?? [], ['webhook' => $payload]),
            'paid_at' => $status === 'paid' ? now() : $payment->paid_at,
        ]);

        if ($status === 'paid') {
            $payment->order->update(['status' => 'paid']);
            if (! $wasPaid) {
                $this->walletService->recordOrderPaid($payment->order->fresh('items'));
            }
        }

        return $payment->fresh();
    }

    private function mapGatewayStatus(string $edfapayStatus): string
    {
        return match (strtolower($edfapayStatus)) {
            'captured', 'success', 'paid' => 'paid',
            'refunded' => 'refunded',
            'failed', 'declined' => 'failed',
            default => 'pending',
        };
    }
}
