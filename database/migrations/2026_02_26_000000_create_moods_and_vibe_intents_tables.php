<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moods', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->string('emoji', 10);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('mood_genres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mood_id')->constrained()->onDelete('cascade');
            $table->string('genre_name');
            $table->timestamps();
        });

        Schema::create('mood_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mood_id')->constrained()->onDelete('cascade');
            $table->string('style_name');
            $table->timestamps();
        });

        Schema::create('mood_exclude_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mood_id')->constrained()->onDelete('cascade');
            $table->string('style_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mood_exclude_styles');
        Schema::dropIfExists('mood_styles');
        Schema::dropIfExists('mood_genres');
        Schema::dropIfExists('moods');
    }
};
