<?php

namespace App\Console\Commands;

use App\Models\GithubToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshGithubTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:refresh-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh all GitHub tokens that are nearing expiration (less than 1 hour).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting GitHub token refresh process...');

        $tokensToRefresh = GithubToken::where('is_active', true)
            ->whereNotNull('refresh_token')
            ->where('token_expires_at', '<=', now()->addHour())
            ->get();

        if ($tokensToRefresh->isEmpty()) {
            $this->info('No tokens require refreshing at this time.');

            return Command::SUCCESS;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($tokensToRefresh as $token) {
            $this->line("Refreshing token for User ID: {$token->user_id} ({$token->github_username})");

            try {
                $response = Http::asForm()->post('https://github.com/login/oauth/access_token', [
                    'client_id' => config('services.github_app.client_id'),
                    'client_secret' => config('services.github_app.client_secret'),
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $token->refresh_token,
                ]);

                if (! $response->successful()) {
                    $this->error("  HTTP error: {$response->status()}");
                    $failCount++;

                    continue;
                }

                // Parse the response (GitHub returns form-encoded by default)
                $body = $response->body();
                parse_str($body, $data);

                if (isset($data['error'])) {
                    $this->error("  GitHub error: {$data['error']} — ".($data['error_description'] ?? ''));
                    Log::warning("GitHub token refresh failed for user {$token->user_id}: {$data['error']}");

                    // If the refresh token is invalid, deactivate
                    if ($data['error'] === 'bad_refresh_token') {
                        $token->update(['is_active' => false]);
                        $this->warn('  Token deactivated due to invalid refresh token.');
                    }

                    $failCount++;

                    continue;
                }

                if (! isset($data['access_token'])) {
                    $this->error('  No access_token in response.');
                    $failCount++;

                    continue;
                }

                $token->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $token->refresh_token,
                    'token_expires_at' => isset($data['expires_in'])
                        ? now()->addSeconds((int) $data['expires_in'])
                        : $token->token_expires_at,
                    'token_type' => $data['token_type'] ?? $token->token_type,
                    'refreshed_at' => now(),
                ]);

                $this->info('  ✓ Token refreshed successfully.');
                $successCount++;

            } catch (\Exception $e) {
                Log::error("GitHub token refresh exception for user {$token->user_id}: ".$e->getMessage());
                $this->error("  ✗ Exception: {$e->getMessage()}");
                $failCount++;
            }
        }

        $this->info("Process complete. Refreshed: {$successCount}, Failed: {$failCount}.");

        return Command::SUCCESS;
    }
}
