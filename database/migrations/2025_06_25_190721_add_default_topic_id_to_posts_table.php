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
            $table->uuid('topic_id')->default('bc096ffc-dfe0-4c73-a28a-837b9040d19a')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->uuid('topic_id')->default(null)->change(); // or remove default
        });
    }
};
