<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GithubToken;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GitHubAppAuthController extends Controller
{
    /**
     * Redirect to GitHub for user authorization (GitHub App OAuth flow).
     */
    public function redirectToProvider(Request $request)
    {
        $action = $request->query('action', 'login');
        $request->session()->put('github_oauth_action', $action);
        $request->session()->put('github_state', Str::random(40));

        $params = http_build_query([
            'client_id' => config('services.github_app.client_id'),
            'redirect_uri' => config('services.github_app.callback'),
            'scope' => 'user:email',
            'state' => $request->session()->get('github_state'),
            'allow_signup' => 'true',
        ]);

        return redirect("https://github.com/login/oauth/authorize?{$params}");
    }

    /**
     * Link GitHub to authenticated user.
     */
    public function redirectToProviderLink(Request $request)
    {
        $request->session()->put('github_oauth_action', 'link');
        return $this->redirectToProvider($request);
    }

    /**
     * Handle GitHub App OAuth callback.
     * Exchange code for access token + refresh token.
     */
    public function handleProviderCallback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $sessionState = $request->session()->pull('github_state');

        // Verify state
        if (!$code || !$state || $state !== $sessionState) {
            return redirect()->route('login')->with('error', 'GitHub authorization failed (invalid state).');
        }

        // Exchange code for token
        try {
            $response = Http::asForm()
                ->accept('application/json')
                ->post('https://github.com/login/oauth/access_token', [
                    'client_id' => config('services.github_app.client_id'),
                    'client_secret' => config('services.github_app.client_secret'),
                    'code' => $code,
                    'redirect_uri' => config('services.github_app.callback'),
                ]);

            $tokenResponse = $response->json();

                // Debug: log token response
                if (config('app.debug')) {
                    Log::debug('GitHub token response', [
                        'status' => $response->status(),
                        'access_token' => isset($tokenResponse['access_token']) ? substr($tokenResponse['access_token'], 0, 10) . '...' : null,
                        'refresh_token' => isset($tokenResponse['refresh_token']) ? substr($tokenResponse['refresh_token'], 0, 10) . '...' : null,
                        'expires_in' => $tokenResponse['expires_in'] ?? null,
                    ]);
                }
        } catch (\Exception $e) {
            Log::error('GitHub App token exchange failed', ['error' => $e->getMessage()]);
            return redirect()->route('login')->with('error', 'Token exchange failed.');
        }

        if (!isset($tokenResponse['access_token'])) {
            Log::error('GitHub App token exchange returned no access_token', ['response' => $tokenResponse]);
            return redirect()->route('login')->with('error', 'Token exchange failed.');
        }

        $accessToken = $tokenResponse['access_token'];
        $refreshToken = $tokenResponse['refresh_token'] ?? null;
        $expiresIn = $tokenResponse['expires_in'] ?? null;

        // Get user info from GitHub API
        try {
            $userResponse = Http::withToken($accessToken)
                ->accept('application/vnd.github.v3+json')
                ->get('https://api.github.com/user')
                ->json();
        } catch (\Exception $e) {
            Log::error('GitHub API user fetch failed', ['error' => $e->getMessage()]);
            return redirect()->route('login')->with('error', 'Failed to fetch GitHub user info.');
        }

        if (!isset($userResponse['id'])) {
            Log::error('GitHub API returned no user id', ['response' => $userResponse]);
            return redirect()->route('login')->with('error', 'Failed to fetch GitHub user info.');
        }

        // Debug log
        if (config('app.debug')) {
            Log::debug('GitHub App OAuth callback', [
                'access_token' => substr($accessToken, 0, 10) . '...',
                'refresh_token' => $refreshToken ? substr($refreshToken, 0, 10) . '...' : null,
                'expires_in' => $expiresIn,
                'github_id' => $userResponse['id'],
                'github_username' => $userResponse['login'],
            ]);
        }

        // Find or create user
        $action = $request->session()->pull('github_oauth_action', 'login');

        if (Auth::check() && $action === 'link') {
            $user = Auth::user();
        } else {
            $email = $userResponse['email'] ?? null;
            $user = $email ? User::where('email', $email)->first() : null;

            if (!$user) {
                $user = User::create([
                    'name' => $userResponse['name'] ?? $userResponse['login'],
                    'email' => $email ?? 'no-reply@' . $userResponse['login'] . '.local',
                    'password' => bcrypt(Str::random(24)),
                    'password_set_at' => null,
                    'role' => 'student',
                ]);

                Student::create([
                    'user_id' => $user->id,
                    'nis' => '',
                    'class' => '',
                    'year' => date('Y'),
                ]);
            }

            Auth::login($user, true);
        }

        // Store/update token with GitHub App data
        $data = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'github_id' => $userResponse['id'],
            'github_username' => $userResponse['login'],
            'is_active' => true,
            'scope' => 'user:email',
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
        ];

        if ($expiresIn) {
            $data['token_expires_at'] = now()->addSeconds($expiresIn);
        }

        GithubToken::updateOrCreate(['user_id' => $user->id], $data);

        Log::info('GitHub token stored', ['user_id' => $user->id, 'has_refresh' => !empty($refreshToken)]);

        return redirect()->route('dashboard')->with('success', 'GitHub account connected.');
    }

    /**
     * Unlink GitHub.
     */
    public function unlinkProvider(Request $request)
    {
        $user = $request->user();

        if (! $user->hasLocalPassword()) {
            return redirect()->route('profile')->with('error', 'Set a password before disconnecting GitHub.');
        }

        GithubToken::where('user_id', $user->id)->delete();

        return redirect()->route('profile')->with('success', 'GitHub account disconnected.');
    }
}
