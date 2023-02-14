<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscriber extends Model
{
  use HasFactory, SoftDeletes;
  protected $timestamps = false;

  protected $fillable = [
    'subscriber_id',
    'channel_id',
    'video_id'
  ];
  function channel() {
    return $this->hasOne(Channel::class, 'id', 'channel_id');
  }

  public static function boot() {
    parent::boot();
    static::creating(function (Subscriber $subscriber) {
      $subscriber->subscriber_id = auth()->id();
    });
  }
}