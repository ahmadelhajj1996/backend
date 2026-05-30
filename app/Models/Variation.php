<?php

namespace App\Models;

use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variation extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'product_id',
        'sku',

        // Sell
        'sell_price',
        'base_price',
        'sell_rate',

        // Buy
        'buy_price',
        'base_buy_price',
        'buy_rate',

        // Inventory
        'quantity',
        'sold_count',

        // Status
        'is_default',
        'is_active',
        'image',

        // Cached computed fields (IMPORTANT)
        'cached_final_price',
        'cached_profit',
        'cached_profit_percentage',
    ];

    /*
    |--------------------------------------------------------------------------
    | Type Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        // Sell
        'sell_price'     => 'decimal:1',
        'base_price'     => 'decimal:1',
        'sell_rate'      => 'decimal:2',

        // Buy
        'buy_price'      => 'decimal:1',
        'base_buy_price' => 'decimal:1',
        'buy_rate'       => 'decimal:2',

        // Inventory
        'quantity'       => 'integer',
        'sold_count'     => 'integer',

        // Status
        'is_default'     => 'boolean',
        'is_active'      => 'boolean',

        // Cached fields
        'cached_final_price'      => 'decimal:1',
        'cached_profit'           => 'decimal:1',
        'cached_profit_percentage'=> 'decimal:1',
    ];

    /*
    |--------------------------------------------------------------------------
    | Appended Attributes
    |--------------------------------------------------------------------------
    */

    protected $appends = [
        'image_url',
        'is_in_stock',

        // Pricing (FAST)
        'final_price',
        'usd_price',

        // Analytics (FAST)
        'profit',
        'profit_percentage',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(VariationAttribute::class)
            ->with(['attribute', 'option']);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VariationImage::class)
            ->orderBy('sort_order');
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
    | Accessors (NOW PURE CACHE READS ONLY)
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

    /*
    |--------------------------------------------------------------------------
    | Pricing (CACHE-ONLY HOT PATH)
    |--------------------------------------------------------------------------
    */

    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->cached_final_price ?? 0);
    }

    public function getUsdPriceAttribute(): ?float
    {
        return $this->base_price
            ? (float) round($this->base_price, 2)
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Profit (CACHE-ONLY HOT PATH)
    |--------------------------------------------------------------------------
    */

    public function getProfitAttribute(): float
    {
        return (float) ($this->cached_profit ?? 0);
    }

    public function getProfitPercentageAttribute(): float
    {
        return (float) ($this->cached_profit_percentage ?? 0);
    }
}