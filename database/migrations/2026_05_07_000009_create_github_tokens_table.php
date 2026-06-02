<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->longText('access_token');
            $table->longText('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('scope')->nullable();
            $table->bigInteger('github_id')->nullable();
            $table->string('github_username')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id', 'idx_user_github');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_tokens');
    }
};
