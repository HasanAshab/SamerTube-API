<?php
namespace App\Traits;
use App\Models\Review;

trait ReviewUtility {

  public static function bootReviewUtility() {
    static::deleting(function ($model) {
      $model->reviews()->delete();
    });
  }

  public function reviews() {
    return $this->morphMany(Review::class, 'reviewable');
  }
  
  public function reviewed(){
    return $this->morphOne(Review::class, 'reviewable')->where('reviewer_id', auth()->id());
  }
  
  public static function liked($limit = null, $offset = null){
    $review_query = Review::with('reviewable')->where('reviewable_type', get_called_class())->where('reviewer_id', auth()->id())->where('review', 1);
    if(!is_null($limit)){
      $offset = is_null($offset)?0:$offset;
      $review_query->offset($offset)->limit($limit);
    }
    return $review_query->get();
  }

  public function review($review_code) {
    $review = Review::updateOrCreate(
      ['reviewable_type' => get_class($this), 'reviewable_id' => $this->id],
      ['review' => $review_code]
    );
    $this->like_count = Review::where('reviewable_type', get_class($this))->where('reviewable_id', $this->id)->where('review', 1)->count();
    $this->dislike_count = Review::where('reviewable_type', get_class($this))->where('reviewable_id', $this->id)->where('review', 0)->count();
    $this->save();
    return $review;
  }

  public function unreview(){
    $reviewed = $this->reviewed(true);
    if($reviewed->review === 1){
      $result = $reviewed->delete();
      if($result){
        $this->decrement('like_count', 1);
      }
    }
    else if($reviewed->review === 0){
      $result = $reviewed->delete();
      if($result){
        $this->decrement('dislike_count', 1);
      }
    }
    return $result;
  }

  public static function reviewedAt($id, $get_model = false){
    $user_id = auth()->id();
    $review = Review::where('reviewer_id', $user_id)->where('reviewable_type', get_called_class())->where('reviewable_id', $id)->first();
    if($get_model){
      return $review;
    }
    return ($review)
      ?$review->review
      :null;
  }
}