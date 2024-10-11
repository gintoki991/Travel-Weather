<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
  protected $apiKey;
  protected $currentWeatherUrl;
  protected $forecastUrl;

  public function __construct()
  {
    $this->apiKey = config('services.openweather.api_key');
    $this->currentWeatherUrl = 'https://api.openweathermap.org/data/2.5/weather';
    $this->forecastUrl = 'https://api.openweathermap.org/data/2.5/forecast';
  }

  //都市名を座標に変換するメソッド
  public function getCoordinates($cityName)
  {
    $response = Http::get("https://api.openweathermap.org/geo/1.0/direct", [
      'q' => $cityName,
      'limit' => 1,
      'appid' => $this->apiKey
    ]);

    return $response->json();
  }

  // 現在の天気を取得するメソッド
  public function getCurrentWeather($latitude, $longitude, $units = 'metric', $lang = 'ja')
  {
    $response = Http::get($this->currentWeatherUrl, [
      'lat' => $latitude,
      'lon' => $longitude,
      'appid' => $this->apiKey,
      'units' => $units,
      'lang' => $lang,
    ]);

    return $response->json();
  }

  // 5日間の天気予報を取得するメソッド
  public function getForecast($latitude, $longitude, $units = 'metric', $lang = 'ja', $cnt = null)
  {
    try {
      $params = [
        'lat' => $latitude,
        'lon' => $longitude,
        'appid' => $this->apiKey,
        'units' => $units,
        'lang' => $lang,
      ];

      if ($cnt) {
        $params['cnt'] = $cnt;
      }

      $response = Http::get($this->forecastUrl, $params);

      if ($response->successful()) {
        return $response->json();
      } else {
        // エラーレスポンスの内容をログに出力
        Log::error('Forecast API error: ' . $response->body());
        return null;
      }
    } catch (\Exception $e) {
      Log::error('Forecast API exception: ' . $e->getMessage());
      return null;
    }
  }
}
