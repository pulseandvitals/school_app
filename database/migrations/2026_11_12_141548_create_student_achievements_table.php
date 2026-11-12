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
        Schema::create('student_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitat_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_score_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('awarded_at');

            $table->unique(['habitat_member_id', 'achievement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
    }
};
