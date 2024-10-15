<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;


Route::get('/', function () {
    return view('weather-forecast');
});

// 都市名で現在の天気情報を取得するエンドポイント
Route::get('/weather/current/city/{cityName}', [WeatherController::class, 'getWeatherByCityName']);

// 都市名で5日間の天気予報を取得するエンドポイント
Route::get('/weather/forecast/city/{cityName}', [WeatherController::class, 'getForecastByCityName']);
