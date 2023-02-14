<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
  use HasFactory;
  public $timestamps = false;
  protected $fillable= [
    'voter_id',
    'post_id',
    'poll_id',
  ];
  
  public function poll() {
    return $this->belongsTo(Poll::class);
  }
  
  public static function boot() {
    parent::boot();
    static::creating(function (Vote $vote) {
      $vote->voter_id = auth()->id();
    });
  }
}