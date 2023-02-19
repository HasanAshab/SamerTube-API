<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
  use HasFactory;
  
  protected $fillable = [
    'user_id',
    'reportable_type',
    'reportable_id',
    'reason'
  ];
  
  public function reportable() {
    return $this->morphTo();
  }
  
  public static function boot() {
    parent::boot();
    static::creating(function (Report $report) {
      $report->user_id = auth()->id();
    });
  }
}