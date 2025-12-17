<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get notifications for restaurants the user owns or manages
        try {
            $restaurantIds = collect();
            
            // Get restaurants the user owns
            if (method_exists($user, 'restaurants')) {
                $restaurantIds = $restaurantIds->merge($user->restaurants()->pluck('id'));
            }
            
            // Get restaurants the user manages
            if (method_exists($user, 'managedRestaurants')) {
                $restaurantIds = $restaurantIds->merge($user->managedRestaurants()->pluck('restaurants.id'));
            }
            
            $restaurantIds = $restaurantIds->unique();
        } catch (\Exception $e) {
            \Log::error('Error getting restaurant IDs for notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            $restaurantIds = collect();
        }

        // If no restaurants, return empty
        if ($restaurantIds->isEmpty()) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
            ]);
        }

        $notifications = Notification::whereIn('restaurant_id', $restaurantIds)
            ->whereNull('read_at')
            ->with(['restaurant', 'order'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'message' => $notification->message,
                    'restaurant_name' => $notification->restaurant->name ?? null,
                    'order_id' => $notification->order_id,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'read_at' => $notification->read_at,
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->count(),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Verify user has access to this notification's restaurant
        $restaurantIds = $user->restaurants()->pluck('id');
        
        if (method_exists($user, 'managedRestaurants')) {
            $restaurantIds = $restaurantIds->merge($user->managedRestaurants()->pluck('restaurants.id'));
        }
        
        $restaurantIds = $restaurantIds->unique();

        if (!$restaurantIds->contains($notification->restaurant_id)) {
            abort(403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $restaurantIds = $user->restaurants()->pluck('id');
        
        if (method_exists($user, 'managedRestaurants')) {
            $restaurantIds = $restaurantIds->merge($user->managedRestaurants()->pluck('restaurants.id'));
        }
        
        $restaurantIds = $restaurantIds->unique();

        if ($restaurantIds->isNotEmpty()) {
            Notification::whereIn('restaurant_id', $restaurantIds)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
