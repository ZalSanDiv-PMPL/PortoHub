<?php

namespace App\Console\Commands;

use App\Models\GithubMetadata;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncGithubMetadataCommand extends Command
{
    protected $signature = 'github:sync-metadata {--project= : Sync a specific project ID}';

    protected $description = 'Sinkronisasi metadata GitHub (commit, bahasa, stars, forks) untuk proyek yang terhubung.';

    public function handle()
    {
        $query = Project::whereNotNull('github_url')
            ->where('github_url', '!=', '')
            ->whereHas('student.user.githubToken', function ($q) {
                $q->where('is_active', true);
            })
            ->with(['student.user.githubToken']);

        if ($projectId = $this->option('project')) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->info('Tidak ada proyek yang perlu disinkronisasi.');

            return 0;
        }

        $this->info("Menyinkronkan {$projects->count()} proyek...");

        $synced = 0;
        $errors = 0;

        foreach ($projects as $project) {
            $token = $project->student->user->githubToken;
            if (! $token || ! $token->access_token) {
                continue;
            }

            // Parse owner/repo from github_url
            $parsed = $this->parseGithubUrl($project->github_url);
            if (! $parsed) {
                $this->warn("URL tidak valid: {$project->github_url}");
                $errors++;

                continue;
            }

            [$owner, $repo] = $parsed;

            try {
                // Fetch repo info
                $repoResponse = Http::withToken($token->access_token)
                    ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                    ->get("https://api.github.com/repos/{$owner}/{$repo}");

                if (! $repoResponse->successful()) {
                    $this->warn("Gagal ambil repo {$owner}/{$repo}: {$repoResponse->status()}");
                    $errors++;

                    continue;
                }

                $repoData = $repoResponse->json();

                // Fetch latest commit
                $commitResponse = Http::withToken($token->access_token)
                    ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                    ->get("https://api.github.com/repos/{$owner}/{$repo}/commits", [
                        'per_page' => 1,
                    ]);

                $lastCommitMessage = null;
                $lastCommitAt = null;
                $commitCount = 0;

                if ($commitResponse->successful()) {
                    $commits = $commitResponse->json();
                    if (! empty($commits[0])) {
                        $lastCommitMessage = $commits[0]['commit']['message'] ?? null;
                        $lastCommitAt = isset($commits[0]['commit']['committer']['date'])
                            ? Carbon::parse($commits[0]['commit']['committer']['date'])->format('Y-m-d H:i:s')
                            : null;
                    }

                    // Parse total commit count from Link header
                    $linkHeader = $commitResponse->header('Link');
                    if ($linkHeader && preg_match('/page=(\d+)>; rel="last"/', $linkHeader, $matches)) {
                        $commitCount = (int) $matches[1];
                    } else {
                        $commitCount = count($commits);
                    }
                }

                // Fetch languages for tech-stack detection
                $langResponse = Http::withToken($token->access_token)
                    ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                    ->get("https://api.github.com/repos/{$owner}/{$repo}/languages");

                $techStack = [];
                if ($langResponse->successful()) {
                    $techStack = array_keys($langResponse->json());
                }

                // Calculate commit frequency (commits per week, rough estimate)
                $createdAt = $repoData['created_at'] ?? null;
                $commitFrequency = null;
                if ($createdAt && $commitCount > 0) {
                    $weeks = max(1, now()->diffInWeeks(Carbon::parse($createdAt)));
                    $commitFrequency = round($commitCount / $weeks, 1);
                }

                // Update or create github_metadata
                GithubMetadata::updateOrCreate(
                    ['project_id' => $project->id],
                    [
                        'repo_name' => $repoData['name'] ?? $repo,
                        'repo_owner' => $repoData['owner']['login'] ?? $owner,
                        'repo_url' => $repoData['html_url'] ?? $project->github_url,
                        'commit_count' => $commitCount,
                        'last_commit_at' => $lastCommitAt,
                        'last_commit_message' => $lastCommitMessage,
                        'commit_frequency' => $commitFrequency,
                        'language' => $repoData['language'] ?? null,
                        'stars' => $repoData['stargazers_count'] ?? 0,
                        'forks' => $repoData['forks_count'] ?? 0,
                        'is_public' => ! ($repoData['private'] ?? true),
                        'last_synced_at' => now(),
                    ]
                );

                // Update project tech_stack
                if (! empty($techStack)) {
                    $project->update(['tech_stack' => $techStack]);
                }

                $synced++;
                $this->line("  ✓ {$project->title} ({$owner}/{$repo}) — {$commitCount} commits, ".implode(', ', $techStack));

            } catch (\Exception $e) {
                Log::error("GitHub sync error for project {$project->id}: ".$e->getMessage());
                $this->error("  ✗ {$project->title}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("Selesai: {$synced} berhasil, {$errors} gagal.");

        return 0;
    }

    private function parseGithubUrl(string $url): ?array
    {
        // Match patterns: https://github.com/owner/repo or https://github.com/owner/repo.git
        if (preg_match('#github\.com/([^/]+)/([^/\.]+)#', $url, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return null;
    }
}
