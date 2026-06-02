<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUserHasRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_student_can_access_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'student', 'email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_user_without_required_role_gets_403(): void
    {
        Route::middleware(['auth', 'role:admin'])
            ->get('/admin-only-test', fn() => 'ok');

        $user = User::factory()->create(['role' => 'student', 'email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/admin-only-test');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }
}
