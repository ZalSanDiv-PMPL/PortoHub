<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nis',
        'year',
        'phone',
        'address',
        'is_validated',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'year' => 'integer',
        'is_validated' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classAssignments(): HasMany
    {
        return $this->hasMany(ClassAssignment::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'class_assignments', 'student_id', 'teacher_id')
            ->withPivot(['class', 'semester', 'is_active'])
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Mendapatkan nama kelas siswa yang sedang aktif.
     */
    public function getActiveClassAttribute(): string
    {
        $assignment = $this->classAssignments->where('is_active', true)->first()
                   ?? $this->classAssignments->sortByDesc('created_at')->first();

        return $assignment ? $assignment->class : 'Belum ada kelas';
    }
}
