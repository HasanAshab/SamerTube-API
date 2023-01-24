<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use DB;
use Carbon\Carbon;

class Video extends Model
{
  use HasFactory;
  
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
    'average_view_duration',
    'tags'
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

  public function scopeRank($query, $sort = 'relevance', $date_range = 'anytime') {
    if($sort === 'relevance'){
      $query = $query->orderByDesc('average_view_duration')->orderByDesc('watch_time')->orderByDesc('view_count')->orderByDesc('comment_count')->orderByDesc('like_count')->orderByDesc('created_at')->orderByDesc('duration');
    }
    else if($sort === 'view'){
      $query = $query->orderByDesc('view_count');
    }
    else if($sort === 'date'){
      $query = $query->latest('created_at');
    }
    else if($sort === 'rate'){
      $query = $query->orderByDesc('like_count');
    }
    $date_range === 'anytime' && $query;
    $date_range === 'hour' && $query->whereBetween('created_at', [Carbon::now()->subHours(1), Carbon::now()]);
    $date_range === 'day' && $query->where('created_at', 'like', Carbon::today()->toDateString().'%');
    $date_range === 'week' && $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    $date_range === 'month' && $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
    $date_range === 'year' && $query->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
    return null;
  }

  public function scopeChannel($query, $columns = []) {
    return $query->with(['channel' => fn($query2) => $query2->select(array_merge(['id'], $columns))]);
  }

  protected function tags(): Attribute {
    return new Attribute(
      get: fn($value) => explode(',', $value),
    );
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