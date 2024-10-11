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

    // 都市名で現在の天気情報を取得するメソッド
    public function getWeatherByCityName($cityName)
    {
        $coordinates = $this->weatherService->getCoordinates($cityName);

        if (!empty($coordinates)) {
            $latitude = $coordinates[0]['lat'];
            $longitude = $coordinates[0]['lon'];
            $weatherData = $this->weatherService->getCurrentWeather($latitude, $longitude);
            return response()->json($weatherData);
        } else {
            return response()->json(['error' => 'Location not found'], 404);
        }
    }

    // 都市名で5日間の天気予報を取得するメソッド
    public function getForecastByCityName($cityName)
    {
        $coordinates = $this->weatherService->getCoordinates($cityName);

        if (!empty($coordinates)) {
            $latitude = $coordinates[0]['lat'];
            $longitude = $coordinates[0]['lon'];
            $forecastData = $this->weatherService->getForecast($latitude, $longitude);
            return response()->json($forecastData);
        } else {
            return response()->json(['error' => 'Location not found'], 404);
        }
    }
}
