<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class VariationAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'variation_id',
        'attribute_id',
        'attribute_option_id',
    ];

    protected $appends = [
        'name',
        'value',
        'price_modifier',
    ];

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'attribute_option_id');
    }

    public function getNameAttribute(): ?string
    {
        return $this->attribute?->name;
    }

    public function getValueAttribute(): ?string
    {
        return $this->option?->value;
    }

    public function getPriceModifierAttribute(): float
    {
        return (float) ($this->option?->price_modifier ?? 0);
    }
}