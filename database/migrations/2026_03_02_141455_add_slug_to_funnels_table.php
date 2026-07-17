<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('funnels', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name')->unique();
        });

        DB::table('funnels')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function ($funnel): void {
                $base = Str::slug((string) $funnel->name);

                if ($base === '') {
                    $base = 'funnel';
                }

                $slug = $base;
                $suffix = 1;

                while (DB::table('funnels')->where('slug', $slug)->exists()) {
                    $suffix++;
                    $slug = "{$base}-{$suffix}";
                }

                DB::table('funnels')->where('id', $funnel->id)->update(['slug' => $slug]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funnels', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
