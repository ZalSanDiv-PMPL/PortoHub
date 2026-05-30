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
        // PERBAIKAN: Hanya gunakan query parameter 'action' jika session github_oauth_action belum diatur
        // Ini mencegah session 'link' tertimpa menjadi 'login' saat memanggil endpoint ini.
        $action = $request->session()->get('github_oauth_action') ?? $request->query('action', 'login');
        
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
        // 1. Tangani jika user membatalkan otorisasi di halaman GitHub
        if ($request->has('error')) {
            $errorDesc = $request->get('error_description', 'GitHub authorization was canceled.');
            return redirect()->route('login')->with('error', $errorDesc);
        }

        $code = $request->get('code');
        $state = $request->get('state');
        $sessionState = $request->session()->pull('github_state');
        $action = $request->session()->pull('github_oauth_action', 'login');

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

        $githubId = $userResponse['id'];
        $githubUsername = $userResponse['login'];
        $githubEmail = $userResponse['email'] ?? null;

        // Siapkan data token untuk disimpan
        $tokenData = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'github_id' => $githubId,
            'github_username' => $githubUsername,
            'is_active' => true,
            'scope' => 'user:email',
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
        ];

        if ($expiresIn) {
            $tokenData['token_expires_at'] = now()->addSeconds($expiresIn);
        }

        // =========================================================
        // FLOW 1: MODE LINK (User is Logged In and connecting GitHub)
        // =========================================================
        if (Auth::check() && $action === 'link') {
            $currentUser = Auth::user();

            // Proteksi Identity Collision: Apakah akun GitHub ini sudah ditautkan ke user lain?
            $existingToken = GithubToken::where('github_id', $githubId)->first();
            
            if ($existingToken && $existingToken->user_id !== $currentUser->id) {
                // Akun GitHub sudah terdaftar di sistem untuk pengguna lain! Tolak penautan.
                return redirect()->route('profile')->with('error', 'Akun GitHub ini sudah digunakan oleh pengguna lain.');
            }

            // Tautkan ke user yang sedang login
            GithubToken::updateOrCreate(['user_id' => $currentUser->id], $tokenData);
            
            Log::info('GitHub token linked', ['user_id' => $currentUser->id]);
            return redirect()->route('profile')->with('success', 'Akun GitHub berhasil dihubungkan.');
        }

        // =========================================================
        // FLOW 2: MODE LOGIN / REGISTER (User is a Guest)
        // =========================================================

        // Langkah 2.1: Pencarian Utama (Primary Lookup) via Provider ID
        $existingToken = GithubToken::where('github_id', $githubId)->first();

        if ($existingToken && $existingToken->user) {
            // Pengguna ditemukan via GitHub ID.
            $user = $existingToken->user;
            
            // Perbarui token
            $existingToken->update($tokenData);
            
            Auth::login($user, true);
            return redirect()->route('dashboard');
        }

        // Langkah 2.2: Pencarian Cadangan (Fallback Auto-link) via Email
        if ($githubEmail) {
            $user = User::where('email', $githubEmail)->first();
            if ($user) {
                // Pengguna ditemukan berdasarkan email. Lakukan auto-link token ke pengguna ini.
                GithubToken::updateOrCreate(['user_id' => $user->id], $tokenData);
                
                Auth::login($user, true);
                return redirect()->route('dashboard');
            }
        }

        // Langkah 2.3: Register Pengguna Baru
        $user = User::create([
            'name' => $userResponse['name'] ?? $githubUsername,
            'email' => $githubEmail ?? 'no-reply@' . $githubUsername . '.local',
            'password' => bcrypt(Str::random(24)),
            'password_set_at' => null,
            'role' => 'student',
        ]);

        Student::create([
            'user_id' => $user->id,
            'nis' => '',
            'year' => date('Y'),
        ]);

        // Simpan token untuk pengguna baru
        GithubToken::updateOrCreate(['user_id' => $user->id], $tokenData);

        Auth::login($user, true);
        return redirect()->route('dashboard');
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
