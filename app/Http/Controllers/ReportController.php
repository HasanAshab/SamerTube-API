<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Report;
use DB;

class ReportController extends Controller
{
  public function report(Request $request, $type, $id) {
    $request->validate([
      'reason' => 'required|string|between:10,100'
    ]);
    $Model = $this->getClassByType($type);
    if(method_exists(Gate::getPolicyFor($Model), 'report')){
      $model = $Model::find($id);
      if(!auth()->user()->can('report', [$Model, $model])){
        abort(405);
      }
      $result = $model->report($request->reason);
    }
    else{
      $result = $Model::reportAt($id, $request->reason);
    }
    if ($result) {
      return [
        'success' => true,
        'message' => 'Thanks for reporting!'
      ];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to report!'
    ], 451);
  }
  
   // Get all reports
  public function getReports(Request $request) {
    $report_query = Report::with('reportable')->latest();
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $report_query->offset($offset)->limit($request->limit);
    }
    return $report_query->get();
  }
  
  // Get all reports of a specific content
  public function getContentReports(Request $request, $type, $id) {
    $Model = $this->getClassByType($type);
    $report_query = Report::where('reportable_type', $Model)->where('reportable_id', $id)->latest();
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $report_query->offset($offset)->limit($request->limit);
    }
    return $report_query->get();
  }
  
  // Get top reported content
  public function getTopReportedContent(Request $request, $type){
    $Model = $this->getClassByType($type);
    $report_query = Report::with('reportable')->select('reportable_id', 'reportable_type', DB::raw('count(*) as report_count'))->where('reportable_type', $Model)->groupBy('reportable_id')->orderByDesc('report_count');
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $report_query->offset($offset)->limit($request->limit);
    }   
    return $report_query->get();
  }
  
  protected function getClassByType($type){
    return match($type){
      'channel' => Channel::class,
      'video' => Video::class,
      'post' => Post::class,
      'comment' => Comment::class,
      'reply' => Reply::class,
      default => null
    };
  }
}