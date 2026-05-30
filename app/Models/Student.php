<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    'is_validated' => 'boolean',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function classAssignments()
  {
    return $this->hasMany(ClassAssignment::class);
  }

  public function teachers()
  {
    return $this->belongsToMany(Teacher::class, 'class_assignments', 'student_id', 'teacher_id')
      ->withPivot(['class', 'semester', 'is_active'])
      ->withTimestamps();
  }

  public function projects()
  {
    return $this->hasMany(Project::class);
  }

  /**
   * Mendapatkan nama kelas siswa yang sedang aktif.
   */
  public function getActiveClassAttribute()
  {
      $assignment = $this->classAssignments->where('is_active', true)->first() 
                 ?? $this->classAssignments->sortByDesc('created_at')->first();
                 
      return $assignment ? $assignment->class : 'Belum ada kelas';
  }
}
