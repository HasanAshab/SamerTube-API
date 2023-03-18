<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
  use HasFactory;
  protected $fillable = [
    'for',
    'data'
  ];
  protected $casts = [
    'data' => 'object'
  ];
  public static function for($name){
    $config = Static::where('for', $name)->first();
    if(is_null($config)){
      return null;
    }
    $data = $config->data;
    cache()->put("config:$name", $data);
    return $data;
  }
}
