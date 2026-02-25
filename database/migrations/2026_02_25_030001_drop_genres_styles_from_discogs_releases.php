<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discogs_releases', function (Blueprint $table) {
            $table->dropColumn(['genres', 'styles']);
        });
    }

    public function down(): void
    {
        Schema::table('discogs_releases', function (Blueprint $table) {
            $table->text('genres')->nullable()->after('formats');
            $table->text('styles')->nullable()->after('genres');
        });
    }
};
