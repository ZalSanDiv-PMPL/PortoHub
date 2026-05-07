<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function classAssignments()
  {
    return $this->hasMany(ClassAssignment::class);
  }

  public function students()
  {
    return $this->belongsToMany(Student::class, 'class_assignments', 'teacher_id', 'student_id')
      ->withPivot(['class', 'semester', 'is_active'])
      ->withTimestamps();
  }
}
