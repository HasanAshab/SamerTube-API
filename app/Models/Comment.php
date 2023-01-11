<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Comment extends Model
{
    use HasFactory;
    
    public function video(){
      return $this->belongsTo(Video::class);
    }
    
    public function replies(){
      return $this->hasMany(Reply::class)->join('channels', 'channels.id', '=', 'replies.replier_id')->select('replies.*', 'channels.name', 'channels.logo_url')->orderByDesc('like_count')->latest();
    }
    
    protected function createdAt(): Attribute{
    return new Attribute(
      get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->diffForHumans(),
    );
  }
}
