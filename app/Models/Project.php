<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    'submission_date' => 'datetime',
    'approval_date' => 'datetime',
    'tech_stack' => 'array',
  ];

  public function scopePubliclyVisible($query)
  {
    return $query->where('visibility', 'public');
  }

  public function student()
  {
    return $this->belongsTo(Student::class);
  }

  public function validation()
  {
    return $this->hasOne(Validation::class);
  }

  public function comments()
  {
    return $this->hasMany(Comment::class);
  }

  public function documentation()
  {
    return $this->hasMany(Documentation::class);
  }

  public function urls()
  {
    return $this->hasMany(ProjectUrl::class);
  }

  public function githubMetadata()
  {
    return $this->hasOne(GithubMetadata::class);
  }
}
