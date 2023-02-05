<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\videoApi;
use App\Http\Controllers\PostmanController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/postman', [PostmanController::class, 'index']);
Route::get('/get-token/{id}', [PostmanController::class, 'getToken']);


Route::view('/', 'welcome');
Route::post('/', [videoApi::class, 'store']);
