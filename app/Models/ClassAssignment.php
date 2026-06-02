<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassAssignment extends Model
{
    use HasFactory;

    protected $table = 'class_assignments';

    protected $fillable = [
        'teacher_id',
        'student_id',
        'class',
        'semester',
        'is_active',
    ];

    protected $casts = [
        'teacher_id' => 'integer',
        'student_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
