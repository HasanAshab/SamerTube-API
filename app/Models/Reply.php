<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Reply extends Model
{
  use HasFactory;
  use \Znck\Eloquent\Traits\BelongsToThrough;

  protected $appends = ['edited'];
  protected $hidden = ['updated_at'];

  public function video() {
    return $this->belongsToThrough(Video::class, Comment::class);
  }

  public function comment() {
    return $this->belongsTo(Comment::class);
  }

  protected function createdAt(): Attribute {
    return new Attribute(
      get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->diffForHumans(),
    );
  }
  
  protected function updatedAt(): Attribute {
    return new Attribute(
      get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->diffForHumans(),
    );
  }
  
  protected function edited(): Attribute {
    return new Attribute(
      get: fn() => $this->created_at !== $this->updated_at,
    );
  }
}