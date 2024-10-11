<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WeatherService;
use App\Models\WeatherForecast;

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
        $today = now()->toDateString();
        $existingWeather = WeatherForecast::where('region', $cityName)->where('date', $today)->first();

        if ($existingWeather) {
            // DBからデータを返す
            return response()->json(json_decode($existingWeather->daily_data, true));
        }

        // DBに存在しない場合、APIを呼び出してデータを保存
        $coordinates = $this->weatherService->getCoordinates($cityName);

        if (!empty($coordinates)) {
            $latitude = $coordinates[0]['lat'];
            $longitude = $coordinates[0]['lon'];

            // リクエストからオプションパラメータを取得
            $units = $request->get('units', 'metric');
            $lang = $request->get('lang', 'ja');

            $weatherData = $this->weatherService->getCurrentWeather($latitude, $longitude, $units, $lang);

            // 必要なデータのみ保存
            $this->weatherService->storeCurrentWeatherData($latitude, $longitude, $weatherData);

            return response()->json($weatherData);
        } else {
            return response()->json(['error' => 'Location not found'], 404);
        }
    }

    // 都市名で5日間の天気予報を取得するメソッド
    public function getForecastByCityName($cityName, Request $request)
    {
        $today = now()->toDateString();
        $existingForecast = WeatherForecast::where('region', $cityName)->where('date', $today)->first();

        if ($existingForecast) {
            // DBからデータを返す
            return response()->json(json_decode($existingForecast->hourly_data, true));
        }

        // DBに存在しない場合、APIを呼び出してデータを保存
        $coordinates = $this->weatherService->getCoordinates($cityName);

        if (!empty($coordinates)) {
            $latitude = $coordinates[0]['lat'];
            $longitude = $coordinates[0]['lon'];

            // リクエストからオプションパラメータを取得
            $units = $request->get('units', 'metric');
            $lang = $request->get('lang', 'ja');
            $cnt = $request->get('cnt', null);

            $forecastData = $this->weatherService->getForecast($latitude, $longitude, $units, $lang, $cnt);

            if ($forecastData) {
                // 必要なデータのみ保存
                $this->weatherService->storeForecastData($latitude, $longitude, $forecastData);

                return response()->json($forecastData);
            } else {
                return response()->json(['error' => 'Failed to retrieve forecast data'], 500);
            }
        } else {
            return response()->json(['error' => 'Location not found'], 404);
        }
    }
}
