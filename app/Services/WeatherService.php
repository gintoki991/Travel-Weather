<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WeatherForecast;
use Illuminate\Support\Facades\Cache;

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

    // ユーザーが入力した都市名に近い候補を取得
    public function getCitySuggestions($cityName)
    {
        // キャッシュキーを都市名で作成
        $cacheKey = 'city_suggestions_' . strtolower($cityName);

        // キャッシュにデータがあればそれを返す
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::get("https://api.openweathermap.org/geo/1.0/direct", [
                'q' => $cityName,
                'limit' => 5,
                'appid' => $this->apiKey
            ]);

            if ($response->successful()) {
                $cities = $response->json();

                // キャッシュに都市候補を保存（1日間）
                Cache::put($cacheKey, $cities, now()->addDay());

                return $cities;
            } else {
                Log::error('Geocoding API error: ' . $response->body());
                return ['error' => 'Failed to retrieve city suggestions from API'];
            }
        } catch (\Exception $e) {
            Log::error('Geocoding API exception: ' . $e->getMessage());
            return ['error' => 'An error occurred while fetching city suggestions'];
        }
    }

    // 都市名を座標に変換するメソッド
    public function getCoordinates($cityName)
    {
        // キャッシュキーを都市名で作成
        $cacheKey = 'coordinates_' . strtolower($cityName);

        // キャッシュにデータがあればそれを返す
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::get("https://api.openweathermap.org/geo/1.0/direct", [
                'q' => $cityName,
                'limit' => 1,
                'appid' => $this->apiKey,
                'country' => 'JP'  // 日本を優先的に検索
            ]);

            if ($response->successful()) {
                $coordinates = $response->json();

                // キャッシュに座標データを保存（1日間）
                Cache::put($cacheKey, $coordinates, now()->addDay());

                return $coordinates;
            } else {
                Log::error('Geocoding API error: ' . $response->body());
                return ['error' => 'Failed to retrieve coordinates from API'];
            }
        } catch (\Exception $e) {
            Log::error('Geocoding API exception: ' . $e->getMessage());
            return ['error' => 'An error occurred while fetching coordinates'];
        }
    }

    // 天気データを取得するメソッド
    public function fetchWeatherData($cityName, $units = 'metric', $lang = 'ja', $date = null)
    {
        $fetchDate = now()->toDateString();

        // 座標を取得
        $coordinates = $this->getCoordinates($cityName);
        if (isset($coordinates['error'])) {
            // 座標取得エラー時
            return ['error' => $coordinates['error']];
        }

        if (!empty($coordinates)) {
            $latitude = $coordinates[0]['lat'];
            $longitude = $coordinates[0]['lon'];

            // 実際に取得された地点名
            $actualCityName = $coordinates[0]['name'] . ', ' . $coordinates[0]['country'];

            // DB検索時に取得された実際の都市名を使用する
            $existingData = WeatherForecast::where('region', $actualCityName)
                ->where('date', $date ?? $fetchDate)
                ->first();

            if ($existingData) {
                return [
                    'current' => json_decode($existingData->daily_data, true),
                    'forecast' => json_decode($existingData->hourly_data, true),
                    'region' => $actualCityName // 実際の地点名を返す
                ];
            }

            $currentWeather = $this->getCurrentWeather($latitude, $longitude, $units, $lang);
            $forecastData = $this->getForecast($latitude, $longitude, $units, $lang);

            if (isset($currentWeather['error']) || isset($forecastData['error'])) {
                return ['error' => 'Failed to retrieve weather data.'];
            }

            if ($currentWeather && $forecastData) {
                // 実際の都市名で保存
                $this->storeCurrentWeatherData($actualCityName, $fetchDate, $currentWeather);
                $this->storeForecastData($actualCityName, $fetchDate, $forecastData);

                return [
                    'current' => $currentWeather,
                    'forecast' => $forecastData,
                    'region' => $actualCityName // 実際の地点名を返す
                ];
            }
        }

        return ['error' => 'Failed to retrieve weather data.'];
    }

    // 現在の天気データ取得メソッド
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
                return ['error' => 'Failed to retrieve current weather data'];
            }
        } catch (\Exception $e) {
            Log::error('Current Weather API exception: ' . $e->getMessage());
            return ['error' => 'An error occurred while fetching current weather data'];
        }
    }

    // 3時間ごとの予報を取得するメソッド
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
                return ['error' => 'Failed to retrieve weather forecast data'];
            }
        } catch (\Exception $e) {
            Log::error('Forecast API exception: ' . $e->getMessage());
            return ['error' => 'An error occurred while fetching weather forecast data'];
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

    public function calculateIndexes($forecast)
    {
        $umbrellaIndex = $forecast['pop'] * 100;
        // 降水確率に応じたメッセージ
        if ($umbrellaIndex <= 15) {
            $umbrellaText = '傘はなくて大丈夫';
        } elseif ($umbrellaIndex <= 49) {
            $umbrellaText = '傘があると安心';
        } else {
            $umbrellaText = '傘を持っておこう';
        }

        $feelsLike = $forecast['feels_like'];
        if ($feelsLike < 15) {
            $clothesText = 'コートが必要';
            $clothesClass = 'cold';
        } elseif ($feelsLike < 25) {
            $clothesText = 'シャツや薄手のジャケット';
            $clothesClass = 'mild';
        } else {
            $clothesText = '軽装で大丈夫';
            $clothesClass = 'hot';
        }

        return [
            'umbrella' => ['index' => $umbrellaIndex, 'text' => $umbrellaText],
            'clothes' => ['text' => $clothesText, 'class' => $clothesClass],
        ];
    }
}
