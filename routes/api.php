<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerifyEmailController;
use App\Http\Controllers\adminApi;
use App\Http\Controllers\userAuthApi;
use App\Http\Controllers\channelApi;
use App\Http\Controllers\videoApi;
use App\Http\Controllers\fileApi;
use Illuminate\Http\Request;
// Endpoints to Verify email
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verify/resend', [VerifyEmailController::class, 'resend'])->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');


// Endpoints for Admin services
Route::group([
  'prefix' => 'admin',
  'middleware' => ['api', 'auth:sanctum', 'abilities:admin'],

], function ($router) {
  Route::post('login', [adminApi::class, 'login'])->withoutMiddleware(['auth:sanctum', 'abilities:admin']);
  Route::post('logout', [adminApi::class, 'logout']);
  Route::post('logout-all', [adminApi::class, 'logoutAllDevices']);
  Route::post('refresh', [adminApi::class, 'refresh']);
  Route::delete('delete', [adminApi::class, 'destroy']);
  Route::post('category', [adminApi::class, 'addCategory']);
  Route::get('user', [adminApi::class, 'getUsers']);
  Route::get('channel', [adminApi::class, 'getChannels']);
  Route::delete('user/{id}', [adminApi::class, 'removeUser']);
  Route::delete('category/{id}', [adminApi::class, 'removeCategory']);
  Route::delete('notification/{id}', [adminApi::class, 'removeNotification']);
});


// Endpoints for Authenticate user
Route::group([
  'prefix' => 'auth',
  'middleware' => ['api', 'auth:sanctum', 'abilities:user']
], function ($router) {
  Route::get('google', [userAuthApi::class, 'googleRedirect'])->withoutMiddleware(['auth:sanctum', 'abilities:user']);
  Route::get('google/callback', [userAuthApi::class, 'loginWithGoogle'])->withoutMiddleware(['auth:sanctum', 'abilities:user']);
  Route::post('logout', [userAuthApi::class, 'logout']);
  Route::post('logout-all', [userAuthApi::class, 'logoutAllDevices']);
  Route::post('refresh', [userAuthApi::class, 'refresh']);
  Route::delete('delete', [userAuthApi::class, 'destroy']);
});

//Endpoints for  Serve files from server storage
Route::get('file/{type}/{id}', [fileApi::class, 'index'])->middleware('signed')->name('file.serve');

// Endpoints for logged in users
Route::group([
  'middleware' => ['api', 'auth:sanctum', 'abilities:user']
], function ($router) {
  Route::get('channel', [channelApi::class, 'index']);
  Route::get('channel/{id}', [channelApi::class, 'show'])->withoutMiddleware('abilities:user')->name('channel.show');
  Route::put('channel', [channelApi::class, 'update']);
  Route::get('videos/channel/{id?}', [channelApi::class, 'getChannelVideos']);
  Route::post('subscribe/{channel_id}', [channelApi::class, 'handleSubscribe']);
  Route::get('subscriptions', [channelApi::class, 'subscriptions']);
  //Route::post('video/upload', [videoApi::class, 'store']);
  Route::get('explore', [videoApi::class, 'explore'])->withoutMiddleware('abilities:user');
  Route::put('video/{id}', [videoApi::class, 'update']);
  Route::get('video/watch/{id}', [videoApi::class, 'watch'])->middleware('signed')->withoutMiddleware('abilities:user')->name('video.watch');
  Route::delete('video/{id}', [videoApi::class, 'destroy'])->withoutMiddleware('abilities:user');
  Route::post('increase-view/{id}', [videoApi::class, 'increaseView'])->withoutMiddleware('abilities:user');
  Route::get('notification', [videoApi::class, 'getNotifications']);
  Route::post('notification/hide/{notification_id}', [videoApi::class, 'hideNotification']);
  Route::get('categories', [videoApi::class, 'category'])->withoutMiddleware('abilities:user');
  Route::get('suggestions/{query?}', [videoApi::class, 'suggestions'])->withoutMiddleware('abilities:user');
  Route::get('search/{query?}', [videoApi::class, 'search'])->withoutMiddleware('abilities:user');
  Route::get('history', [videoApi::class, 'watchHistory']);
  Route::post('review/{video_id}', [videoApi::class, 'postReview']);
  Route::get('review/{video_id}', [videoApi::class, 'getReview']);
  Route::post('comment/{video_id}', [videoApi::class, 'postComment']);
  Route::get('comment/{video_id}', [videoApi::class, 'getComments'])->withoutMiddleware('abilities:user');
  Route::get('comment/highlighted/{video_id}/{comment_id}', [videoApi::class, 'getCommentsWithHighlighted'])->middleware('signed')->withoutMiddleware('abilities:user')->name('comments.highlighted');
  Route::put('comment/{comment_id}', [videoApi::class, 'updateComment']);
  Route::delete('comment/{comment_id}', [videoApi::class, 'removeComment'])->withoutMiddleware('abilities:user');
  Route::post('review/comment/{comment_id}', [videoApi::class, 'postCommentReview']);
  Route::post('reply/{comment_id}', [videoApi::class, 'postReply']);
  Route::get('reply/{comment_id}', [videoApi::class, 'getReplies'])->withoutMiddleware('abilities:user');
  Route::get('reply/highlighted/{comment_id}/{reply_id}', [videoApi::class, 'getRepliesWithHighlighted'])->middleware('signed')->withoutMiddleware('abilities:user')->name('replies.highlighted');
  Route::delete('reply/{comment_id}', [videoApi::class, 'removeReply'])->withoutMiddleware('abilities:user');
  Route::post('review/reply/{reply_id}', [videoApi::class, 'postReplyReview']);
  Route::post('heart/{type}/{id}', [videoApi::class, 'giveHeart']);
  Route::delete('history/{history_id}', [videoApi::class, 'removeHistory']);
});
Route::post('video/upload', [videoApi::class, 'store']);

Route::get('/test', function(Request $req){
  // Test user relation ...
  return $req->user()->channel;
})->middleware('auth:sanctum');