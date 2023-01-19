<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Channel extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'description',
    'country',
    'logo_path',
    'logo_url'
  ];

  protected $hidden = [
    'logo_path',
    'total_likes',
    'total_comments',
    'updated_at'
  ];

  public function videos() {
    return $this->hasMany(Video::class);
  }
  public function replies() {
    return $this->hasManyThrough(Reply::class, Comment::class, 'commenter_id');
  }
  public function views() {
    return $this->hasManyThrough(View::class, Video::class);
  }
  protected function createdAt(): Attribute {
    return new Attribute(
      get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->format('jS M, Y'),
    );
  }
}