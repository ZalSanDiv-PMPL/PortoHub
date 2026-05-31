<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nip',
        'specialization',
        'department',
        'phone',
        'address',
        'is_validated',
    ];

    protected $casts = [
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

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_assignments', 'teacher_id', 'student_id')
            ->withPivot(['class', 'semester', 'is_active'])
            ->withTimestamps();
    }
}
