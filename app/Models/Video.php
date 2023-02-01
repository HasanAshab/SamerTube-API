<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TagUtility;
use App\Traits\SearchUtility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use DB;
use Carbon\Carbon;

class Video extends Model
{
  use HasFactory, TagUtility, SearchUtility;
  
  public $morphable_type = 'App\Models\Video';
  
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
  
  public function getNextId() {
    $statement = DB::select("show table status like 'videos'");
    return $statement[0]->Auto_increment;
  }

  public function channel() {
    return $this->belongsTo(Channel::class);
  }

  public function comments() {
    return $this->hasMany(Comment::class, 'video_id')->join('channels', 'channels.id', '=', 'comments.commenter_id')->select('comments.*', 'channels.name', 'channels.logo_url');
  }

  public function scopeChannel($query, $columns = []) {
    return $query->with(['channel' => fn($query2) => $query2->select(array_merge(['id'], $columns))]);
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

}