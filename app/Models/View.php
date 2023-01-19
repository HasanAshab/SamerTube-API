<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
class View extends Model
{
  use HasFactory;
  
  public function video(){
    return $this->belongsTo(Video::class);
  }
}