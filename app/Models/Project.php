<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'title',
        'description',
        'thumbnail_path',
        'development_model',
        'github_url',
        'status',
        'visibility',
        'tech_stack',
        'submission_date',
        'approval_date',
        'rejection_reason',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'submission_date' => 'datetime',
        'approval_date' => 'datetime',
        'tech_stack' => 'array',
    ];

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('visibility', 'public');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function validation(): HasOne
    {
        return $this->hasOne(Validation::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function documentation(): HasMany
    {
        return $this->hasMany(Documentation::class);
    }

    public function urls(): HasMany
    {
        return $this->hasMany(ProjectUrl::class);
    }

    public function githubMetadata(): HasOne
    {
        return $this->hasOne(GithubMetadata::class);
    }
}
