<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'cnpj',
        'address',
        'phone',
        'whatsapp_number',
        'ifood_client_id',
        'ifood_client_secret',
        'ifood_access_token',
        'ifood_refresh_token',
        'ifood_token_expires_at',
        'ifood_merchant_id',
        'notification_settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ifood_token_expires_at' => 'datetime',
            'notification_settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(RestaurantMetric::class);
    }

    /**
     * Managers assigned to this restaurant
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'restaurant_manager', 'restaurant_id', 'manager_id')
            ->withTimestamps();
    }
}
