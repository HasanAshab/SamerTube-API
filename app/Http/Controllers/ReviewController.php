<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Models\Video;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Review;

class ReviewController extends Controller
{
  // Like and dislike on a content
  public function __invoke(Request $request, $type, $id) {
    $request->validate([
      'review' => 'required|in:0,1'
    ]);
    $Model = getClassByType($type);
    $model = $Model::find($id);
    if (method_exists(Gate::getPolicyFor($Model), 'review') && !auth()->user()->can('review', [$Model, $model])) {
      abort(405);
    }
    $reviewed = $model->reviewed();
    if ($reviewed === $request->review) {
      $result = $model->unreview();
    }
    else {
      $result = $model->review($request->review);
    }

    if ($result) {
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Get what is the review of user on a specific content
  public function getReview($type, $id) {
    $Model = getClassByType($type);
    $review_code = $Model::reviewedAt($id);
    return ['review' => $review_code];
  }

  protected function getClassByType($type) {
    return match($type) {
      'video' => Video::class,
      'post' => Post::class,
      'comment' => Comment::class,
      'reply' => Reply::class,
      default => null
      };
    }


  }