<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validation extends Model
{
    use HasFactory;

    protected $table = 'validations';

    protected $fillable = [
        'project_id',
        'teacher_id',
        'functionality_score',
        'code_quality_score',
        'documentation_score',
        'originality_score',
        'is_approved',
        'validation_date',
        'notes',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'validation_date' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
