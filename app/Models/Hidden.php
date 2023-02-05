<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hidden extends Model
{
  use HasFactory;

  public static function boot() {
    parent::boot();
    static::creating(function (Hidden $hidden) {
      $hidden->user_id = auth()->id();
    });
  }
}