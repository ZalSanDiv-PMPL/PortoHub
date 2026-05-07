<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('github_metadata', function (Blueprint $table) {
      $table->id();
      $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
      $table->string('repo_name');
      $table->string('repo_owner');
      $table->string('repo_url');
      $table->integer('commit_count')->default(0);
      $table->timestamp('last_commit_at')->nullable();
      $table->string('last_commit_message')->nullable();
      $table->integer('commit_frequency')->default(0);
      $table->string('language')->nullable();
      $table->integer('stars')->default(0);
      $table->integer('forks')->default(0);
      $table->boolean('is_public')->default(true);
      $table->timestamp('last_synced_at')->nullable();
      $table->timestamps();

      $table->index('project_id', 'idx_project_gm');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('github_metadata');
  }
};
