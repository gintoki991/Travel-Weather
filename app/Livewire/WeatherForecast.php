<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\WeatherService;

class WeatherForecast extends Component
{
    public $cityName = 'Tokyo';
    public $weatherData = [];
    public $selectedDate;
    protected $weatherService;

    // コンストラクタで WeatherService を初期化せずに、必要なときにインスタンスを取得
    public function mount()
    {
        $this->selectedDate = now()->toDateString();
        $this->weatherService = app(WeatherService::class); // app() ヘルパーを使って取得
        $this->getWeatherData();
    }

    public function getWeatherData()
    {
        if (empty($this->cityName)) {
            return;
        }

        // 再度インスタンスを取得することで、Livewireリクエストの際も確実に取得
        $this->weatherService = app(WeatherService::class);
        $this->weatherData = $this->weatherService->fetchWeatherData($this->cityName, 'metric', 'ja', $this->selectedDate);
    }

    public function previousDay()
    {
        $this->selectedDate = date('Y-m-d', strtotime($this->selectedDate . ' -1 day'));
    }

    public function nextDay()
    {
        $this->selectedDate = date('Y-m-d', strtotime($this->selectedDate . ' +1 day'));
    }

    public function render()
    {
        return view('livewire.weather-forecast', [
            'weatherData' => $this->weatherData,
            'selectedDate' => $this->selectedDate,
        ]);
    }
}
