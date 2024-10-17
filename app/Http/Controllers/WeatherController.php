<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeatherRequest;
use App\Services\WeatherService;

class WeatherController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * 都市名で現在の天気情報と5日間の天気予報を取得するメソッド
     *
     * @param WeatherRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWeatherByCityName(WeatherRequest $request)
    {
        $units = 'metric';
        $lang = 'ja';

        $weatherData = $this->weatherService->fetchWeatherData($request->city, $units, $lang);

        if ($weatherData) {
            return response()->json($weatherData);
        } else {
            return response()->json(['error' => 'Location not found or failed to retrieve data'], 404);
        }
    }

    /**
     * 都市名と日付で天気情報と指数を取得するメソッド
     *
     * @param WeatherRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWeatherData(WeatherRequest $request)
    {
        $selectedDate = now()->toDateString(); // サーバー側で日付を設定

        $forecast = $this->weatherService->fetchWeatherData($request->city, 'metric', 'ja', $selectedDate);

        if (!$forecast) {
            return response()->json(['error' => 'Weather data not found'], 404);
        }

        // 指数を計算
        $indexes = $this->weatherService->calculateIndexes($forecast[0]);

        // レスポンス用のデータを作成
        $weatherData = [
            'forecast' => $forecast,
            'indexes' => $indexes
        ];

        return response()->json($weatherData);
    }
}
