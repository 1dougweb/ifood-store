<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    public function edit(Request $request): Response
    {
        $restaurantId = $request->query('restaurant_id');
        $restaurants = $request->user()->restaurants;

        $restaurant = null;
        if ($restaurantId) {
            $restaurant = $restaurants->find($restaurantId);
        } elseif ($restaurants->isNotEmpty()) {
            $restaurant = $restaurants->first();
        }

        return Inertia::render('settings/Notifications', [
            'restaurant' => $restaurant,
            'restaurants' => $restaurants,
        ]);
    }
}
