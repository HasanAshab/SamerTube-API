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
  protected $appends = ['vote_rate', 'voted'];
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
  
  protected function voteRate(): Attribute {
    return new Attribute(
      get: function () {
        $total_votes = Post::find($this->post_id)->total_votes;
        return ($this->vote_count*100)/$total_votes;
      }
    );
  }
  
  protected function voted(): Attribute {
    return new Attribute(
      get: fn() => Vote::where('poll_id', $this->id)->where('voter_id', auth()->id())->exists()
    );
  }
}