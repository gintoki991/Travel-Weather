<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\WeatherService;

class WeatherForecast extends Component
{
    public $cityName = '東京';
    public $weatherData = [];
    public $selectedDate;
    protected $weatherService;

    // コンストラクタで WeatherService を初期化せずに、必要なときにインスタンスを取得
    public function mount()
    {
        $this->selectedDate = now()->toDateString();
        $this->weatherService = app(WeatherService::class);
        $this->getWeatherData();
    }

    public function getWeatherData()
    {
        if (empty($this->cityName)) {
            $this->weatherData = ['error' => '都市名が入力されていません。'];
            return;
        }

        try {
            // 再度インスタンスを取得することで、Livewireリクエストの際も確実に取得
            $this->weatherService = app(WeatherService::class);
            $this->weatherData = $this->weatherService->fetchWeatherData($this->cityName, 'metric', 'ja', $this->selectedDate);

            // 日付ごとの指数を計算
            $this->calculateWeatherIndexes();
        } catch (\Exception $e) {
            // エラーメッセージを設定
            $this->weatherData = ['error' => '天気情報の取得に失敗しました。都市名を確認して再入力してください。'];
        }
        // 日付ごとの指数を計算
        $this->calculateWeatherIndexes();
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
        ]);
    }
}
