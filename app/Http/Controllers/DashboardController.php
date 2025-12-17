<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use App\Services\MetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected MetricsService $metricsService
    ) {
    }

    /**
     * Display dashboard data
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Clientes veem apenas seus restaurantes, gestores veem os que gerenciam
        if ($user->hasRole('cliente')) {
            $restaurants = $user->restaurants;
        } else {
            $restaurants = \App\Models\Restaurant::whereHas('managers', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            })
                ->orWhere('user_id', $user->id)
                ->get();
        }

        // Get primary restaurant (first active or first)
        $primaryRestaurant = $restaurants->where('is_active', true)->first()
            ?? $restaurants->first();

        // Get summary metrics
        $summaryMetrics = $primaryRestaurant
            ? $this->metricsService->getSummaryMetrics($primaryRestaurant, 30)
            : [];

        // Get recent orders
        $restaurantIds = $restaurants->pluck('id');
        $recentOrders = Order::whereIn('restaurant_id', $restaurantIds)
            ->with(['restaurant', 'items'])
            ->latest('placed_at')
            ->limit(10)
            ->get();

        // Get pending orders count
        $pendingOrdersCount = Order::whereIn('restaurant_id', $restaurantIds)
            ->whereNotIn('status', ['DELIVERED', 'CANCELLED'])
            ->count();

        // Get metrics for chart (last 30 days)
        $chartMetrics = $primaryRestaurant
            ? $this->metricsService->getMetricsForRange(
                $primaryRestaurant,
                now()->subDays(30),
                now(),
                'daily'
            )
            : collect([]);

        return Inertia::render('Dashboard', [
            'summaryMetrics' => $summaryMetrics,
            'recentOrders' => $recentOrders,
            'pendingOrdersCount' => $pendingOrdersCount,
            'chartMetrics' => $chartMetrics,
            'restaurants' => $restaurants,
            'primaryRestaurant' => $primaryRestaurant,
        ]);
    }
}
