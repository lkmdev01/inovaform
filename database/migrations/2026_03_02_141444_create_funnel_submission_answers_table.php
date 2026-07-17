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
        Schema::create('funnel_submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('funnel_stage_id')->constrained()->cascadeOnDelete();
            $table->string('block_id', 120)->index();
            $table->string('block_type', 40);
            $table->string('block_label', 160)->nullable();
            $table->json('value')->nullable();
            $table->timestamps();

            $table->index(
                ['funnel_submission_id', 'funnel_stage_id'],
                'funnel_submission_stage_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funnel_submission_answers');
    }
};
