<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
class View extends Model
{
  use HasFactory;
  protected $timestamps = false;

  public function video(){
    return $this->belongsTo(Video::class);
  }
  
  public static function boot() {
    parent::boot();
    static::creating(function (View $view) {
      $view->user_id = auth()->id();
    });
  }
}