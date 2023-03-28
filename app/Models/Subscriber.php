<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Events\Subscribed;

class Subscriber extends Model
{
  use HasFactory, SoftDeletes;
  public $timestamps = false;

  protected $fillable = [
    'subscriber_id',
    'channel_id',
    'video_id',
    'preference'
  ];
  
  

  public function user() {
    return $this->hasOne(User::class, 'id', 'channel_id');
  }
  
  public function channel() {
    return $this->hasOne(Channel::class);
  }
  
  public function subscriber() {
    return $this->hasOne(User::class, 'id', 'subscriber_id');
  }
  
  protected static function boot() {
    parent::boot();
    static::creating(function (Subscriber $subscriber) {
      $subscriber->subscriber_id = auth()->id();
      $subscriber->preference = 'all';
    });
    static::created(function (Subscriber $subscriber) {
      event(new Subscribed($subscriber));
    });
  }
}