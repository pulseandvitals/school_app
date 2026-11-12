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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitat_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 32);
            $table->string('custom_type')->nullable();
            $table->date('assessed_on');
            $table->decimal('total_score', 8, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['habitat_id', 'assessed_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
