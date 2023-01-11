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
    'updated_at'
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
    $query = ($sort === 'relevance')??$query->orderByDesc('view_count')->orderByDesc('comment_count')->orderByDesc('like_count')->orderByDesc('created_at')->orderByDesc('duration');
    $query = ($sort === 'view')??$query->orderByDesc('view_count');
    $query = ($sort === 'date')??$query->latest('created_at');
    $query = ($sort === 'rate')??$query->orderByDesc('like_count');
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

  protected function title(): Attribute {
    return new Attribute(
      get: fn($value) => ucfirst($value),
    );
  }
  protected function createdAt(): Attribute {
    return new Attribute(
      get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->diffForHumans(),
    );
  }

}