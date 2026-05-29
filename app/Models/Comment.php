<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
  use HasFactory;

  protected $fillable = [
    'project_id',
    'teacher_id',
    'content',
    'comment_type',
    'status',
    'is_pinned',
  ];

  protected $casts = [
    'is_pinned' => 'boolean',
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
