<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discogs_collection_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instance_id')->unique();
            $table->unsignedBigInteger('discogs_release_id');
            $table->unsignedInteger('folder_id')->nullable();
            $table->integer('rating')->nullable();
            $table->text('notes')->nullable(); // JSON array: [{"field_id": int, "value": "string"}]
            $table->timestamp('date_added')->nullable();
            $table->timestamps();

            $table->foreign('discogs_release_id')->references('discogs_id')->on('discogs_releases')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discogs_collection_items');
    }
};
