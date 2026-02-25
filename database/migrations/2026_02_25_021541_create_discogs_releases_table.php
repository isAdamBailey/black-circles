<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discogs_releases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discogs_id')->unique();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('label')->nullable();
            $table->string('catalog_number')->nullable();
            $table->integer('year')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('thumb')->nullable();
            $table->text('formats')->nullable();
            $table->text('genres')->nullable();
            $table->text('styles')->nullable();
            $table->text('tracklist')->nullable();
            $table->text('videos')->nullable();
            $table->decimal('lowest_price', 8, 2)->nullable();
            $table->decimal('median_price', 8, 2)->nullable();
            $table->decimal('highest_price', 8, 2)->nullable();
            $table->string('discogs_uri')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('release_data_cached_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discogs_releases');
    }
};
