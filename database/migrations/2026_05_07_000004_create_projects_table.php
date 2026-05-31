<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->enum('development_model', ['waterfall', 'agile', 'other'])->default('waterfall');
            $table->string('github_url')->nullable();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'archived'])->default('draft');
            $table->timestamp('submission_date')->nullable();
            $table->timestamp('approval_date')->nullable();
            $table->longText('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status'], 'idx_student_status');
            $table->index('submission_date', 'idx_submission_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
