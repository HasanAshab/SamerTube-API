<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class File extends Model
{
  use HasFactory;
  public $timestamps = false;

  protected $fillable = [
    'fileable_id',
    'fileable_type',
    'name',
    'path',
    'link'
  ];
  
  public static function getNextId() {
    $statement = DB::select("show table status like 'files'");
    return $statement[0]->Auto_increment;
  }
}