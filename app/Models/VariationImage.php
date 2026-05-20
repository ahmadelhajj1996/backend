<?php

namespace App\Models;

use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariationImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'variation_id',
        'path',
        'sort_order',
    ];

    protected $appends = [
        'path_url',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

    public function getPathUrlAttribute(): ?string
    {
        return ImageHelper::url($this->path);
    }
}