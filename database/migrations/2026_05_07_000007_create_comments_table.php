<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->longText('content');
            $table->enum('comment_type', ['general', 'code_review', 'requirement', 'suggestion'])->default('general');
            $table->enum('status', ['pending', 'viewed', 'resolved'])->default('pending');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'created_at'], 'idx_project_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
