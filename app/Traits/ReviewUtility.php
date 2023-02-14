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
  
  public static function liked($limit = null, $offset = null){
    $review_query = Review::where('reviewable_type', get_called_class())->where('reviewer_id', auth()->id())->where('review', 1);
    if(!is_null($limit)){
      $offset = is_null($offset)?0:$offset;
      $review_query->offset($offset)->limit($limit);
    }
    $reviews = $review_query->get();
    $models = collect();
    foreach ($reviews as $review){
      $models->push($review->reviewable);
    }
    return $models;
  }

  public function review($review_code) {
    $user_id = auth()->id();
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
    $user_id = auth()->id();
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

  public function reviewed($get_model = false){
    $user_id = auth()->id();
    $review = Review::where('reviewer_id', $user_id)->where('reviewable_type', get_class($this))->where('reviewable_id', $this->id)->first();
    if($get_model){
      return $review;
    }
    return ($review)
      ?$review->review
      :null;
  }
}