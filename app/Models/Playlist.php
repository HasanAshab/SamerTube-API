<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Playlist extends Model
{
  use HasFactory;
  
  public function getNextId() {
    $statement = DB::select("show table status like 'playlists'");
    return $statement[0]->Auto_increment;
  }
  
  public function videos(){
    return $this->hasMany(PlaylistVideo::class)->oldest();
  }
}
