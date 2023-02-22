<?php
namespace App\Traits;
use App\Models\Report;

trait ReportUtility {
  
  public static function bootReportUtility() {
    static::deleting(function ($model) {
      $model->reports()->delete();
    });
  }
  
  public function reports() {
    return $this->morphMany(Report::class, 'reportable');
  }
  
  public static function reportAt($id, $reason){
    return Report::updateOrCreate(
      [
        'user_id' => auth()->id(),
        'reportable_type' => get_called_class(),
        'reportable_id' => $id,
      ],
      ['reason' => $reason]
    );
  }
  
  public function report($reason){
    return Report::updateOrCreate(
      [
        'user_id' => auth()->id(),
        'reportable_type' => get_called_class(),
        'reportable_id' => $this->id,
      ],
      ['reason' => $reason]
    );
  }
  
  public function unreport(){
    return Report::where('user_id', auth()->id)->where('reportable_type', get_class($this))->where('reportable_id', $this->id)->delete();
  }
  
}