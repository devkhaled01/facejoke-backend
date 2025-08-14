<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Post;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reactions', function (Blueprint $table) {
            if (!Schema::hasColumn('reactions', 'reactable_id')) {
                $table->uuid('reactable_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('reactions', 'reactable_type')) {
                $table->string('reactable_type')->nullable()->after('reactable_id');
            }
            if (!Schema::hasColumn('reactions', 'reactions_count')) {
                $table->unsignedInteger('reactions_count')->default(0)->after('likes_count');
            }
        });

        // Migrate existing data from post_id to the new polymorphic columns
        DB::table('reactions')->whereNotNull('post_id')->update([
            'reactable_id' => DB::raw('post_id'),
            'reactable_type' => Post::class,
        ]);

        // Add an index for the new polymorphic relationship
        Schema::table('reactions', function (Blueprint $table) {
            $table->index(['reactable_id', 'reactable_type'], 'reactions_reactable_index');
        });
    }

    public function down(): void
    {
        Schema::table('reactions', function (Blueprint $table) {
            if (Schema::hasColumn('reactions', 'reactable_id')) {
                 $table->dropIndex('reactions_reactable_index');
                 $table->dropColumn(['reactable_id', 'reactable_type', 'reactions_count']);
            }
        });
    }
};
