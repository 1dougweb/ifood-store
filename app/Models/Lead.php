<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'restaurant_name',
        'message',
        'source',
        'contacted',
        'contacted_at',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'contacted' => 'boolean',
            'contacted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
