<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariationAttribute extends Model
{
    use HasFactory;

    protected $table = 'variation_attributes';

    protected $fillable = [
        'variation_id',
        'attribute_id',
        'attribute_option_id',
    ];

    protected $casts = [
        'variation_id'        => 'integer',
        'attribute_id'        => 'integer',
        'attribute_option_id' => 'integer',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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
        return $this->belongsTo(
            AttributeOption::class,
            'attribute_option_id'
        );
    }
}