<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'display_name');
            $table->string('unique_name')->nullable()->after('email');
        });

        DB::table('users')->get()->each(function ($user) {
            DB::table('users')->where('id', $user->id)
                ->update(['unique_name' => 'user_' . uniqid()]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('unique_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['unique_name']);
            $table->dropColumn('unique_name');
            $table->renameColumn('display_name', 'name');
        });
    }
};
