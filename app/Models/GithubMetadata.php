<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GithubMetadata extends Model
{
    use HasFactory;

    protected $table = 'github_metadata';

    protected $fillable = [
        'project_id',
        'repo_name',
        'repo_owner',
        'repo_url',
        'commit_count',
        'last_commit_at',
        'last_commit_message',
        'commit_frequency',
        'language',
        'stars',
        'forks',
        'is_public',
        'last_synced_at',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'commit_count' => 'integer',
        'last_commit_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
