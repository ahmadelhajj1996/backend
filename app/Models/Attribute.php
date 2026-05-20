<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_filterable',
        'is_required',     // Add this
        'sort_order',      // Add this (if you have this column)
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'is_required' => 'boolean',    // Add this
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }
}