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
        Schema::create('habitat_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_asset_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('growth_points')->default(0);
            $table->unsignedTinyInteger('growth_stage')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['habitat_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habitat_members');
    }
};
