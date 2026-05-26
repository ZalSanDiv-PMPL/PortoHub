<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GithubToken;

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
            $this->line("Refreshing token for User ID: {$token->user_id}");
            
            // TODO: Implement actual HTTP call to GitHub OAuth endpoint using $token->refresh_token
            // For now, this is just a skeleton for the best practice implementation.
            
            // Simulating success
            $successCount++;
        }

        $this->info("Process complete. Refreshed: {$successCount}, Failed: {$failCount}.");
        
        return Command::SUCCESS;
    }
}
