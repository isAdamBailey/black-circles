<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('vibe_intent_exclude_styles');
        Schema::dropIfExists('vibe_intent_include_styles');
        Schema::dropIfExists('vibe_intent_keywords');
        Schema::dropIfExists('vibe_intents');
    }

    public function down(): void
    {
        Schema::create('vibe_intents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('vibe_intent_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vibe_intent_id')->constrained()->onDelete('cascade');
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::create('vibe_intent_include_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vibe_intent_id')->constrained()->onDelete('cascade');
            $table->string('style_name');
            $table->timestamps();
        });

        Schema::create('vibe_intent_exclude_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vibe_intent_id')->constrained()->onDelete('cascade');
            $table->string('style_name');
            $table->timestamps();
        });
    }
};
