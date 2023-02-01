<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
      'tagable_id',
      'tagable_type',
      'name'
    ];
    public function tagable(){
        return $this->morphTo();
    }
}
