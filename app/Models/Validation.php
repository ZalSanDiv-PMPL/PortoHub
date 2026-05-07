<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

  public function project()
  {
    return $this->belongsTo(Project::class);
  }

  public function teacher()
  {
    return $this->belongsTo(Teacher::class);
  }
}
