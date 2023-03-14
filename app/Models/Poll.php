<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FileUtility;

class Poll extends Model
{
  use HasFactory, FileUtility;
  public $timestamps = false;
  protected $hidden = ['vote_count'];
  protected $fillable = [
    'post_id',
    'name',
    'image_url'
  ];
  public function post() {
    return $this->belongsTo(Post::class);
  }
  
  public function votes() {
    return $this->hasMany(Vote::class);
  }
  
  public function voted() {
    return $this->hasOne(Vote::class)->where('voter_id', auth()->id());
  }
}