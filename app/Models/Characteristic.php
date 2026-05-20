<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Characteristic extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'variation_id',
        'attribute',
    ];

    protected $casts = [
        'variation_id' => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

}
