<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

  public function teacher()
  {
    return $this->belongsTo(Teacher::class);
  }

  public function student()
  {
    return $this->belongsTo(Student::class);
  }
}
