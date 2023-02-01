<?php
namespace App\Traits;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tag;

trait TagUtility{
  public function tags(){
    return $this->morphMany(Tag::class, 'tagable');
  }
  
  public function setTags($tagNames){
    $this->tags()->delete();
    foreach ($tagNames as $tagName){
      $tag = Tag::create([
        'tagable_id' => $this->id,
        'tagable_type' => $this->morphable_type,
        'name' => $tagName
      ]);
    }
  }
}