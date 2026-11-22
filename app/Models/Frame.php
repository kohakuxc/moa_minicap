<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Frame extends Model
{
    protected $fillable = [
        'sku',
        'brand',
        'model_name',
        'category',
        'shape',
        'material',
        'color',
        'gender',
        'eye_size',
        'bridge_size',
        'temple_length',
        'retail_price',
        'cost_price',
        'stock_quantity',
        'reorder_threshold',
        'supplier_name',
        'is_consignment',
        'image_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'eye_size' => 'integer',
            'bridge_size' => 'integer',
            'temple_length' => 'integer',
            'retail_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reorder_threshold' => 'integer',
            'is_consignment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }

    public function tryOnReservations(): HasMany
    {
        return $this->hasMany(TryOnReservation::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->reorder_threshold;
    }
}
