<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Iman\Streamer\VideoStreamer;
use App\Models\File;


class fileApi extends Controller
{
  public function index($id) {
    $file = File::find($id);
    $path = storage_path("app/public/".$file->path);
    return response()->file($path);
  }
}