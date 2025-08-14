<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apply the migration: Add `type` column to the `posts` table.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Add a new 'type' column as a nullable string (or text, if you prefer)
            $table->string('type')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migration: Remove the `type` column if the migration is rolled back.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Remove the 'type' column
            $table->dropColumn('type');
        });
    }
};
