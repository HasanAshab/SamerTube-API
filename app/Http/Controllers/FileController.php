<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;


class FileController extends Controller
{
  public function __invoke($id) {
    $file = File::find($id);
    $path = storage_path("app/public/".$file->path);
    return response()->file($path);
  }
}