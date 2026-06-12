<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Helpers\ImageHelper;


class ProductImage extends Model
{
    protected $fillable = ['path', 'hash' , 'sort_order'];

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(Variation::class, 'product_image_variations');
    }

    protected $appends = [
        'path_url',
    ];

    public function getPathUrlAttribute(): ?string
    {
        return ImageHelper::url($this->path);
    }
}
