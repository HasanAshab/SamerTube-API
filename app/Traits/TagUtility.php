<?php
namespace App\Traits;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tag;

trait TagUtility{
  
  public static function bootTagUtility() {
    static::deleting(function ($model) {
      $model->tags()->delete();
    });
  }
  
  public function tags(){
    return $this->morphMany(Tag::class, 'tagable');
  }
  
  
  public function setTags($tagNames){
    $this->tags()->delete();
    $tags = [];
    foreach ($tagNames as $tagName){
      array_push($tags, [
        'tagable_id' => $this->id,
        'tagable_type' => get_class($this),
        'name' => $tagName
        ]);
    }
    $tag = Tag::insert($tags);
  }
}