<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class History extends Model
{
  use HasFactory;
  protected $fillable = [
    'user_id',
    'type',
    'history',
    'created_at'
  ];
  function video() {
    return $this->hasOne(Video::class, 'id', 'history');
  }

  
}