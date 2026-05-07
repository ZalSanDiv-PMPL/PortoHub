<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GithubToken extends Model
{
  use HasFactory;

  protected $table = 'github_tokens';

  protected $fillable = [
    'user_id',
    'access_token',
    'refresh_token',
    'token_expires_at',
    'scope',
    'github_id',
    'github_username',
    'is_active',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
