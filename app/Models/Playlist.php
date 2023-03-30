<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SearchUtility;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\URL;
use DB;


class Playlist extends Model
{
  use HasFactory, SearchUtility;
  protected $fillable = [
    'name',
    'description',
    'visibility'
  ];
  protected $appends = ['thumbnail_url', 'link'];
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
    return $this->belongsToMany(Video::class, 'playlist_videos');
  }
  
  protected function link(): Attribute {
    return new Attribute(
      get: function () {
        $backend_url = URL::signedRoute('playlist.videos', ['id' => $this->id]);
        preg_match('/signature=([\w]+)/', $backend_url, $matches);
        $signature = $matches[1];
        $link = config('frontend.url')."/playlist/$this->id/videos/?s=$signature";
        return $link;
      }
    );
  }
  
  protected function thumbnailUrl(): Attribute {
    return new Attribute(
      get: fn() => $this->videos()->orderBy('serial', 'asc')->value('thumbnail_url'),
    );
  }
  
  public static function boot() {
    parent::boot();
    static::creating(function (Playlist $playlist) {
      $playlist->user_id = auth()->id();
    });
  }
}
