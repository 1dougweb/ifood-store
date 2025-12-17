<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('LandingPage');
})->name('home');

// Public routes
Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');

// Webhook routes (public, but should be protected with signature validation)
Route::post('/api/webhooks/ifood', [WebhookController::class, 'handleIfood'])->name('webhooks.ifood');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('restaurants', RestaurantController::class);
    Route::post('/restaurants/{restaurant}/ifood/user-code', [RestaurantController::class, 'getIfoodUserCode'])->name('restaurants.ifood.user-code');
    Route::post('/restaurants/{restaurant}/ifood/exchange-code', [RestaurantController::class, 'exchangeIfoodUserCode'])->name('restaurants.ifood.exchange-code');
    Route::post('/restaurants/{restaurant}/ifood/connect', [RestaurantController::class, 'connectIfood'])->name('restaurants.ifood.connect');
    Route::get('/restaurants/{restaurant}/ifood/auth-url', [RestaurantController::class, 'getIfoodAuthUrl'])->name('restaurants.ifood.auth-url');
    Route::get('/restaurants/{restaurant}/ifood/callback', [RestaurantController::class, 'handleIfoodCallback'])->name('restaurants.ifood.callback');
    
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    
    // Integrations (only for managers and admins)
    Route::get('/integrations', [\App\Http\Controllers\IntegrationsController::class, 'index'])
        ->name('integrations.index')
        ->middleware('permission:view-integrations');
    
    // Language
    Route::post('/language', [\App\Http\Controllers\LanguageController::class, 'update'])->name('language.update');
    
    // Notifications API
    Route::prefix('api')->group(function () {
        Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('api.notifications.index');
        Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('api.notifications.read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('api.notifications.read-all');
    });
});

require __DIR__.'/settings.php';
