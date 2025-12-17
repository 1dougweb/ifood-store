<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantMetric;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MetricsService
{
    /**
     * Calculate metrics for a restaurant for a specific period
     *
     * @param Restaurant $restaurant
     * @param Carbon $date
     * @param string $periodType
     * @return RestaurantMetric
     */
    public function calculateMetrics(Restaurant $restaurant, Carbon $date, string $periodType = 'daily'): RestaurantMetric
    {
        $startDate = match ($periodType) {
            'daily' => $date->copy()->startOfDay(),
            'weekly' => $date->copy()->startOfWeek(),
            'monthly' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };

        $endDate = match ($periodType) {
            'daily' => $date->copy()->endOfDay(),
            'weekly' => $date->copy()->endOfWeek(),
            'monthly' => $date->copy()->endOfMonth(),
            default => $date->copy()->endOfDay(),
        };

        $orders = Order::where('restaurant_id', $restaurant->id)
            ->whereBetween('placed_at', [$startDate, $endDate])
            ->get();

        $totalOrders = $orders->count();
        $placedOrders = $orders->where('status', 'PLACED')->count();
        $confirmedOrders = $orders->where('status', 'CONFIRMED')->count();
        $deliveredOrders = $orders->where('status', 'DELIVERED')->count();
        $cancelledOrders = $orders->where('status', 'CANCELLED')->count();

        // Calculate delayed orders (expected_delivery_at is in the past and status is not DELIVERED or CANCELLED)
        $delayedOrders = $orders->filter(function ($order) {
            return $order->expected_delivery_at
                && $order->expected_delivery_at->isPast()
                && !in_array($order->status, ['DELIVERED', 'CANCELLED']);
        })->count();

        $totalRevenue = $orders->where('status', 'DELIVERED')->sum('total_amount');
        $totalDeliveryFees = $orders->sum('delivery_fee');
        $totalDiscounts = $orders->sum('discount');
        $averageOrderValue = $deliveredOrders > 0 ? $totalRevenue / $deliveredOrders : 0;

        // Calculate average times
        $deliveredOrdersWithTimes = $orders->where('status', 'DELIVERED')
            ->filter(fn($order) => $order->placed_at && $order->delivered_at);

        $averagePreparationTime = null;
        $averageDeliveryTime = null;
        $averageTotalTime = null;

        if ($deliveredOrdersWithTimes->isNotEmpty()) {
            $totalTimes = $deliveredOrdersWithTimes->map(function ($order) {
                $preparationTime = $order->confirmed_at && $order->dispatched_at
                    ? $order->confirmed_at->diffInMinutes($order->dispatched_at)
                    : null;

                $deliveryTime = $order->dispatched_at && $order->delivered_at
                    ? $order->dispatched_at->diffInMinutes($order->delivered_at)
                    : null;

                $totalTime = $order->placed_at && $order->delivered_at
                    ? $order->placed_at->diffInMinutes($order->delivered_at)
                    : null;

                return [
                    'preparation' => $preparationTime,
                    'delivery' => $deliveryTime,
                    'total' => $totalTime,
                ];
            });

            $preparationTimes = $totalTimes->pluck('preparation')->filter();
            $deliveryTimes = $totalTimes->pluck('delivery')->filter();
            $totalTimesList = $totalTimes->pluck('total')->filter();

            $averagePreparationTime = $preparationTimes->isNotEmpty() ? $preparationTimes->average() : null;
            $averageDeliveryTime = $deliveryTimes->isNotEmpty() ? $deliveryTimes->average() : null;
            $averageTotalTime = $totalTimesList->isNotEmpty() ? $totalTimesList->average() : null;
        }

        return RestaurantMetric::updateOrCreate(
            [
                'restaurant_id' => $restaurant->id,
                'period_date' => $date->format('Y-m-d'),
                'period_type' => $periodType,
            ],
            [
                'total_orders' => $totalOrders,
                'placed_orders' => $placedOrders,
                'confirmed_orders' => $confirmedOrders,
                'delivered_orders' => $deliveredOrders,
                'cancelled_orders' => $cancelledOrders,
                'delayed_orders' => $delayedOrders,
                'total_revenue' => $totalRevenue,
                'average_order_value' => $averageOrderValue,
                'total_delivery_fees' => $totalDeliveryFees,
                'total_discounts' => $totalDiscounts,
                'average_preparation_time' => $averagePreparationTime,
                'average_delivery_time' => $averageDeliveryTime,
                'average_total_time' => $averageTotalTime,
            ]
        );
    }

    /**
     * Get metrics for a date range
     *
     * @param Restaurant $restaurant
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $periodType
     * @return Collection
     */
    public function getMetricsForRange(
        Restaurant $restaurant,
        Carbon $startDate,
        Carbon $endDate,
        string $periodType = 'daily'
    ): Collection {
        return RestaurantMetric::where('restaurant_id', $restaurant->id)
            ->where('period_type', $periodType)
            ->whereBetween('period_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('period_date')
            ->get();
    }

    /**
     * Get summary metrics for dashboard
     *
     * @param Restaurant $restaurant
     * @param int $days
     * @return array
     */
    public function getSummaryMetrics(Restaurant $restaurant, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        $endDate = now();

        $orders = Order::where('restaurant_id', $restaurant->id)
            ->whereBetween('placed_at', [$startDate, $endDate])
            ->get();

        $todayOrders = Order::where('restaurant_id', $restaurant->id)
            ->whereDate('placed_at', today())
            ->get();

        return [
            'total_orders' => $orders->count(),
            'today_orders' => $todayOrders->count(),
            'pending_orders' => $orders->whereNotIn('status', ['DELIVERED', 'CANCELLED'])->count(),
            'delivered_orders' => $orders->where('status', 'DELIVERED')->count(),
            'cancelled_orders' => $orders->where('status', 'CANCELLED')->count(),
            'total_revenue' => $orders->where('status', 'DELIVERED')->sum('total_amount'),
            'today_revenue' => $todayOrders->where('status', 'DELIVERED')->sum('total_amount'),
            'average_order_value' => $orders->where('status', 'DELIVERED')->count() > 0
                ? $orders->where('status', 'DELIVERED')->sum('total_amount') / $orders->where('status', 'DELIVERED')->count()
                : 0,
        ];
    }
}

