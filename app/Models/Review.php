<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
  use HasFactory;
  public $timestamps = false;

  protected $fillable = [
    'reviewer_id',
    'reviewable_type',
    'reviewable_id',
    'review'
  ];
  public function reviewable() {
    return $this->morphTo();
  }

  public static function boot() {
    parent::boot();
    static::creating(function (Review $review) {
      $review->reviewer_id = auth()->id();
    });
  }
}