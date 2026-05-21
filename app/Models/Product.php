<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\ImageHelper;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description',
        'short_description', 'barcode',
        'featured_image', 'status', 'sku' ,
        'is_featured', 'is_active', 'view_count', 'sold_count',
    ];

    protected $casts = [
        'gallery'     => 'array',
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    protected $appends = ['featured_image_url'];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(Variation::class);
    }


    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('quantity', '>', 0)
                ->orWhereHas('variations', function ($sq) {
                    $sq->where('quantity', '>', 0);
                });
        });
    }

    public function getFeaturedImageUrlAttribute()
    {
        return ImageHelper::url($this->featured_image);
    }

}

// $product = Product::with([
//     'variations.attributes.attribute',
//     'variations.attributes.option',`
//     'variations.images'
// ])->find($id);