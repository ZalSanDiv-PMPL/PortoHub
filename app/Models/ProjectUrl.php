<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectUrl extends Model
{
  use HasFactory;

  protected $table = 'project_urls';

  protected $fillable = [
    'project_id',
    'url_type',
    'url',
    'description',
    'is_public',
  ];

  public function project()
  {
    return $this->belongsTo(Project::class);
  }
}
