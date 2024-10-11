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
    public function getWeatherByCityName($cityName, Request $request)
    {
        $coordinates = $this->weatherService->getCoordinates($cityName);

        if (!empty($coordinates)) {
            $latitude = $coordinates[0]['lat'];
            $longitude = $coordinates[0]['lon'];

            // リクエストからオプションパラメータを取得（デフォルトは摂氏と日本語）
            $units = $request->get('units', 'metric');
            $lang = $request->get('lang', 'ja');

            $weatherData = $this->weatherService->getCurrentWeather($latitude,$longitude, $units, $lang);

            return response()->json($weatherData);
        } else {
            return response()->json(['error' => 'Location not found'], 404);
        }
    }

    // 都市名で5日間の天気予報を取得するメソッド
    public function getForecastByCityName($cityName, Request $request)
    {
        $coordinates = $this->weatherService->getCoordinates($cityName);

        if (!empty($coordinates)) {
            $latitude = $coordinates[0]['lat'];
            $longitude = $coordinates[0]['lon'];

            $units = $request->get('units', 'metric');
            $lang = $request->get('lang', 'ja');
            $cnt = $request->get('cnt', null);

            $forecastData = $this->weatherService->getForecast($latitude, $longitude, $units, $lang, $cnt);

            if ($forecastData) {
                return response()->json($forecastData);
            } else {
                return response()->json(['error' => 'Failed to retrieve forecast data'], 500);
            }
        } else {
            return response()->json(['error' => 'Location not found'], 404);
        }
    }
}
