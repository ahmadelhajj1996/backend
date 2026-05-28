<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'previous_rate',
        'change_amount',
        'change_percentage',
    ];

    protected $casts = [
        'rate'              => 'float',
        'previous_rate'     => 'float',
        'change_amount'     => 'float',
        'change_percentage' => 'float',
    ];

}
