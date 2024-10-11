<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;


Route::get('/', function () {
    return view('welcome');
});

// 現在の天気情報を取得するエンドポイント
Route::get('/weather/current/{latitude}/{longitude}', [WeatherController::class, 'getCurrentWeather']);

// 5日間の天気予報を取得するエンドポイント
Route::get('/weather/forecast/{latitude}/{longitude}', [WeatherController::class, 'getForecast']);
