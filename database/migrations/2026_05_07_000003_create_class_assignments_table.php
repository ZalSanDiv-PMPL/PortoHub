<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('class_assignments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
      $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
      $table->string('class');
      $table->integer('semester');
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      $table->unique(['teacher_id', 'student_id', 'class', 'semester'], 'unique_assignment');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('class_assignments');
  }
};
