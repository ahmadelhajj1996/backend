<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_id',
        'value',
        'color_code',
        'sort_order',
        'price_modifier',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function variationAttributes(): HasMany
    {
        return $this->hasMany(VariationAttribute::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
|--------------------------------------------------------------------------
    */

    public function getIsColorAttribute(): bool
    {
        return !is_null($this->color_code);
    }

    public function getFormattedValueAttribute(): string
    {
        return ucfirst($this->value);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->attribute?->name . ': ' . $this->value;
    }
}