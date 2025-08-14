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
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'reactions_count')) {
                $table->unsignedInteger('reactions_count')->default(0);
            }
            if (!Schema::hasColumn('posts', 'likes_count')) {
                $table->unsignedInteger('likes_count')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'reactions_count')) {
                $table->dropColumn('reactions_count');
            }
            if (Schema::hasColumn('posts', 'likes_count')) {
                $table->dropColumn('likes_count');
            }
        });
    }
};
