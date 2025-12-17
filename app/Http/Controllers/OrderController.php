<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get restaurant IDs the user can access
        if ($user->hasRole('cliente')) {
            $restaurantIds = $user->restaurants()->pluck('id');
        } else {
            $restaurantIds = \App\Models\Restaurant::whereHas('managers', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            })
                ->orWhere('user_id', $user->id)
                ->pluck('id');
        }

        $query = Order::with(['restaurant', 'items'])
            ->whereIn('restaurant_id', $restaurantIds);

        // Filter by restaurant if provided
        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereDate('placed_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('placed_at', '<=', $request->date_to);
        }

        $orders = $query->latest('placed_at')->paginate(20);

        $restaurants = \App\Models\Restaurant::whereIn('id', $restaurantIds)->get();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'restaurants' => $restaurants,
            'filters' => $request->only(['restaurant_id', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Order $order): Response
    {
        $user = $request->user();
        
        // Verificar acesso ao restaurante do pedido
        if ($user->hasRole('cliente')) {
            abort_if($order->restaurant->user_id !== $user->id, 403);
        } else {
            abort_if(
                !$order->restaurant->managers->contains($user) && $order->restaurant->user_id !== $user->id,
                403
            );
        }

        $order->load(['restaurant', 'items', 'notifications']);

        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }
}
