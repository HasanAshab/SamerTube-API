<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerifyEmailApi;
use App\Http\Controllers\adminApi;
use App\Http\Controllers\AuthApi;
use App\Http\Controllers\channelApi;
use App\Http\Controllers\videoApi;
use App\Http\Controllers\fileApi;
use App\Http\Controllers\DashboardApi;

// Endpoints to Verify email
Route::get('/email/verify/{id}/{hash}', [VerifyEmailApi::class, '__invoke'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verify/resend', [VerifyEmailApi::class, 'resend'])->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');

// Endpoints for Authenticating user and admin
Route::group([
  'prefix' => 'auth',
  'middleware' => ['api', 'auth:sanctum']
], function ($router) {
  Route::post('register', [AuthApi::class, 'register'])->withoutMiddleware(['auth:sanctum']);
  Route::post('login', [AuthApi::class, 'login'])->withoutMiddleware(['auth:sanctum']);
  Route::get('google', [AuthApi::class, 'googleRedirect'])->withoutMiddleware(['auth:sanctum']);
  Route::get('google/callback', [AuthApi::class, 'loginWithGoogle'])->withoutMiddleware(['auth:sanctum']);
  Route::post('change-password', [AuthApi::class, 'changePassword']);
  Route::post('forgot-password', [AuthApi::class, 'sentForgotPasswordLink'])->withoutMiddleware(['auth:sanctum']);
  Route::post('reset-password', [AuthApi::class, 'resetPassword'])->withoutMiddleware(['auth:sanctum']);
  Route::post('logout', [AuthApi::class, 'logout']);
  Route::post('logout-all', [AuthApi::class, 'logoutAllDevices']);
  Route::post('refresh', [AuthApi::class, 'refresh']);
  Route::delete('delete', [AuthApi::class, 'destroy']);
});

// Endpoints for Admin services
Route::group([
  'prefix' => 'admin',
  'middleware' => ['api', 'auth:sanctum', 'abilities:admin'],

], function ($router) {
  Route::post('make-admin/{id}', [adminApi::class, 'makeAdmin']);
  Route::post('category', [adminApi::class, 'addCategory']);
  Route::delete('user/{id}', [adminApi::class, 'removeUser']);
  Route::delete('category/{id}', [adminApi::class, 'removeCategory']);
  Route::delete('notification/{id}', [adminApi::class, 'removeNotification']);
  Route::group([
    'prefix' => 'dashboard'
  ], function ($router) {
    Route::get('/', [adminApi::class, 'dashboard']);
    Route::get('users', [adminApi::class, 'getUsers']);
    Route::get('users/active', [adminApi::class, 'getActiveUsers']);
    Route::get('channels', [adminApi::class, 'getChannels']);
    Route::get('reports/{type}', [adminApi::class, 'getReports']);
    Route::get('admins', [adminApi::class, 'getAdmins']);
  });
});

//Endpoints for  Serve files from server storage
Route::get('file/{type}/{id?}', [fileApi::class, 'index'])->middleware('signed')->name('file.serve');

// Endpoints for logged in users
Route::group([
  'middleware' => ['api', 'auth:sanctum', 'verified']
], function ($router) {
  Route::get('channel', [channelApi::class, 'index']);
  Route::get('channel/{id}', [channelApi::class, 'show'])->name('channel.show');
  Route::put('channel', [channelApi::class, 'update']);
  Route::get('videos/channel/{id?}', [channelApi::class, 'getChannelVideos']);
  Route::post('subscribe/{channel_id}/{video_id?}', [channelApi::class, 'handleSubscribe']);
  Route::get('subscriptions', [channelApi::class, 'subscriptions']);
  //Route::post('video/upload', [videoApi::class, 'store']);
  Route::get('explore', [videoApi::class, 'explore']);
  Route::put('video/{id}', [videoApi::class, 'update']);
  Route::get('video/watch/{id}', [videoApi::class, 'watch'])->middleware('signed')->name('video.watch');
  Route::delete('video/{id}', [videoApi::class, 'destroy']);
  Route::post('view/{id}/{time}', [videoApi::class, 'setViewWatchTime']);
  Route::get('notification', [videoApi::class, 'getNotifications']);
  Route::post('notification/hide/{notification_id}', [videoApi::class, 'hideNotification']);
  Route::get('categories', [videoApi::class, 'category']);
  Route::get('suggestions/{query?}', [videoApi::class, 'suggestions']);
  Route::get('search/{query?}', [videoApi::class, 'search']);
  Route::get('history', [videoApi::class, 'watchHistory']);
  Route::get('video/liked', [videoApi::class, 'getLikedVideos'])->name('videos.liked');
  Route::get('video/watch-later', [videoApi::class, 'getWatchLaterVideos'])->name('videos.watchLater');
  Route::post('review/{video_id}', [videoApi::class, 'postReview']);
  Route::get('review/{video_id}', [videoApi::class, 'getReview']);
  Route::post('comment/{video_id}', [videoApi::class, 'postComment']);
  Route::get('comment/{video_id}', [videoApi::class, 'getComments']);
  Route::get('comment/highlighted/{video_id}/{comment_id}', [videoApi::class, 'getCommentsWithHighlighted'])->middleware('signed')->name('comments.highlighted');
  Route::put('comment/{comment_id}', [videoApi::class, 'updateComment']);
  Route::delete('comment/{comment_id}', [videoApi::class, 'removeComment']);
  Route::post('review/comment/{comment_id}', [videoApi::class, 'postCommentReview']);
  Route::post('reply/{comment_id}', [videoApi::class, 'postReply']);
  Route::get('reply/{comment_id}', [videoApi::class, 'getReplies']);
  Route::get('reply/highlighted/{comment_id}/{reply_id}', [videoApi::class, 'getRepliesWithHighlighted'])->middleware('signed')->name('replies.highlighted');
  Route::delete('reply/{comment_id}', [videoApi::class, 'removeReply']);
  Route::post('review/reply/{reply_id}', [videoApi::class, 'postReplyReview']);
  Route::post('heart/{type}/{id}', [videoApi::class, 'giveHeart']);
  Route::delete('history/{history_id}', [videoApi::class, 'removeHistory']);
  Route::get('playlist', [videoApi::class, 'getPlaylists']);
  Route::post('playlist', [videoApi::class, 'createPlaylist']);
  Route::get('playlist/{id}', [videoApi::class, 'getPlaylistVideos'])->middleware('signed')->name('playlist.videos');
  Route::put('playlist/{id}', [videoApi::class, 'updatePlaylist']);
  Route::delete('playlist/{id}', [videoApi::class, 'removePlaylist']);
  Route::post('playlist/{id}', [videoApi::class, 'savePlaylist']);
  Route::delete('playlist/saved/{id}', [videoApi::class, 'removeSavedPlaylist']);
  Route::post('playlist/{playlist_id}/{video_id}', [videoApi::class, 'addVideoToPlaylist']);
  Route::delete('playlist/{playlist_id}/{video_id}', [videoApi::class, 'removeVideoFromPlaylist']);
  Route::post('watch-later/{video_id}', [videoApi::class, 'addVideoToWatchLater']);
  Route::delete('watch-later/{video_id}', [videoApi::class, 'removeVideoFromWatchLater']);
  Route::post('report/{id}', [videoApi::class, 'report']);
  Route::group([
    'prefix' => 'dashboard'
  ], function ($router) {
    Route::get('overview', [DashboardApi::class, 'getChannelOverview']);
    Route::get('audience', [DashboardApi::class, 'getChannelAudience']);
    Route::get('video/{video_id}/overview', [DashboardApi::class, 'getVideoOverview']);
    Route::get('video/{video_id}/engagement', [DashboardApi::class, 'getVideoEngagement']);
    Route::get('video/{video_id}/perfomance', [DashboardApi::class, 'getVideoPerfomance']);
    Route::get('video/{video_id}/audience', [DashboardApi::class, 'getVideoAudience']);
    Route::get('videos/previous/rankedby/views', [DashboardApi::class, 'getPreviousRankedVideos']);
  });
});
Route::post('video/upload', [videoApi::class, 'store']);

Route::get('/test', function() {});