<?php
namespace App\Models;

use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'parent_id', 'image', 'is_active',
    ];

    protected $appends = ['image_url'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');

    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getIsMainAttribute(): bool
    {
        return is_null($this->parent_id);
    }

    public function getIsSubAttribute(): bool
    {
        return ! is_null($this->parent_id);
    }

    // Get all descendants (recursive)
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    // Get all parent categories (breadcrumb)
    public function ancestors()
    {
        $ancestors = collect();
        $parent    = $this->parent;

        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? ImageHelper::url($this->image)
            : null;
    }
}
