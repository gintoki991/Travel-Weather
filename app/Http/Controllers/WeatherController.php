<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WeatherService;

class WeatherController extends Controller
{
    protected $weatherService;
    protected $cityName;
    protected $selectedDate;
    protected $weatherData;

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

    // 都市名と日付で天気情報と指数を取得するメソッド
    public function getWeatherData(Request $request)
    {
        $cityName = $request->get('city');
        $selectedDate = $request->get('date', now()->toDateString()); // 日付がなければ今日の日付

        if (empty($cityName)) {
            return response()->json(['error' => 'City name is required'], 400);
        }

        // WeatherServiceを使って天気データを取得
        $forecast = $this->weatherService->fetchWeatherData($cityName, 'metric', 'ja', $selectedDate);

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

        // JSONでレスポンスを返す
        return response()->json($weatherData);
    }
}
