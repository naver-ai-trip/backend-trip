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
        // Add images column to reviews table (JSON array of image paths)
        Schema::table('reviews', function (Blueprint $table) {
            $table->json('images')->nullable()->after('comment');
            $table->json('moderation_results')->nullable()->after('images');
            $table->boolean('is_flagged')->default(false)->after('moderation_results');
            $table->index('is_flagged');
        });

        // Add images column to comments table (JSON array of image paths)
        Schema::table('comments', function (Blueprint $table) {
            $table->json('images')->nullable()->after('content');
            $table->json('moderation_results')->nullable()->after('images');
            $table->boolean('is_flagged')->default(false)->after('moderation_results');
            $table->index('is_flagged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['images', 'moderation_results', 'is_flagged']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn(['images', 'moderation_results', 'is_flagged']);
        });
    }
};
