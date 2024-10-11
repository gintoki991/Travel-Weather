<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WeatherForecast;

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

  // 都市名を座標に変換するメソッド
  public function getCoordinates($cityName)
  {
    try {
      $response = Http::get("https://api.openweathermap.org/geo/1.0/direct", [
        'q' => $cityName,
        'limit' => 1,
        'appid' => $this->apiKey
      ]);

      if ($response->successful()) {
        return $response->json();
      } else {
        Log::error('Geocoding API error: ' . $response->body());
        return null;
      }
    } catch (\Exception $e) {
      Log::error('Geocoding API exception: ' . $e->getMessage());
      return null;
    }
  }

  // 現在の天気を取得し、必要なデータだけを抽出して保存するメソッド
  public function getCurrentWeather($latitude, $longitude, $units = 'metric', $lang = 'ja')
  {
    try {
      $response = Http::get($this->currentWeatherUrl, [
        'lat' => $latitude,
        'lon' => $longitude,
        'appid' => $this->apiKey,
        'units' => $units,
        'lang' => $lang
      ]);

      if ($response->successful()) {
        $data = $response->json();
        $selectedData = [
          'temp' => $data['main']['temp'],
          'feels_like' => $data['main']['feels_like'],
          'description' => $data['weather'][0]['description'],
          'humidity' => $data['main']['humidity'],
          'wind_speed' => $data['wind']['speed'],
          'rain' => $data['rain']['1h'] ?? 0,
          'sunrise' => $data['sys']['sunrise'],
          'sunset' => $data['sys']['sunset']
        ];

        // 保存処理
        $this->storeCurrentWeatherData($latitude, $longitude, $selectedData);

        return $selectedData;
      } else {
        Log::error('Current Weather API error: ' . $response->body());
        return null;
      }
    } catch (\Exception $e) {
      Log::error('Current Weather API exception: ' . $e->getMessage());
      return null;
    }
  }

  // 3時間ごとの予報を取得し、必要なデータだけを抽出して保存するメソッド
  public function getForecast($latitude, $longitude, $units = 'metric', $lang = 'ja')
  {
    try {
      $response = Http::get($this->forecastUrl, [
        'lat' => $latitude,
        'lon' => $longitude,
        'appid' => $this->apiKey,
        'units' => $units,
        'lang' => $lang
      ]);

      if ($response->successful()) {
        $data = $response->json();
        $forecastData = [];
        foreach ($data['list'] as $forecast) {
          $forecastData[] = [
            'datetime' => $forecast['dt'],
            'temp' => $forecast['main']['temp'],
            'feels_like' => $forecast['main']['feels_like'],
            'temp_min' => $forecast['main']['temp_min'],
            'temp_max' => $forecast['main']['temp_max'],
            'description' => $forecast['weather'][0]['description'],
            'humidity' => $forecast['main']['humidity'],
            'wind_speed' => $forecast['wind']['speed'],
            'pop' => $forecast['pop']
          ];
        }

        // 保存処理
        $this->storeForecastData($latitude, $longitude, $forecastData);

        return $forecastData;
      } else {
        Log::error('Forecast API error: ' . $response->body());
        return null;
      }
    } catch (\Exception $e) {
      Log::error('Forecast API exception: ' . $e->getMessage());
      return null;
    }
  }

  // 現在の天気データを保存するメソッド
  protected function storeCurrentWeatherData($latitude, $longitude, $data)
  {
    try {
      WeatherForecast::updateOrCreate(
        ['region' => "$latitude,$longitude", 'date' => now()->toDateString()],
        ['hourly_data' => json_encode($data)]
      );
    } catch (\Exception $e) {
      Log::error('Error storing current weather data: ' . $e->getMessage());
    }
  }

  // 3時間ごとの予報データを保存するメソッド
  protected function storeForecastData($latitude, $longitude, $data)
  {
    try {
      WeatherForecast::updateOrCreate(
        ['region' => "$latitude,$longitude", 'date' => now()->toDateString()],
        ['daily_data' => json_encode($data)]
      );
    } catch (\Exception $e) {
      Log::error('Error storing forecast data: ' . $e->getMessage());
    }
  }
}
