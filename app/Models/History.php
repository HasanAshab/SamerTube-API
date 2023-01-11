<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
class History extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $casts = [
      'date' => 'date:y-m-d'
    ];
    
    function video(){
      return $this->hasOne(Video::class, 'id', 'history');
    }
}
