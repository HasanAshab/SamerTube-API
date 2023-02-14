<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TagUtility;
use App\Traits\SearchUtility;
use App\Traits\ReviewUtility;
use App\Traits\CommentUtility;
use App\Traits\FileUtility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Video extends Model
{
  use HasFactory, TagUtility, SearchUtility, ReviewUtility, CommentUtility, FileUtility;
  
  protected $fillable = [
    'channel_id',
    'title',
    'description',
    'duration',
    'video_url',
    'thumbnail_url',
    'link',
    'category',
    'visibility'
  ];
  
  protected $hidden = [
    'video_path',
    'thumbnail_path',
    'updated_at',
    'watch_time',
    'average_view_duration'
  ];
  
  protected $searchable = [
    'title',
    'description'
  ];
  
  public static $rankable = [
    'relevance' => [
      ['average_view_duration', 'desc'],
      ['watch_time', 'desc'],
      ['view_count', 'desc'],
      ['comment_count', 'desc'],
      ['like_count', 'desc'],
    ],
    'view' => [
      ['view_count', 'desc'],
      ['average_view_duration', 'desc'],
      ['watch_time', 'desc'],
      ['comment_count', 'desc'],
      ['like_count', 'desc'],
    ],
    'date' => [
      ['created_at', 'desc'],
      ['average_view_duration', 'desc'],
      ['watch_time', 'desc'],
      ['view_count', 'desc'],
      ['comment_count', 'desc'],
      ['like_count', 'desc'],
    ],
    'rate' => [
      ['like_count', 'desc'],
      ['average_view_duration', 'desc'],
      ['watch_time', 'desc'],
      ['view_count', 'desc'],
      ['comment_count', 'desc'],
    ],
  ];

  public function channel(){
    return $this->belongsTo(Channel::class);
  }

  public function scopeChannel($query): Builder
  {
    return $query->join('channels', 'channels.id', 'videos.channel_id');
  }

  protected function duration(): Attribute {
    return new Attribute(
      get: fn($value) => $value<3600?gmdate("i:s", $value):gmdate("H:i:s", $value),
    );
  }
  protected function createdAt(): Attribute {
    return new Attribute(
      get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->diffForHumans(),
    );
  }
  
  public static function boot() {
    parent::boot();
    static::creating(function (Video $video) {
      $video->channel_id = auth()->id();
    });
  }
}