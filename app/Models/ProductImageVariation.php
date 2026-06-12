<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImageVariation extends Model
{
    protected $table = 'product_image_variations';

    protected $fillable = [
        'product_image_id',
        'variation_id',
    ];

    public function productImage(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }
}