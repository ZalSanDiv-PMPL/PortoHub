<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->enum('url_type', ['live_demo', 'video_tutorial', 'documentation', 'design', 'other']);
            $table->string('url');
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index('project_id', 'idx_project');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_urls');
    }
};
