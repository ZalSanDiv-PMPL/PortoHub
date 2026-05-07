<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class GithubToken extends Model
{
  use HasFactory;

  protected $table = 'github_tokens';

  protected $casts = [
    'token_expires_at' => 'datetime',
  ];

  protected $hidden = [
    'access_token',
    'refresh_token',
  ];

  protected $fillable = [
    'user_id',
    'access_token',
    'refresh_token',
    'token_expires_at',
    'scope',
    'github_id',
    'github_username',
    'is_active',
    'installation_id',
    'token_type',
    'expires_in',
    'refreshed_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function getAccessTokenAttribute($value)
  {
    return $this->decryptTokenValue($value);
  }

  public function setAccessTokenAttribute($value): void
  {
    $this->attributes['access_token'] = $value === null ? null : Crypt::encryptString($value);
  }

  public function getRefreshTokenAttribute($value)
  {
    return $this->decryptTokenValue($value);
  }

  public function setRefreshTokenAttribute($value): void
  {
    $this->attributes['refresh_token'] = $value === null ? null : Crypt::encryptString($value);
  }

  private function decryptTokenValue($value)
  {
    if ($value === null || $value === '') {
      return $value;
    }

    try {
      return Crypt::decryptString($value);
    } catch (DecryptException) {
      return $value;
    }
  }
}
