<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SearchUtility;
use Illuminate\Database\Eloquent\Casts\Attribute;
use DB;

class Playlist extends Model
{
  use HasFactory, SearchUtility;
  protected $appends = ['channel_name', 'thumbnail_url'];
  protected $searchable = ['name'];
  public static $rankable = [
    'relevance' => [
      ['total_videos', 'desc'],
    ],
  ];
  public function getNextId() {
    $statement = DB::select("show table status like 'playlists'");
    return $statement[0]->Auto_increment;
  }
  
  public function videos(){
    return $this->hasMany(PlaylistVideo::class)->oldest();
  }
  
  protected function channelName(): Attribute {
    return new Attribute(
      get: fn() => Channel::where('id', $this->user_id)->value('name'),
    );
  }
  
  protected function thumbnailUrl(): Attribute {
    return new Attribute(
      get: fn() => PlaylistVideo::where('playlist_id', $this->id)->join('videos', 'videos.id', '=', 'playlist_videos.video_id')->select('videos.thumbnail_url')->value('thumbnail_url'),
    );
  }
}
