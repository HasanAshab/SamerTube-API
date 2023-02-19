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

  public function replies() {
    return $this->hasMany(Reply::class)->join('channels', 'channels.id', '=', 'replies.replier_id')->select('replies.*', 'channels.name', 'channels.logo_url')->orderByDesc('like_count')->latest();
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