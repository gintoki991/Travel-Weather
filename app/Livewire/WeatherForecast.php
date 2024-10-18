<?php

namespace App\Livewire;

use Livewire\Component;
use App\Http\Requests\WeatherRequest;
use App\Services\WeatherService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherForecast extends Component
{
    public $cityName = '';
    public $weatherData = [];
    public $selectedDate;
    public $citySuggestions = [];

    protected $weatherService;

    // バリデーションルールとメッセージの定義
    protected $rules = [
        'cityName' => 'required|string',
    ];

    protected $messages = [
        'cityName.required' => '都市名は必須です。',
        'cityName.string' => '都市名は文字列で入力してください。',
    ];

    // コンストラクタで WeatherService を初期化せずに、必要なときにインスタンスを取得
    public function mount()
    {
        $this->selectedDate = now()->toDateString();
        $this->weatherService = app(WeatherService::class);
        $this->getWeatherData();
    }

    public function updatedCityName()
    {
        if (strlen($this->cityName) < 2) {
            $this->citySuggestions = [];
            return;
        }

        try {
            $response = Http::get("https://api.openweathermap.org/geo/1.0/direct", [
                'q' => $this->cityName,
                'limit' => 5,
                'appid' => config('services.openweather.api_key')
            ]);

            if ($response->successful()) {
                $cities = $response->json();
                $uniqueCities = [];

                foreach ($cities as $city) {
                    $key = strtolower($city['name']) . ',' . ($city['country'] ?? '');
                    if (!array_key_exists($key, $uniqueCities)) {
                        $uniqueCities[$key] = $city['name'] . ', ' . $city['country'];
                    }
                }

                $this->citySuggestions = array_values($uniqueCities);
            } else {
                $this->citySuggestions = [];
            }
        } catch (\Exception $e) {
            $this->citySuggestions = [];
        }
    }

    public function setCityName($city)
    {
        $this->cityName = $city;
        $this->citySuggestions = []; // サジェストリストをクリア
    }

    public function getWeatherData()
    {
        // バリデーションを実行
        $this->validate();

        try {
            // WeatherService のインスタンスを取得
            $this->weatherService = app(WeatherService::class);
            // 天気データを取得
            $this->weatherData = $this->weatherService->fetchWeatherData($this->cityName, 'metric', 'ja', $this->selectedDate);
            // 日付ごとの指数を計算
            $this->calculateWeatherIndexes();
        } catch (\Exception $e) {
            // エラーメッセージを設定
            $this->weatherData = ['error' => '天気情報の取得に失敗しました。都市名を確認して再入力してください。'];
            Log::error('Error fetching weather data: ' . $e->getMessage());
        }
    }

    public function calculateWeatherIndexes()
    {
        // weatherService が null の場合にインスタンスを取得
        if (is_null($this->weatherService)) {
            $this->weatherService = app(WeatherService::class);
        }

        // weatherData が空の場合や 'forecast' キーが存在しない場合のチェック
        if (empty($this->weatherData) || !isset($this->weatherData['forecast'])) {
            $this->weatherData['indexes'] = ['error' => '天気データがありません。都市名を確認して再度検索してください。'];
            return;
        }

        // 選択された日付のデータをフィルタリング
        $forecastForDay = array_filter($this->weatherData['forecast'], function ($forecast) {
            return date('Y-m-d', $forecast['datetime']) == $this->selectedDate;
        });

        if (!empty($forecastForDay)) {
            // 集計用の初期化
            $totalFeelsLike = 0;
            $totalPop = 0;
            $count = count($forecastForDay);

            // 日付内の予報データの合計を計算
            foreach ($forecastForDay as $forecast) {
                $totalFeelsLike += $forecast['feels_like'];
                $totalPop += $forecast['pop'];
            }

            // 平均を計算
            $averageFeelsLike = $totalFeelsLike / $count;
            $averagePop = ($totalPop / $count);

            // 集計されたデータを元に指数を計算
            $indexes = $this->weatherService->calculateIndexes([
                'feels_like' => $averageFeelsLike,
                'pop' => $averagePop
            ]);

            $this->weatherData['indexes'] = $indexes;
        } else {
            $this->weatherData['indexes'] = ['error' => '指数データが見つかりませんでした。'];
        }
    }

    public function previousDay()
    {
        $this->selectedDate = date('Y-m-d', strtotime($this->selectedDate . ' -1 day'));
        $this->calculateWeatherIndexes();
    }

    public function nextDay()
    {
        $this->selectedDate = date('Y-m-d', strtotime($this->selectedDate . ' +1 day'));
        $this->calculateWeatherIndexes();
    }

    public function render()
    {
        return view('livewire.weather-forecast', [
            'weatherData' => $this->weatherData,
            'selectedDate' => $this->selectedDate,
            'citySuggestions' => $this->citySuggestions,
        ]);
    }
}
