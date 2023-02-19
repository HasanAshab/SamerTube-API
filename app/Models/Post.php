<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ReviewUtility;
use App\Traits\CommentUtility;
use App\Traits\ReportUtility;
use App\Traits\FileUtility;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Post extends Model
{
  use HasFactory, ReviewUtility, CommentUtility, FileUtility, ReportUtility;
  
  protected $appends = ['edited'];
  protected $hidden = [
    'updated_at',
    'total_votes'
  ];
 
  protected $fillable = [
    'channel_id',
    'content',
    'type',
    'visibility',
    'shared_id',
  ];
  
  public function channel(){
    return $this->belongsTo(Channel::class);
  }
  
  public function polls(){
    return $this->hasMany(Poll::class);
  }
  
  public function scopeChannel($query): Builder
  {
    return $query->join('channels', 'channels.id', 'videos.channel_id');
  }
  
  protected function createdAt(): Attribute {
    return new Attribute(
      get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->diffForHumans(),
    );
  }
  
  protected function updatedAt(): Attribute {
    return new Attribute(
      get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->diffForHumans(),
    );
  }
  
  protected function edited(): Attribute {
    return new Attribute(
      get: fn() => $this->created_at !== $this->updated_at,
    );
  }
  
  public static function boot() {
    parent::boot();
    static::creating(function (Post $post) {
     $post->channel_id = auth()->id();
    });
  }
}