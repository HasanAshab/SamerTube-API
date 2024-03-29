<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\CanResetPassword;

class User extends Authenticatable implements MustVerifyEmail, CanResetPassword
{
  use HasApiTokens,
  HasFactory,
  Notifiable;

  /**
  * The attributes that are mass assignable.
  *
  * @var array<int, string>
  */
  protected $fillable = [
    'email',
    'google_id',
    'password',
    'search_history',
    'watch_history',
  ];

  /**
  * The attributes that should be hidden for serialization.
  *
  * @var array<int, string>
  */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  public function channel() {
    return $this->hasOne(Channel::class, 'id', 'id');
  }

  public function videos() {
    return $this->hasMany(Video::class, 'channel_id');
  }
  public function settings() {
    return $this->hasOne(UserSetting::class);
  }
  public function hiddens() {
    return $this->hasMany(Hidden::class);
  }

  protected static function boot() {
    parent::boot();
    static::created(function ($user) {
      $data = [
        'notifications' => [
          'mail' => true,
          'subscriptions' => true,
          'channel' => true,
          'replies' => true,
          'shared_content' => true
        ],
        'autoplay' => true
      ];
      $user->settings()->create(['data' => $data]);
    });
  }
}