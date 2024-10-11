<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WeatherService;

class WeatherController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    // 現在の天気情報を取得するメソッド
    public function getCurrentWeather($latitude, $longitude)
    {
        $weatherData = $this->weatherService->getCurrentWeather($latitude, $longitude);
        return response()->json($weatherData);
    }

    // 5日間の天気予報を取得するメソッド
    public function getForecast($latitude, $longitude)
    {
        $forecastData = $this->weatherService->getForecast($latitude, $longitude);
        return response()->json($forecastData);
    }
}
