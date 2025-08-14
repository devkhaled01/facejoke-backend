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
        Schema::create('post_sample_reaction', function (Blueprint $table) {
            $table->id();
            $table->uuid('post_id');
            $table->unsignedBigInteger('sample_reaction_id');
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('sample_reaction_id')->references('id')->on('sample_reactions')->onDelete('cascade');
            $table->unique(['post_id', 'sample_reaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_sample_reaction');
    }
};
