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
        Schema::create('growth_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitat_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_score_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 32);
            $table->string('description')->nullable();
            $table->unsignedInteger('points_before');
            $table->unsignedInteger('points_after');
            $table->integer('delta');
            $table->unsignedTinyInteger('stage_before');
            $table->unsignedTinyInteger('stage_after');
            $table->timestamp('created_at')->nullable();

            $table->index(['habitat_member_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('growth_histories');
    }
};
