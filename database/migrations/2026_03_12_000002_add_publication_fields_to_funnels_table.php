<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funnels', function (Blueprint $table): void {
            $table->string('custom_domain')->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('funnels', function (Blueprint $table): void {
            $table->dropUnique(['custom_domain']);
            $table->dropColumn('custom_domain');
        });
    }
};
