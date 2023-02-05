<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedPlaylist extends Model
{
    use HasFactory;
    
    public static function boot() {
    parent::boot();
    static::creating(function (SavedPlaylist $savedPlaylist) {
      $savedPlaylist->user_id = auth()->id();
    });
  }
}
