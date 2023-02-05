<?php 
namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Tag;
use Carbon\Carbon;

trait SearchUtility
{
  
  public static function search_rankable($rank_by)
  {
    $rankable = static::$rankable[$rank_by];
    array_unshift($rankable, ['unmatched', 'asc']);
    return $rankable;
  }
  
  public function scopeSearch(Builder $query, $term, $search_tags=false, $word_by_word=false): Builder
  {
    if ($search_tags){
      $query->whereTagLike($term);
    }
    foreach ($this->searchable as $fild){
      $query->orWhere($fild, 'LIKE', '%'.$term.'%');
      if ($word_by_word){
        foreach (explode(' ', $term) as $word){
          $query->orWhere($fild, 'LIKE', '%'.$word.'%');
        }
      }
    }
    return $query;
  }
  public function scopeWhereTagLike(Builder $query, $tagName): Builder
  {
    $tagables_id = Tag::where('tagable_type', get_class($this))->where('name', 'LIKE', '%'.$tagName.'%')->distinct('tagable_id')->pluck('tagable_id');
    return $query->whereIn('id', $tagables_id);
  }
  
  public function scopeOrWhereTagLike(Builder $query, $tagName): Builder
  {
    $tagables_id = Tag::where('tagable_type', get_class($this))->where('name', 'LIKE', '%'.$tagName.'%')->distinct('tagable_id')->pluck('tagable_id');
    return $query->orWhere(function ($query) use ($tagables_id){
      $query->whereIn('id', $tagables_id);
    });
  }
  
  public function scopeDate(Builder $query, $date_range = 'anytime'): Builder
  {
    $date_range === 'anytime' && $query;
    $date_range === 'hour' && $query->whereBetween('created_at', [Carbon::now()->subHours(1), Carbon::now()]);
    $date_range === 'day' && $query->whereDay('created_at', date('d'));
    $date_range === 'week' && $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->whereYear('created_at', date('Y'));
    $date_range === 'month' && $query->whereMonth('created_at', date('m'));
    $date_range === 'year' && $query->whereYear('created_at', date('Y'));
    return $query;
  }
  
  public function scopeRank(Builder $query, $sort_by = 'relevance'): Builder
  {
    foreach (static::$rankable[$sort_by] as $value){
      $query->orderBy($value[0], $value[1]);
    }
    return $query;
  }
}
