<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUrl extends Model
{
    use HasFactory;

    protected $table = 'project_urls';

    protected $fillable = [
        'project_id',
        'url_type',
        'url',
        'description',
        'is_public',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'is_public' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
