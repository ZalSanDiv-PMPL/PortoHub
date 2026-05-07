<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GithubToken;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GitHubAuthController extends Controller
{
    /**
     * Redirect to GitHub for authentication.
     * Optional query param `action=link` to link to authenticated user.
     */
    public function redirectToProvider(Request $request)
    {
        $action = $request->query('action', 'login');
        $request->session()->put('github_oauth_action', $action);
        return Socialite::driver('github')
            ->scopes(['read:user', 'user:email'])
            ->redirect();
    }

    /**
     * Handle GitHub callback and store token.
     */
    public function handleProviderCallback(Request $request)
    {
        $socialUser = Socialite::driver('github')->user();
        $action = $request->session()->pull('github_oauth_action', 'login');

        if (Auth::check() && $action === 'link') {
            $user = Auth::user();
        } else {
            // Try to find user by email
            $email = $socialUser->getEmail();
            $user = $email ? User::where('email', $email)->first() : null;

            if (! $user) {
                // Create minimal student account if not exists
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'email' => $email ?? 'no-reply@' . $socialUser->getNickname() . '.local',
                    'password' => bcrypt(Str::random(24)),
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

        // Persist or update GitHub token
        GithubToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken ?? null,
                'token_expires_at' => isset($socialUser->expiresIn) ? now()->addSeconds($socialUser->expiresIn) : null,
                'github_id' => $socialUser->getId(),
                'github_username' => $socialUser->getNickname(),
                'is_active' => true,
                'scope' => null,
            ]
        );

        return redirect()->route('dashboard')->with('success', 'GitHub account connected.');
    }
}
