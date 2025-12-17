<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'ifood_order_id',
        'short_reference',
        'display_id',
        'status',
        'sub_status',
        'customer_name',
        'customer_phone',
        'customer_delivery_address',
        'total_amount',
        'delivery_fee',
        'discount',
        'currency',
        'items_count',
        'notes',
        'payment_methods',
        'delivery_method',
        'placed_at',
        'confirmed_at',
        'dispatched_at',
        'delivered_at',
        'cancelled_at',
        'expected_delivery_at',
        'ifood_data',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount' => 'decimal:2',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expected_delivery_at' => 'datetime',
            'payment_methods' => 'array',
            'delivery_method' => 'array',
            'ifood_data' => 'array',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
