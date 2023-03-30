<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PlaylistVideo extends Model
{
  use HasFactory;
  public $timestamps = false;
  protected $fillable = [
    'playlist_id',
    'video_id',
    'serial'
  ];
}
