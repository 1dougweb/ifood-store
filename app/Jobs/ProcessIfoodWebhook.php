<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\IfoodService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessIfoodWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $webhookData
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(IfoodService $ifoodService, NotificationService $notificationService): void
    {
        try {
            $eventType = $this->webhookData['event'] ?? null;
            $orderData = $this->webhookData['data'] ?? null;

            if (!$eventType || !$orderData) {
                Log::warning('Invalid webhook data received', ['data' => $this->webhookData]);
                return;
            }

            // Find restaurant by merchant ID or order data
            $restaurant = $this->findRestaurant($orderData);

            if (!$restaurant) {
                Log::warning('Restaurant not found for webhook', ['order_data' => $orderData]);
                return;
            }

            // Get full order details from iFood API
            $orderId = $orderData['id'] ?? null;
            if ($orderId) {
                $fullOrderData = $ifoodService->getOrder($restaurant, $orderId);
                if ($fullOrderData) {
                    $orderData = array_merge($orderData, $fullOrderData);
                }
            }

            // Create or update order
            $order = $this->createOrUpdateOrder($restaurant, $orderData, $eventType);

            // Send notifications based on event type
            match ($eventType) {
                'PLACED' => $notificationService->notifyNewOrder($order),
                'DELIVERED' => $notificationService->notifyDeliveredOrder($order),
                'CANCELLED' => $notificationService->notifyCancelledOrder($order),
                default => null,
            };

            Log::info('Webhook processed successfully', [
                'event' => $eventType,
                'order_id' => $order->id,
                'restaurant_id' => $restaurant->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing iFood webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'webhook_data' => $this->webhookData,
            ]);

            throw $e;
        }
    }

    /**
     * Find restaurant from webhook data
     */
    protected function findRestaurant(array $orderData): ?Restaurant
    {
        // Try to find by merchant ID from order data
        $merchantId = $orderData['merchant']['id'] ?? null;
        if ($merchantId) {
            $restaurant = Restaurant::where('ifood_merchant_id', $merchantId)->first();
            if ($restaurant) {
                return $restaurant;
            }
        }

        // If not found, try to get from state (passed in OAuth flow)
        $state = $this->webhookData['state'] ?? null;
        if ($state) {
            return Restaurant::find($state);
        }

        return null;
    }

    /**
     * Create or update order from iFood data
     */
    protected function createOrUpdateOrder(Restaurant $restaurant, array $orderData, string $eventType): Order
    {
        $orderId = $orderData['id'] ?? null;
        if (!$orderId) {
            throw new \Exception('Order ID not found in webhook data');
        }

        $order = Order::firstOrNew([
            'ifood_order_id' => $orderId,
            'restaurant_id' => $restaurant->id,
        ]);

        // Update order fields
        $order->fill([
            'short_reference' => $orderData['shortReference'] ?? null,
            'display_id' => $orderData['displayId'] ?? null,
            'status' => $orderData['status'] ?? $eventType,
            'sub_status' => $orderData['subStatus'] ?? null,
            'customer_name' => $orderData['customer']['name'] ?? null,
            'customer_phone' => $orderData['customer']['phone'] ?? null,
            'customer_delivery_address' => $this->formatDeliveryAddress($orderData['delivery'] ?? []),
            'total_amount' => $orderData['total']['value'] ?? 0,
            'delivery_fee' => $orderData['delivery']['deliveryFee']['value'] ?? 0,
            'discount' => $orderData['total']['discount'] ?? 0,
            'currency' => $orderData['total']['currency'] ?? 'BRL',
            'notes' => $orderData['notes'] ?? null,
            'payment_methods' => $orderData['payments'] ?? [],
            'delivery_method' => $orderData['delivery'] ?? [],
            'placed_at' => isset($orderData['createdAt']) ? \Carbon\Carbon::parse($orderData['createdAt']) : null,
            'confirmed_at' => isset($orderData['confirmedAt']) ? \Carbon\Carbon::parse($orderData['confirmedAt']) : null,
            'dispatched_at' => isset($orderData['dispatchedAt']) ? \Carbon\Carbon::parse($orderData['dispatchedAt']) : null,
            'delivered_at' => isset($orderData['deliveredAt']) ? \Carbon\Carbon::parse($orderData['deliveredAt']) : null,
            'cancelled_at' => isset($orderData['cancelledAt']) ? \Carbon\Carbon::parse($orderData['cancelledAt']) : null,
            'expected_delivery_at' => isset($orderData['expectedDeliveryAt']) ? \Carbon\Carbon::parse($orderData['expectedDeliveryAt']) : null,
            'ifood_data' => $orderData,
        ]);

        $order->save();

        // Update order items
        if (isset($orderData['items']) && is_array($orderData['items'])) {
            $this->syncOrderItems($order, $orderData['items']);
        }

        // Update items count
        $order->items_count = $order->items()->count();
        $order->save();

        return $order;
    }

    /**
     * Sync order items
     */
    protected function syncOrderItems(Order $order, array $items): void
    {
        // Delete existing items
        $order->items()->delete();

        foreach ($items as $itemData) {
            OrderItem::create([
                'order_id' => $order->id,
                'ifood_item_id' => $itemData['id'] ?? null,
                'name' => $itemData['name'] ?? 'Item sem nome',
                'description' => $itemData['description'] ?? null,
                'quantity' => $itemData['quantity'] ?? 1,
                'unit_price' => $itemData['unitPrice']['value'] ?? 0,
                'total_price' => $itemData['totalPrice']['value'] ?? 0,
                'category' => $itemData['category'] ?? null,
                'modifiers' => $itemData['modifiers'] ?? [],
                'observations' => $itemData['observations'] ?? [],
                'ifood_data' => $itemData,
            ]);
        }
    }

    /**
     * Format delivery address
     */
    protected function formatDeliveryAddress(array $delivery): ?string
    {
        if (empty($delivery)) {
            return null;
        }

        $address = $delivery['address'] ?? [];
        $parts = array_filter([
            $address['streetName'] ?? null,
            $address['streetNumber'] ?? null,
            $address['neighborhood'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postalCode'] ?? null,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }
}
