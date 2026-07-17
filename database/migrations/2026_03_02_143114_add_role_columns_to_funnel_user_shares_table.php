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
        Schema::table('funnel_user_shares', function (Blueprint $table) {
            $table->string('role', 20)->default('viewer')->after('user_id');
            $table->foreignId('shared_by_user_id')->nullable()->after('role')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funnel_user_shares', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shared_by_user_id');
            $table->dropColumn('role');
        });
    }
};
