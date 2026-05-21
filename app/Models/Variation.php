<?php

namespace App\Models;

use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Characteristic;

class Variation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'quantity',
        'sold_count',
        'is_default',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'sold_count' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'image_url',
        'is_in_stock',
        'final_price', // 🔥 NEW
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(VariationAttribute::class)->with(['attribute', 'option']);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VariationImage::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function characteristics(): HasMany
    {
        return $this->hasMany(Characteristic::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute(): ?string
    {
        return ImageHelper::url($this->image);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->quantity > 0;
    }

public function getFinalPriceAttribute(): float
{
    $modifier = $this->attributes()
        ->get()
        ->sum(fn ($attr) => $attr->option?->price_modifier ?? 0);

    return (float) $this->price + $modifier;
}
}