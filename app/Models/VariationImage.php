<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\ImageHelper;


class VariationImage extends Model
{
    use HasFactory;

    protected $table = 'variation_images';

    protected $fillable = [
        'variation_id',
        'path',
    ];

    protected $appends = ['path_url'];


    protected $casts = [
        'variation_id' => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    public function getPathUrlAttribute()
    {
        return ImageHelper::url($this->path);
    }
}
