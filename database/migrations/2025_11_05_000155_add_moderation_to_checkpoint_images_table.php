<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('checkpoint_images', function (Blueprint $table) {
            $table->json('moderation_results')->nullable()->after('caption');
            $table->boolean('is_flagged')->default(false)->after('moderation_results');
            $table->index('is_flagged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkpoint_images', function (Blueprint $table) {
            $table->dropColumn(['moderation_results', 'is_flagged']);
        });
    }
};
