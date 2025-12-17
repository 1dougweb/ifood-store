<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        protected EvolutionApiService $evolutionApi
    ) {
    }

    /**
     * Check if notification should be sent based on restaurant settings
     *
     * @param Restaurant $restaurant
     * @param string $eventType
     * @return bool
     */
    public function shouldSendNotification(Restaurant $restaurant, string $eventType): bool
    {
        if (!$restaurant->is_active) {
            return false;
        }

        if (!$restaurant->whatsapp_number) {
            return false;
        }

        $settings = $restaurant->notification_settings ?? [];

        // Default: send all notifications if not configured
        if (!isset($settings['enabled_events'])) {
            return true;
        }

        return in_array($eventType, $settings['enabled_events'] ?? []);
    }

    /**
     * Send notification for new order
     *
     * @param Order $order
     * @return Notification|null
     */
    public function notifyNewOrder(Order $order): ?Notification
    {
        if (!$this->shouldSendNotification($order->restaurant, 'new_order')) {
            return null;
        }

        $message = $this->formatNewOrderMessage($order);

        return $this->sendWhatsAppNotification(
            $order->restaurant,
            $order,
            'new_order',
            $message
        );
    }

    /**
     * Send notification for delayed order
     *
     * @param Order $order
     * @return Notification|null
     */
    public function notifyDelayedOrder(Order $order): ?Notification
    {
        if (!$this->shouldSendNotification($order->restaurant, 'delayed_order')) {
            return null;
        }

        $message = $this->formatDelayedOrderMessage($order);

        return $this->sendWhatsAppNotification(
            $order->restaurant,
            $order,
            'delayed_order',
            $message
        );
    }

    /**
     * Send notification for delivered order
     *
     * @param Order $order
     * @return Notification|null
     */
    public function notifyDeliveredOrder(Order $order): ?Notification
    {
        if (!$this->shouldSendNotification($order->restaurant, 'delivered_order')) {
            return null;
        }

        $message = $this->formatDeliveredOrderMessage($order);

        return $this->sendWhatsAppNotification(
            $order->restaurant,
            $order,
            'delivered_order',
            $message
        );
    }

    /**
     * Send notification for cancelled order
     *
     * @param Order $order
     * @return Notification|null
     */
    public function notifyCancelledOrder(Order $order): ?Notification
    {
        if (!$this->shouldSendNotification($order->restaurant, 'cancelled_order')) {
            return null;
        }

        $message = $this->formatCancelledOrderMessage($order);

        return $this->sendWhatsAppNotification(
            $order->restaurant,
            $order,
            'cancelled_order',
            $message
        );
    }

    /**
     * Send WhatsApp notification
     *
     * @param Restaurant $restaurant
     * @param Order|null $order
     * @param string $type
     * @param string $message
     * @return Notification
     */
    protected function sendWhatsAppNotification(
        Restaurant $restaurant,
        ?Order $order,
        string $type,
        string $message
    ): Notification {
        $notification = Notification::create([
            'restaurant_id' => $restaurant->id,
            'order_id' => $order?->id,
            'type' => $type,
            'channel' => 'whatsapp',
            'status' => 'pending',
            'message' => $message,
            'recipient' => $restaurant->whatsapp_number,
        ]);

        $phoneNumber = $this->evolutionApi->formatPhoneNumber($restaurant->whatsapp_number);
        $result = $this->evolutionApi->sendTextMessage($phoneNumber, $message);

        if ($result) {
            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => $result,
            ]);
        } else {
            $notification->update([
                'status' => 'failed',
                'error_message' => 'Failed to send message via Evolution API',
            ]);
        }

        return $notification;
    }

    /**
     * Format message for new order
     *
     * @param Order $order
     * @return string
     */
    protected function formatNewOrderMessage(Order $order): string
    {
        $orderId = $order->short_reference ?? $order->display_id ?? $order->ifood_order_id;
        $total = number_format($order->total_amount, 2, ',', '.');

        return "🍽️ *Novo Pedido Recebido*\n\n" .
            "Pedido: #{$orderId}\n" .
            "Cliente: {$order->customer_name}\n" .
            "Total: R$ {$total}\n" .
            "Itens: {$order->items_count}\n\n" .
            "Acesse o painel para mais detalhes.";
    }

    /**
     * Format message for delayed order
     *
     * @param Order $order
     * @return string
     */
    protected function formatDelayedOrderMessage(Order $order): string
    {
        $orderId = $order->short_reference ?? $order->display_id ?? $order->ifood_order_id;

        return "⚠️ *Pedido em Atraso*\n\n" .
            "Pedido: #{$orderId}\n" .
            "Cliente: {$order->customer_name}\n" .
            "Status: {$order->status}\n\n" .
            "Verifique o status do pedido no painel.";
    }

    /**
     * Format message for delivered order
     *
     * @param Order $order
     * @return string
     */
    protected function formatDeliveredOrderMessage(Order $order): string
    {
        $orderId = $order->short_reference ?? $order->display_id ?? $order->ifood_order_id;
        $total = number_format($order->total_amount, 2, ',', '.');

        return "✅ *Pedido Entregue*\n\n" .
            "Pedido: #{$orderId}\n" .
            "Cliente: {$order->customer_name}\n" .
            "Total: R$ {$total}\n" .
            "Entregue em: " . $order->delivered_at?->format('d/m/Y H:i') . "\n\n" .
            "Pedido finalizado com sucesso!";
    }

    /**
     * Format message for cancelled order
     *
     * @param Order $order
     * @return string
     */
    protected function formatCancelledOrderMessage(Order $order): string
    {
        $orderId = $order->short_reference ?? $order->display_id ?? $order->ifood_order_id;

        return "❌ *Pedido Cancelado*\n\n" .
            "Pedido: #{$orderId}\n" .
            "Cliente: {$order->customer_name}\n" .
            "Motivo: {$order->sub_status}\n\n" .
            "Verifique os detalhes no painel.";
    }
}

