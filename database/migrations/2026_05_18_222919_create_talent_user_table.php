<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('talent_id')->constrained('talents')->onDelete('cascade');
            $table->string('proficiency')->default('Beginner'); // Beginner, Intermediate, Expert
            $table->string('portfolio_url')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'talent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_user');
    }
};
