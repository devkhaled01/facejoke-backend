<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('android_min_version')->nullable();
            $table->string('ios_min_version')->nullable();
            $table->boolean('is_app_working')->default(true);
            $table->string('down_message')->nullable();
            $table->string('android_store_url')->nullable();
            $table->string('ios_store_url')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_settings');
    }
}; 