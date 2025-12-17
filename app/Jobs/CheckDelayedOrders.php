<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckDelayedOrders implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        // Find orders that are delayed (expected_delivery_at is in the past and not delivered/cancelled)
        $delayedOrders = Order::whereNotNull('expected_delivery_at')
            ->where('expected_delivery_at', '<', now())
            ->whereNotIn('status', ['DELIVERED', 'CANCELLED'])
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', 'delayed_order')
                    ->where('status', 'sent');
            })
            ->get();

        foreach ($delayedOrders as $order) {
            try {
                $notificationService->notifyDelayedOrder($order);
            } catch (\Exception $e) {
                Log::error('Error sending delayed order notification', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Checked delayed orders', [
            'count' => $delayedOrders->count(),
        ]);
    }
}
