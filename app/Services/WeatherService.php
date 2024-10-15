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

    // 現在の天気と予報データを共通化し、DBに保存するメソッド
    public function fetchWeatherData($cityName, $units = 'metric', $lang = 'ja')
    {
        $today = now()->toDateString();
        $existingData = WeatherForecast::where('region', $cityName)
            ->where('date', $today)
            ->first();

        if ($existingData) {
            // DBから既存データを取得して返す
            return [
                'current' => json_decode($existingData->daily_data, true),
                'forecast' => json_decode($existingData->hourly_data, true)
            ];
        }

        // データが存在しない場合にのみAPIを叩く
        Log::info("Fetching new data from API for city: $cityName");

        // ここから先はデータが存在しない場合にのみAPIを叩いてデータを取得
        $coordinates = $this->getCoordinates($cityName);
        if (!empty($coordinates)) {
            $latitude = $coordinates[0]['lat'];
            $longitude = $coordinates[0]['lon'];

            $currentWeather = $this->getCurrentWeather($latitude, $longitude, $units, $lang);
            $forecastData = $this->getForecast($latitude, $longitude, $units, $lang);

            if ($currentWeather && $forecastData) {
                $this->storeCurrentWeatherData($cityName, $today, $currentWeather);
                $this->storeForecastData($cityName, $today, $forecastData);

                return [
                    'current' => $currentWeather,
                    'forecast' => $forecastData
                ];
            }
        }

        return null;
    }

    // 現在の天気を取得するメソッド（保存処理を削除）
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
                return [
                    'temp' => $data['main']['temp'],
                    'feels_like' => $data['main']['feels_like'],
                    'description' => $data['weather'][0]['description'],
                    'humidity' => $data['main']['humidity'],
                    'wind_speed' => $data['wind']['speed'],
                    'rain' => $data['rain']['1h'] ?? 0,
                    'sunrise' => $data['sys']['sunrise'],
                    'sunset' => $data['sys']['sunset']
                ];
            } else {
                Log::error('Current Weather API error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Current Weather API exception: ' . $e->getMessage());
            return null;
        }
    }

    // 3時間ごとの予報を取得するメソッド（保存処理を削除）
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
                        'icon' => $forecast['weather'][0]['icon'],
                        'humidity' => $forecast['main']['humidity'],
                        'wind_speed' => $forecast['wind']['speed'],
                        'pop' => $forecast['pop']
                    ];
                }
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

    // 現在の天気データ保存メソッド
    public function storeCurrentWeatherData($cityName, $date, $data)
    {
        try {
            $record = WeatherForecast::firstOrCreate(
                ['region' => $cityName, 'date' => $date],
                ['daily_data' => json_encode($data)]
            );

            // daily_dataがnullの場合のみ更新
            if (is_null($record->daily_data)) {
                $record->daily_data = json_encode($data);
                $record->save();
            }
        } catch (\Exception $e) {
            Log::error('Error storing current weather data: ' . $e->getMessage());
        }
    }

    // 予報データの保存メソッド
    public function storeForecastData($cityName, $date, $data)
    {
        try {
            $record = WeatherForecast::firstOrCreate(
                ['region' => $cityName, 'date' => $date],
                ['hourly_data' => json_encode($data)]
            );

            // hourly_dataがnullの場合のみ更新
            if (is_null($record->hourly_data)) {
                $record->hourly_data = json_encode($data);
                $record->save();
            }
        } catch (\Exception $e) {
            Log::error('Error storing forecast data: ' . $e->getMessage());
        }
    }
}
