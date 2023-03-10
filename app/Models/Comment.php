<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ReviewUtility;
use App\Traits\ReportUtility;
use Carbon\Carbon;

class Comment extends Model
{
  use HasFactory, ReviewUtility, ReportUtility;
  protected $appends = ['edited'];
  protected $hidden = ['updated_at'];
  protected $fillable = [
    'commenter_id',
    'commentable_type',
    'commentable_id',
    'text'
  ];
  public function commentable() {
    return $this->morphTo();
  }
  
  public function commenter(){
    return $this->belongsTo(Channel::class);
  }
  
  public function replies() {
    return $this->hasMany(Reply::class);
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
    static::creating(function (Comment $comment) {
      $comment->commenter_id = auth()->id();
    });
  }
}