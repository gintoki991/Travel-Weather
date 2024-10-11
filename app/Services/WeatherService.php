<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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

  // 現在の天気を取得するメソッド
  public function getCurrentWeather($latitude, $longitude)
  {
    $response = Http::get($this->currentWeatherUrl, [
      'lat' => $latitude,
      'lon' => $longitude,
      'appid' => $this->apiKey,
      'units' => 'metric', // 摂氏表示のため
    ]);

    return $response->json();
  }

  // 5日間の天気予報を取得するメソッド
  public function getForecast($latitude, $longitude)
  {
    $response = Http::get($this->forecastUrl, [
      'lat' => $latitude,
      'lon' => $longitude,
      'appid' => $this->apiKey,
      'units' => 'metric', // 摂氏表示のため
    ]);

    return $response->json();
  }
}
