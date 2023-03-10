<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ReviewUtility;
use App\Traits\ReportUtility;
use Carbon\Carbon;

class Reply extends Model
{
  use HasFactory, ReviewUtility, ReportUtility;
  use \Znck\Eloquent\Traits\BelongsToThrough;

  protected $appends = ['edited'];
  protected $hidden = ['updated_at'];
  protected $fillable = [
    'text',
    'comment_id',
    'replier_id'
  ];
  
  public function replier(){
    return $this->belongsTo(Channel::class);
  }
  
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
  
  public static function boot() {
    parent::boot();
    static::creating(function (Reply $reply) {
      $reply->replier_id = auth()->id();
    });
  }
}