<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $notification = $request->all();

            $orderId = $notification['order_id'];
            $statusCode = $notification['status_code'];
            $grossAmount = $notification['gross_amount'];
            $reqSignature = $notification['signature_key'];
            
            $serverKey = config('midtrans.server_key');

            // 1. Signature Verification
            $localSignature = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);

            if ($reqSignature !== $localSignature) {
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            // 2. Find Transaction
            $transaction = Transaction::where('transaction_code', $orderId)->first();

            if (!$transaction) {
                // Transaction not found, usually we return 200 to stop Midtrans from retrying
                // But logging it is important
                Log::warning("Midtrans Webhook: Transaction $orderId not found");
                return response()->json(['message' => 'Transaction not found'], 200);
            }

            // 3. Idempotency Check
            // If transaction is already in final state, ignore update
            if (in_array($transaction->status, ['completed', 'cancelled', 'failed', 'expired'])) {
                 return response()->json(['message' => 'Transaction already processed'], 200);
            }

            $transactionStatus = $notification['transaction_status'];
            $fraudStatus = $notification['fraud_status'] ?? null;
            $paymentType = $notification['payment_type'] ?? null;

            $newStatus = null;
            $shouldRestoreStock = false;

            // 4. Status Mapping
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'challenge') {
                    $newStatus = 'pending'; // Or specific 'challenge' status if POS supports it
                } else if ($fraudStatus == 'accept') {
                    $newStatus = 'completed';
                }
            } else if ($transactionStatus == 'settlement') {
                $newStatus = 'completed';
            } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
                $newStatus = 'cancelled'; // or expired/failed
                if ($transactionStatus == 'expire') $newStatus = 'expired';
                if ($transactionStatus == 'deny') $newStatus = 'failed';
                
                $shouldRestoreStock = true;
            } else if ($transactionStatus == 'pending') {
                $newStatus = 'pending';
            }

            // 5. Update Transaction Logic
            if ($newStatus) {
                DB::transaction(function () use ($transaction, $newStatus, $transactionStatus, $paymentType, $shouldRestoreStock) {
                    $updateData = [
                        'status' => $newStatus,
                        'gateway_status' => $transactionStatus,
                        'payment_channel' => $paymentType
                    ];

                    if ($newStatus == 'completed') {
                        $updateData['paid_at'] = now();
                    }

                    $transaction->update($updateData);

                    // 6. Stock Reversal (if failed/cancelled)
                    if ($shouldRestoreStock) {
                        foreach ($transaction->details as $detail) {
                            $detail->product->increment('stock', $detail->quantity);
                        }
                        Log::info("Stock restored for transaction " . $transaction->transaction_code);
                    }
                });
            }

            return response()->json(['message' => 'OK'], 200);

        } catch (\Exception $e) {
            Log::error("Midtrans Webhook Error: " . $e->getMessage());
            // Return 200 even on error to prevent infinite retries from Midtrans if logic fails, 
            // unless we want retry. "500" triggers retry. "200" stops using.
            // For safety in production, usually 500 triggers retry.
            // But user requirement says "return 200 OK cepat".
            return response()->json(['message' => 'Accepted with error'], 200);
        }
    }
}
