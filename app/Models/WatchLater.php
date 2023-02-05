<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatchLater extends Model
{
  use HasFactory;

  public static function boot() {
    parent::boot();
    static::creating(function (WatchLater $watchLater) {
      $watchLater->user_id = auth()->id();
    });
  }
}