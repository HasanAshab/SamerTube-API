<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;
    function channel(){
    return $this->hasOne(Channel::class, 'id', 'channel_id');
  }
}
