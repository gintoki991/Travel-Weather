<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::create('weather_forecasts', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->date('date');
            $table->json('hourly_data')->nullable(); // 3時間ごとの予報データ
            $table->json('daily_data')->nullable();// 現在の天気データ
            $table->timestamps();

            // 複合ユニークキー
            $table->unique(['region', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_forecasts');
    }
};
