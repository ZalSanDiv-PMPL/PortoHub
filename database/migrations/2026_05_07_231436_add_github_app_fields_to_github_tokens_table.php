<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('github_tokens', function (Blueprint $table) {
            $table->string('installation_id')->nullable()->comment('GitHub App installation ID');
            $table->string('token_type')->default('bearer')->comment('bearer, etc');
            $table->unsignedInteger('expires_in')->nullable()->comment('seconds');
            $table->timestamp('refreshed_at')->nullable()->comment('last refresh time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('github_tokens', function (Blueprint $table) {
            $table->dropColumn(['installation_id', 'token_type', 'expires_in', 'refreshed_at']);
        });
    }
};
