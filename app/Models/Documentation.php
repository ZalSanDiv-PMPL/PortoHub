<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
  use HasFactory;

  protected $table = 'documentation';

  protected $fillable = [
    'project_id',
    'doc_type',
    'file_name',
    'file_path',
    'file_size',
    'mime_type',
    'description',
    'is_public',
  ];

  public function project()
  {
    return $this->belongsTo(Project::class);
  }
}
