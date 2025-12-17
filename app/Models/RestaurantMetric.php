<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'period_date',
        'period_type',
        'total_orders',
        'placed_orders',
        'confirmed_orders',
        'delivered_orders',
        'cancelled_orders',
        'delayed_orders',
        'total_revenue',
        'average_order_value',
        'total_delivery_fees',
        'total_discounts',
        'average_preparation_time',
        'average_delivery_time',
        'average_total_time',
        'additional_data',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'total_revenue' => 'decimal:2',
            'average_order_value' => 'decimal:2',
            'total_delivery_fees' => 'decimal:2',
            'total_discounts' => 'decimal:2',
            'average_preparation_time' => 'decimal:2',
            'average_delivery_time' => 'decimal:2',
            'average_total_time' => 'decimal:2',
            'additional_data' => 'array',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
