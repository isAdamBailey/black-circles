<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('styles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('discogs_release_genre', function (Blueprint $table) {
            $table->foreignId('discogs_release_id')->constrained('discogs_releases')->onDelete('cascade');
            $table->foreignId('genre_id')->constrained('genres')->onDelete('cascade');
            $table->primary(['discogs_release_id', 'genre_id']);
        });

        Schema::create('discogs_release_style', function (Blueprint $table) {
            $table->foreignId('discogs_release_id')->constrained('discogs_releases')->onDelete('cascade');
            $table->foreignId('style_id')->constrained('styles')->onDelete('cascade');
            $table->primary(['discogs_release_id', 'style_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discogs_release_style');
        Schema::dropIfExists('discogs_release_genre');
        Schema::dropIfExists('styles');
        Schema::dropIfExists('genres');
    }
};
