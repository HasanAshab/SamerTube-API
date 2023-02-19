<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TagUtility;
use App\Traits\SearchUtility;
use App\Traits\FileUtility;
use App\Traits\ReportUtility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Channel extends Model
{
  use HasFactory, TagUtility, SearchUtility, FileUtility, ReportUtility;
  
  public $timestamps = false;
  
  protected $fillable = [
    'name',
    'description',
    'country',
    'logo_path',
    'logo_url'
  ];

  protected $hidden = [
    'logo_path',
    'total_likes',
    'total_comments',
    'total_watch_time',
    'updated_at'
  ];
  
  protected $searchable = ['name'];
  public static $rankable = [
    'relevance' => [
      ['total_watch_time', 'desc'],
      ['total_subscribers', 'desc'],
      ['total_comments', 'desc'],
      ['total_likes', 'desc'],
      ['created_at', 'asc'],
    ],
    'date' => [
      ['created_at', 'desc'],
      ['total_watch_time', 'desc'],
      ['total_subscribers', 'desc'],
      ['total_comments', 'desc'],
      ['total_likes', 'desc'],
    ],
  ];
  
  public function user() {
    return $this->hasOne(User::class, 'id', 'id');
  }
  public function videos() {
    return $this->hasMany(Video::class);
  }
  public function replies() {
    return $this->hasManyThrough(Reply::class, Comment::class, 'commenter_id');
  }
  public function views() {
    return $this->hasManyThrough(View::class, Video::class);
  }
  protected function createdAt(): Attribute {
    return new Attribute(
      get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->format('jS M, Y'),
    );
  }
}