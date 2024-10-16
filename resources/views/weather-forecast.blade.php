<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>天気予報アプリ</title>

  @livewireStyles
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
  <!-- Livewireコンポーネント -->
  <livewire:weather-forecast />
  @livewireScripts
</body>

</html>
