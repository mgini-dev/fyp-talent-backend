<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->enum('type', ['post', 'discussion', 'announcement', 'project_update'])->default('post');
            $table->enum('visibility', ['public', 'connections', 'followers'])->default('public');
            $table->foreignId('talent_id')->nullable()->constrained('talents')->nullOnDelete();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['talent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
