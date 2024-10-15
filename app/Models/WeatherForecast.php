<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'region',
        'date',
        'hourly_data',
        'daily_data',
    ];

    // hourly_data と daily_data は JSON フィールドとしてキャスト
    protected $casts = [
        'hourly_data' => 'array',
        'daily_data' => 'array',
    ];
}
