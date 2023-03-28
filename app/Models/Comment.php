<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ReviewUtility;
use App\Traits\ReportUtility;
use Carbon\Carbon;
use App\Events\Commented;

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
    return $this->belongsTo(User::class);
  }
  
  public function replies() {
    return $this->hasMany(Reply::class);
  }
  
  public function isCreator() {
    return $this->commenter_id === $this->commentable->channel_id;
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
    static::created(function (Comment $comment) {
      event(new Commented($comment));
    });
  }
}