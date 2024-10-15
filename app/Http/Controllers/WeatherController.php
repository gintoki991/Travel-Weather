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

    // 都市名で現在の天気情報と5日間の天気予報を取得するメソッド
    public function getWeatherByCityName($cityName, Request $request)
    {
        $units = $request->get('units', 'metric');
        $lang = $request->get('lang', 'ja');

        // WeatherServiceの共通メソッドを利用して天気データを取得
        $weatherData = $this->weatherService->fetchWeatherData($cityName, $units, $lang);

        if ($weatherData) {
            return response()->json($weatherData);
        } else {
            return response()->json(['error' => 'Location not found or failed to retrieve data'], 404);
        }
    }
}
