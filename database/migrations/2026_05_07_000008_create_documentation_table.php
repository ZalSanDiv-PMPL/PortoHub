<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->enum('doc_type', ['video', 'pdf', 'image', 'spreadsheet', 'other']);
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index('project_id', 'idx_project_doc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentation');
    }
};
