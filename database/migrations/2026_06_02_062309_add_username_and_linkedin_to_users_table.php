<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('linkedin_username')->nullable()->after('username');
        });

        // Generate username for existing users
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $baseUsername = Str::slug($user->name);
            $username = $baseUsername;
            $counter = 1;

            // Ensure uniqueness
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $baseUsername . '-' . $counter;
                $counter++;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => $username]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'linkedin_username']);
        });
    }
};
