<div class="weather-container">
    @if(isset($weatherData['error']))
    <div class="error-message">
        {{ $weatherData['error'] }}
    </div>
    @else
    <div class="weather-forecast">
        <div class="city-search">
            <label for="cityNameInput">都市名</label>
            <input id="cityNameInput"
                type="text"
                wire:model.debounce.500ms="cityName"
                list="citySuggestionsList"
                placeholder="都市名を入力してください"
                class="city-input"

                <!-- datalistを使ってサジェストリストを表示 -->
            <datalist id="citySuggestionsList">
                @foreach($citySuggestions as $suggestion)
                <option value="{{ $suggestion }}"></option>
                @endforeach
            </datalist>

            <!-- バリデーションエラーメッセージの表示 -->
            @error('cityName')
            <div class="error-message">{{ $message }}</div>
            @enderror

            <!-- サジェストがない場合のメッセージ -->
            @if(strlen($cityName) > 2 && count($citySuggestions) === 0)
            <div class="no-suggestions">選択できる都市名がありません。この都市名では検索できません。</div>
            @endif

            <!-- 検索ボタン -->
            <button wire:click="getWeatherData" class="search-button">検索</button>

            <!-- 実際に選択された地点名を表示 -->
            <h2>{{ $cityName }}</h2>
        </div>

        <div class="date-navigation">
            <button wire:click="previousDay">前の日</button>
            <span>{{ isset($selectedDate) ? date('Y年m月d日', strtotime($selectedDate)) : '日付が未設定です' }}</span>
            <button wire:click="nextDay">次の日</button>
        </div>

        <!-- テーブル形式で表示 -->
        @if(isset($weatherData['forecast']) && !empty($weatherData['forecast']))
        <div class="forecast-table">
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
                        <td>{{ round($forecast['temp']) }}°C</td>
                        @endif
                        @endforeach
                    </tr>
                    <tr>
                        <th>降水確率</th>
                        @foreach ($weatherData['forecast'] as $forecast)
                        @if (date('Y-m-d', $forecast['datetime']) == date('Y-m-d', strtotime($selectedDate)))
                        <td>{{ round($forecast['pop'] * 100) }}%</td>
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
                        <td>{{ round($forecast['wind_speed']) }} m/s</td>
                        @endif
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
        @else
        <p>天気データが見つかりませんでした。</p>
        @endif
    </div>

    <!-- 下部セクション: 服装や傘の指数 -->
    <div class="indices">
        <h2 class="yjM">持ち物</h2>
        <!-- <p class="yjSt">{{ date('Y年m月d日', strtotime($selectedDate)) }}</p> -->
        <div class="tabView_content" id="index-01">
            <!-- 傘指数 -->
            <dl class="indexList_item">
                <dt>傘：</dt>
                <dd>
                    @if(!empty($weatherData['indexes']) && isset($weatherData['indexes']['umbrella']))
                    <!-- <p class="index_value">
                        <span class="{{ $weatherData['indexes']['umbrella']['index'] > 50 ? 'high' : 'low' }}">
                            傘指数: {{ round($weatherData['indexes']['umbrella']['index']) }} /100
                        </span>
                    </p> -->
                    <p class="index_text">{{ $weatherData['indexes']['umbrella']['text'] }}</p>
                    @else
                    <p class="index_text">傘に関するデータがありません。</p>
                    @endif
                </dd>
            </dl>
            <!-- 服装指数 -->
            <dl class="indexList_item">
                <dt>服装：</dt>
                <dd>
                    @if(!empty($weatherData['indexes']) && isset($weatherData['indexes']['clothes']))
                    <!-- <p class="index_value">
                        <span class="{{ $weatherData['indexes']['clothes']['class'] }}">服装指数</span>
                    </p> -->
                    <p class="index_text">{{ $weatherData['indexes']['clothes']['text'] }}</p>
                    @else
                    <p class="index_text">服装に関するデータがありません。</p>
                    @endif
                </dd>
            </dl>
        </div>
    </div>
    @endif
</div>

<!-- <script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('cityNameInput');
        input.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === 'Backspace') {
            }
        });
    });
</script> -->
