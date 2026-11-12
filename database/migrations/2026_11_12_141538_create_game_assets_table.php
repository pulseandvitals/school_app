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
        Schema::create('game_assets', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('name');
            $table->string('image_path');
            $table->unsignedInteger('asset_number');
            $table->json('stage_images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'asset_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_assets');
    }
};
