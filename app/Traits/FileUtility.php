<?php
namespace App\Traits;
use App\Models\File;
use Illuminate\Support\Facades\URL;
use DB;
trait FileUtility {

  public static function bootFileUtility() {
    static::deleting(function ($model) {
      $model->files()->delete();
    });
  }
  
  public function getNextId() {
    $statement = DB::select("show table status like '".$this->getTable()."'");
    return $statement[0]->Auto_increment;
  }
  
  public function files() {
    return $this->morphMany(File::class, 'fileable');
  }

  public function filesNamed($name) {
    return File::where('fileable_id', $this->id)->where('fileable_type', get_class($this))->where('name', $name)->get();
  }

  public function attachFiles($files, $model_created = false) {
    $urls = [];
    $fileId = File::getNextId();
    foreach ($files as $name => $file) {
      $file_name = $this->generateName($file);
      $path = $file->storeAs("uploads", $file_name, 'public');
      $fileable_id = ($model_created)
      ?$this->id
      :$this->getNextId();
      $url = URL::signedRoute('file.serve', ['id' => $fileId++]);
      $file = File::create([
        'fileable_id' => $fileable_id,
        'fileable_type' => get_class($this),
        'name' => $name,
        'path' => $path,
        'link' => $url
      ]);
      $urls[$name] = $url;
    }
    return (object)$urls;
  }

  public function attachFile($name, $file, $model_created = false) {
    $file_name = $this->generateName($file);
    $path = $file->storeAs("uploads", $file_name, 'public');
    $fileable_id = ($model_created)
    ?$this->id
    :$this->getNextId();
    $url = URL::signedRoute('file.serve', ['id' => File::getNextId()]);
    $file = File::create([
      'fileable_id' => $fileable_id,
      'fileable_type' => get_class($this),
      'name' => $name,
      'path' => $path,
      'link' => $url,
    ]);
    return $url;
  }

  public function generateName($file){
    return time().'_'.$file->hashName();
  }

  public function removeFiles($name) {
    File::where('fileable_id', $this->id)->where('fileable_type', get_class($this))->where('name', $name)->delete();
    $files = File::where('fileable_id', $this->id)->where('fileable_type', get_class($this))->where('name', $name)->get();
    foreach ($files as $file) {
      unlink(storage_path("app/public/".$file->path));
    }
  }

  public function removeFile($id) {
    $file = File::find($id);
    unlink(storage_path("app/public/".$file->path));
    $file->delete();
  }
}