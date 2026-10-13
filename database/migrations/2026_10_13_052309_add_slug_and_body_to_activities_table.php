<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Each activity now has its own detail page: a URL slug and an optional article body.
 * Existing activities get a slug from their title and an empty body.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('id');
            $table->json('body')->nullable()->after('schedule');
        });

        $taken = [];

        foreach (DB::table('activities')->orderBy('id')->get(['id', 'title']) as $activity) {
            $baseSlug = Str::limit(Str::slug($activity->title), 200, '') ?: 'kegiatan';
            $slug = $baseSlug;

            for ($suffix = 2; in_array($slug, $taken, true); $suffix++) {
                $slug = "{$baseSlug}-{$suffix}";
            }

            $taken[] = $slug;
            DB::table('activities')->where('id', $activity->id)->update(['slug' => $slug, 'body' => '[]']);
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'body']);
        });
    }
};
