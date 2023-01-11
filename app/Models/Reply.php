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
    
    public function video(){
      return $this->belongsToThrough(Video::class, Comment::class);
    }
    
    protected function createdAt(): Attribute{
      return new Attribute(
        get: fn($value) => Carbon::createFromTimeStamp(strtotime($value))->diffForHumans(),
      );
  }
}
