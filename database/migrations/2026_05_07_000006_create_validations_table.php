<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('validations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('project_id')->constrained('projects')->onDelete('cascade')->unique();
      $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
      $table->decimal('functionality_score', 5, 2)->nullable();
      $table->decimal('code_quality_score', 5, 2)->nullable();
      $table->decimal('documentation_score', 5, 2)->nullable();
      $table->decimal('originality_score', 5, 2)->nullable();
      $table->boolean('is_approved')->default(false);
      $table->timestamp('validation_date')->nullable();
      $table->longText('notes')->nullable();
      $table->timestamps();

      $table->index(['project_id', 'teacher_id'], 'idx_project_teacher');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('validations');
  }
};
