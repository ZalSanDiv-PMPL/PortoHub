<?php

namespace Tests\Feature\Auth;

use App\Models\GithubToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGitHubTokenExchange(string $accessToken = 'fake-access-token'): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response([
                'access_token' => $accessToken,
                'refresh_token' => 'fake-refresh-token',
                'token_type' => 'bearer',
                'expires_in' => 28800,
            ]),
        ]);
    }

    private function fakeGitHubUserApi(int $id = 12345, string $login = 'testuser', ?string $email = 'test@example.com'): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response([
                'access_token' => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
                'token_type' => 'bearer',
            ]),
            'api.github.com/user' => Http::response([
                'id' => $id,
                'login' => $login,
                'name' => 'Test User',
                'email' => $email,
            ]),
            'api.github.com/user/emails' => Http::response([
                ['email' => $email ?? 'fallback@example.com', 'primary' => true, 'verified' => true],
            ]),
        ]);
    }

    // ============================================================
    // Test 1: Redirect generates state and redirects to GitHub
    // ============================================================

    public function test_github_redirect_generates_state_and_redirects(): void
    {
        $response = $this->get(route('github.redirect'));

        $response->assertRedirect();
        $this->assertStringContainsString('github.com/login/oauth/authorize', $response->headers->get('Location'));
        $this->assertNotNull(session('github_state'));
    }

    // ============================================================
    // Test 2: Callback rejects invalid state
    // ============================================================

    public function test_callback_rejects_invalid_state(): void
    {
        $response = $this->withSession(['github_state' => 'valid-state', 'github_oauth_action' => 'login'])
            ->get(route('github.callback', ['code' => 'some-code', 'state' => 'wrong-state']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    // ============================================================
    // Test 3: Callback handles GitHub cancel (error parameter)
    // ============================================================

    public function test_callback_handles_github_cancel(): void
    {
        $response = $this->get(route('github.callback', [
            'error' => 'access_denied',
            'error_description' => 'The user has denied your application access.',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    // ============================================================
    // Test 4: Existing GitHub user can login via github_id
    // ============================================================

    public function test_existing_github_user_can_login(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        GithubToken::create([
            'user_id' => $user->id,
            'access_token' => 'old-token',
            'github_id' => 12345,
            'github_username' => 'testuser',
            'is_active' => true,
            'scope' => 'user:email',
            'token_type' => 'bearer',
        ]);

        $this->fakeGitHubUserApi(id: 12345, login: 'testuser', email: 'test@example.com');

        $response = $this->withSession([
            'github_state' => 'valid-state',
            'github_oauth_action' => 'login',
        ])->get(route('github.callback', ['code' => 'test-code', 'state' => 'valid-state']));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    // ============================================================
    // Test 5: Email auto-link only uses verified email
    // ============================================================

    public function test_email_auto_link_only_uses_verified_email(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'role' => 'student',
        ]);

        Http::fake([
            'github.com/login/oauth/access_token' => Http::response([
                'access_token' => 'fake-access-token',
                'token_type' => 'bearer',
            ]),
            'api.github.com/user' => Http::response([
                'id' => 99999,
                'login' => 'newuser',
                'name' => 'New User',
                'email' => 'verified@example.com',
            ]),
            // Return email as NOT verified — should NOT auto-link
            'api.github.com/user/emails' => Http::response([
                ['email' => 'verified@example.com', 'primary' => true, 'verified' => false],
            ]),
        ]);

        $response = $this->withSession([
            'github_state' => 'valid-state',
            'github_oauth_action' => 'login',
        ])->get(route('github.callback', ['code' => 'test-code', 'state' => 'valid-state']));

        // Should create a NEW user instead of linking to existing one
        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'github_99999@noreply.portohub.local',
        ]);
    }

    // ============================================================
    // Test 6: New user registered via GitHub
    // ============================================================

    public function test_new_user_registered_via_github(): void
    {
        $this->fakeGitHubUserApi(id: 77777, login: 'newuser', email: 'new@example.com');

        $response = $this->withSession([
            'github_state' => 'valid-state',
            'github_oauth_action' => 'login',
        ])->get(route('github.callback', ['code' => 'test-code', 'state' => 'valid-state']));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'student']);
        $this->assertDatabaseHas('github_tokens', ['github_id' => 77777]);
        $this->assertDatabaseHas('students', ['user_id' => User::where('email', 'new@example.com')->first()->id]);
    }

    // ============================================================
    // Test 7: Link mode connects GitHub to logged-in user
    // ============================================================

    public function test_link_mode_connects_github_to_logged_in_user(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->fakeGitHubUserApi(id: 55555, login: 'linkeduser', email: 'linked@example.com');

        $response = $this->actingAs($user)
            ->withSession([
                'github_state' => 'valid-state',
                'github_oauth_action' => 'link',
            ])
            ->get(route('github.callback', ['code' => 'test-code', 'state' => 'valid-state']));

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('github_tokens', [
            'user_id' => $user->id,
            'github_id' => 55555,
        ]);
    }

    // ============================================================
    // Test 8: Link rejects GitHub already used by another user
    // ============================================================

    public function test_link_rejects_github_already_used_by_another_user(): void
    {
        $otherUser = User::factory()->create();
        GithubToken::create([
            'user_id' => $otherUser->id,
            'access_token' => 'token',
            'github_id' => 33333,
            'github_username' => 'taken-user',
            'is_active' => true,
            'scope' => 'user:email',
            'token_type' => 'bearer',
        ]);

        $currentUser = User::factory()->create();

        $this->fakeGitHubUserApi(id: 33333, login: 'taken-user', email: 'taken@example.com');

        $response = $this->actingAs($currentUser)
            ->withSession([
                'github_state' => 'valid-state',
                'github_oauth_action' => 'link',
            ])
            ->get(route('github.callback', ['code' => 'test-code', 'state' => 'valid-state']));

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('github_tokens', [
            'user_id' => $currentUser->id,
            'github_id' => 33333,
        ]);
    }

    // ============================================================
    // Test 9: Unlink blocked without local password
    // ============================================================

    public function test_unlink_blocked_without_local_password(): void
    {
        $user = User::factory()->create(['password_set_at' => null]);
        GithubToken::create([
            'user_id' => $user->id,
            'access_token' => 'token',
            'github_id' => '123',
            'github_username' => 'test-user',
            'is_active' => true,
            'scope' => 'user:email',
            'token_type' => 'bearer',
        ]);

        $this->actingAs($user);
        $response = $this->post(route('github.unlink'));

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('github_tokens', ['user_id' => $user->id]);
    }

    // ============================================================
    // Test 10: Unlink succeeds with local password
    // ============================================================

    public function test_unlink_succeeds_with_local_password(): void
    {
        $user = User::factory()->create(['password_set_at' => now()]);
        GithubToken::create([
            'user_id' => $user->id,
            'access_token' => 'token',
            'github_id' => '456',
            'github_username' => 'test-user-2',
            'is_active' => true,
            'scope' => 'user:email',
            'token_type' => 'bearer',
        ]);

        $this->actingAs($user);
        $response = $this->post(route('github.unlink'));

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('github_tokens', ['user_id' => $user->id]);
    }
}
