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
        'is_required',     
        'sort_order',      
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'is_required'   => 'boolean',    
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }
}