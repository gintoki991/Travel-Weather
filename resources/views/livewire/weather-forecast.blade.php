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
        <!-- 表形式で3時間ごとのデータを表示 -->
        <table>
            <thead>
                <tr>
                    <th>時刻</th>
                    <th>天気</th>
                    <th>気温</th>
                    <th>降水確率</th>
                    <th>湿度</th>
                    <th>風速</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($weatherData['forecast']) && !empty($weatherData['forecast']))
                // データがある場合に表示
                @foreach ($weatherData['forecast'] as $forecast)
                <tr>
                    <td>{{ date('H:i', $forecast['datetime']) }}</td>
                    <td><img src="https://openweathermap.org/img/wn/{{ $forecast['icon'] }}.png" alt="天気アイコン">{{ $forecast['description'] }}</td>
                    <td>{{ $forecast['temp'] }}°C</td>
                    <td>{{ $forecast['pop'] * 100 }}%</td>
                    <td>{{ $forecast['humidity'] }}%</td>
                    <td>{{ $forecast['wind_speed'] }} m/s</td>
                </tr>
                @endforeach
                @else
                <p>天気データが見つかりませんでした。</p>
                @endif
            </tbody>
        </table>
    </div>

    <!-- 下部セクション: 服装や傘の指数 -->
    <div class="indices">
        <!-- 指数情報表示 -->
        <h2 class="yjM">今日明日の指数情報</h2>
        <p class="yjSt">{{ date('Y年m月d日 H:i', strtotime($selectedDate)) }} 発表</p>
        <div class="tabView_content" id="index-01">
            <!-- 各指数情報 -->
            <dl class="indexList_item">
                <dt>洗濯</dt>
                <dd>
                    <p class="index_value"><span>洗濯指数100</span></p>
                    <p class="index_text">絶好の洗濯日和</p>
                </dd>
            </dl>
            <!-- 他の指数情報を追加 -->
        </div>
    </div>
    @endif
</div>
