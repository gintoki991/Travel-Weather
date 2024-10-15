<div>
    @if(isset($weatherData['error']))
    <div class="error-message">
        {{ $weatherData['error'] }}
    </div>
    @else
    <div class="weather-forecast">
        <div class="city-search">
            <input type="text" wire:model="cityName" placeholder="都市名を入力">
            <button wire:click="getWeatherData">検索</button>
        </div>
        <div class="date-navigation">
            <button wire:click="previousDay">前の日</button>
            <span>{{ isset($selectedDate) ? date('Y年m月d日', strtotime($selectedDate)) : '日付が未設定です' }}</span>
            <button wire:click="nextDay">次の日</button>
        </div>

        <!-- 行列を反転させた形式で表示 -->
        @if(isset($weatherData['forecast']) && !empty($weatherData['forecast']))
        <table>
            <thead>
                <tr>
                    <th>時刻</th>
                    @foreach ($weatherData['forecast'] as $forecast)
                    @if (date('Y-m-d', $forecast['datetime']) == date('Y-m-d', strtotime($selectedDate)))
                    <th>{{ date('H', $forecast['datetime']) }}</th>
                    @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>天気</th>
                    @foreach ($weatherData['forecast'] as $forecast)
                    @if (date('Y-m-d', $forecast['datetime']) == date('Y-m-d', strtotime($selectedDate)))
                    <td><img src="https://openweathermap.org/img/wn/{{ $forecast['icon'] }}.png" alt="天気アイコン"></td>
                    @endif
                    @endforeach
                </tr>
                <tr>
                    <th>気温</th>
                    @foreach ($weatherData['forecast'] as $forecast)
                    @if (date('Y-m-d', $forecast['datetime']) == date('Y-m-d', strtotime($selectedDate)))
                    <td>{{ $forecast['temp'] }}°C</td>
                    @endif
                    @endforeach
                </tr>
                <tr>
                    <th>降水確率</th>
                    @foreach ($weatherData['forecast'] as $forecast)
                    @if (date('Y-m-d', $forecast['datetime']) == date('Y-m-d', strtotime($selectedDate)))
                    <td>{{ $forecast['pop'] * 100 }}%</td>
                    @endif
                    @endforeach
                </tr>
                <tr>
                    <th>湿度</th>
                    @foreach ($weatherData['forecast'] as $forecast)
                    @if (date('Y-m-d', $forecast['datetime']) == date('Y-m-d', strtotime($selectedDate)))
                    <td>{{ $forecast['humidity'] }}%</td>
                    @endif
                    @endforeach
                </tr>
                <tr>
                    <th>風速</th>
                    @foreach ($weatherData['forecast'] as $forecast)
                    @if (date('Y-m-d', $forecast['datetime']) == date('Y-m-d', strtotime($selectedDate)))
                    <td>{{ $forecast['wind_speed'] }} m/s</td>
                    @endif
                    @endforeach
                </tr>
            </tbody>
        </table>
        @else
        <p>天気データが見つかりませんでした。</p>
        @endif
    </div>

    <!-- 下部セクション: 服装や傘の指数 -->
    <div class="indices">
        <h2 class="yjM">今日明日の指数情報</h2>
        <p class="yjSt">{{ date('Y年m月d日 H:i', strtotime($selectedDate)) }} 発表</p>
        <div class="tabView_content" id="index-01">
            <dl class="indexList_item">
                <dt>洗濯</dt>
                <dd>
                    <p class="index_value"><span>洗濯指数100</span></p>
                    <p class="index_text">絶好の洗濯日和</p>
                </dd>
            </dl>
        </div>
    </div>
    @endif
</div>
