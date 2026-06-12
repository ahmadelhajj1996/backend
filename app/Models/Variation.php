<?php
namespace App\Models;

use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Variation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'attributes',
        'sell_price',
        'base_price',
        'sell_rate',
        'buy_price',
        'base_buy_price',
        'buy_rate',
        'quantity',
        'sold_count',
        "group_key",
        'is_default',
        'is_active',
        'image',
        'cached_final_price',
        'cached_profit',
        'cached_profit_percentage',
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    protected $appends = [
        'image_url',
        'images_list',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductImage::class,
            'product_image_variations'
        )->withTimestamps();
    }

    public function characteristics(): BelongsToMany
    {
        return $this->belongsToMany(
            Characteristic::class,
            'variation_characteristics'
        )->withTimestamps();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? ImageHelper::url($this->image) : null;
    }

    /**
     * Highly Optimized Resolution Layer
     * Call explicitly when mapping items out, or integrate within an API Resource layer
     * to completely avoid runtime bottleneck exceptions during mass pagination.
     */
    public function resolveReadableAttributes(): array
    {
        $attributes = $this->attributes ?? [];

        if (empty($attributes)) {
            return [];
        }

        $optionIds = array_values($attributes);

        $records = DB::table('attribute_options')
            ->join('attributes', 'attributes.id', '=', 'attribute_options.attribute_id')
            ->whereIn('attribute_options.id', $optionIds)
            ->select(
                'attributes.id as attribute_id',
                'attributes.name as attribute_name',
                'attribute_options.id as option_id',
                'attribute_options.value as option_value'
            )
            ->get();

        $result = [];
        foreach ($records as $record) {
            $result[] = [
                'attribute_id'   => $record->attribute_id,
                'attribute_name' => $record->attribute_name,
                'option_id'      => $record->option_id,
                'value'          => $record->option_value,
            ];
        }

        return $result;
    }

    public function getImagesListAttribute()
    {
        if (!$this->relationLoaded('images') || !$this->images) {
            return [];
        }

        return $this->images->pluck('path_url')->toArray();
    }

}
