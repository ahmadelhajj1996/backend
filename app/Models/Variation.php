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

    protected $fillable = [
        'product_id', 'sku', 'price', 'quantity', 'is_default', 'sold_count',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'is_default' => 'boolean',
        'quantity'   => 'integer',
    ];

    // protected $appends = ['image_url'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes()
    {
        return $this->hasMany(VariationAttribute::class);
    }

    public function images()
    {
        return $this->hasMany(VariationImage::class);

    }
    public function characteristics()
    {
        return $this->hasMany(Characteristic::class);

    }
    

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // public function attributeOptions(): BelongsToMany
    // {
    //     return $this->belongsToMany(AttributeOption::class, 'variation_attributes')
    //                 ->withTimestamps();
    // }
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->quantity > 0;
    }

    public function getImageUrlAttribute()
    {
        return ImageHelper::url($this->image);
    }
}
