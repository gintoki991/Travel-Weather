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

        // 再度インスタンスを取得することで、Livewireリクエストの際も確実に取得
        $this->weatherService = app(WeatherService::class);
        $this->weatherData = $this->weatherService->fetchWeatherData($this->cityName, 'metric', 'ja', $this->selectedDate);
    }

    public function calculateWeatherIndexes()
    {
        // $this->weatherService が未設定の場合に初期化
        if (is_null($this->weatherService)) {
            $this->weatherService = app(WeatherService::class);
        }

        // 天気データが取得済みであれば指数データを計算
        if (!empty($this->weatherData['forecast'][0])) {
            $forecast = $this->weatherData['forecast'][0];
            $indexes = $this->weatherService->calculateIndexes($forecast);
            $this->weatherData['indexes'] = $indexes;
        } else {
            $this->weatherData['error'] = '指数を計算するための天気データがありません。';
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
