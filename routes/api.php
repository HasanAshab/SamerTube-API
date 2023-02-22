<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerifyEmailApi;
use App\Http\Controllers\adminApi;
use App\Http\Controllers\AuthApi;
use App\Http\Controllers\channelApi;
use App\Http\Controllers\videoApi;
use App\Http\Controllers\SearchApi;
use App\Http\Controllers\fileApi;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardApi;

// Endpoints to Verify email
Route::get('/email/verify/{id}/{hash}', [VerifyEmailApi::class, '__invoke'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verify/resend', [VerifyEmailApi::class, 'resend'])->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');

// Endpoints for Authenticating user and admin
Route::group([
  'prefix' => 'auth',
  'middleware' => ['throttle:10,1']
], function ($router) {
  Route::post('register', [AuthApi::class, 'register']);
  Route::post('login', [AuthApi::class, 'login']);
  Route::get('google', [AuthApi::class, 'googleRedirect']);
  Route::get('google/callback', [AuthApi::class, 'loginWithGoogle']);
  Route::post('forgot-password', [AuthApi::class, 'sentForgotPasswordLink']);
  Route::post('reset-password', [AuthApi::class, 'resetPassword']);
  Route::middleware('auth:sanctum')->group(function () {
    Route::get('profile', [AuthApi::class, 'profile'])->middleware('wrapApiData');
    Route::get('is-admin', [AuthApi::class, 'isAdmin'])->middleware('wrapApiData');
    Route::post('change-password', [AuthApi::class, 'changePassword']);
    Route::post('refresh', [AuthApi::class, 'refresh']);
    Route::post('logout', [AuthApi::class, 'logout']);
    Route::post('logout-all', [AuthApi::class, 'logoutAllDevices']);
    Route::delete('delete', [AuthApi::class, 'destroy']);
  });
});

// Endpoints for Admin services
Route::group([
    'prefix' => 'c-panel',
    'middleware' => ['auth:sanctum', 'admin']
  ], function ($router) {
    Route::post('make-admin/{id}', [adminApi::class, 'makeAdmin']);
    Route::post('notify', [adminApi::class, 'sentNotification']);
    Route::post('category', [adminApi::class, 'addCategory']);
    Route::delete('user/{id}', [adminApi::class, 'removeUser']);
    Route::delete('category/{id}', [adminApi::class, 'removeCategory']);
    Route::delete('notification/{id}', [adminApi::class, 'removeNotification']);
  Route::group([
    'prefix' => 'dashboard',
    'middleware' => 'wrapApiData'
  ], function ($router) {
    Route::get('/', [adminApi::class, 'dashboard']);
    Route::get('admins', [adminApi::class, 'getAdmins']);
    Route::get('users', [adminApi::class, 'getUsers']);
    Route::get('users/active', [adminApi::class, 'getActiveUsers']);
    Route::get('users/new', [adminApi::class, 'getNewUsers']);
    Route::get('reports', [ReportController::class, 'getReports']);
    Route::get('reports/{type}/{id}', [ReportController::class, 'getContentReports'])->where('id', '[0-9]+');
    Route::get('reports/{type}/top', [ReportController::class, 'getTopReportedContent']);
  });
});


//Endpoints for necessary app services
Route::get('file/{id}', [fileApi::class, 'index'])->middleware(['signed', 'throttle:10,1'])->name('file.serve');
Route::get('app/name', fn() => config('app.name'));
Route::get('categories', [videoApi::class, 'category']);


//Endpoints for guest users
Route::middleware('wrapApiData')->group(function () {
  Route::get('channel/{id}', [channelApi::class, 'show'])->name('channel.show');
  Route::get('videos/channel/{id?}', [channelApi::class, 'getChannelVideos']);
  Route::get('posts/channel/{id?}', [channelApi::class, 'getChannelPosts']);
  Route::get('explore', [videoApi::class, 'explore']);
  Route::get('video/watch/{id}', [videoApi::class, 'watch'])->middleware('signed')->name('video.watch');
  Route::get('search/{term?}', [SearchApi::class, 'search']);
  Route::get('suggestions/{query?}', [SearchApi::class, 'suggestions']);
});


// Endpoints for authenticated users
Route::middleware(['auth:sanctum', 'verified', 'throttle:50,1'])->group(function () {
  Route::middleware('wrapApiData')->group(function () {
    Route::get('channel', [channelApi::class, 'index']);
    Route::get('subscriptions', [channelApi::class, 'subscriptions']);
    Route::get('notification', [videoApi::class, 'getNotifications']);
    Route::get('history', [videoApi::class, 'watchHistory']);
    Route::get('video/liked', [videoApi::class, 'getLikedVideos'])->name('videos.liked');
    Route::get('video/watch-later', [videoApi::class, 'getWatchLaterVideos'])->name('videos.watchLater');
    Route::get('comment/{video_id}', [videoApi::class, 'getComments']);
    Route::get('comment/highlighted/{video_id}/{comment_id}', [videoApi::class, 'getCommentsWithHighlighted'])->middleware('signed')->name('comments.highlighted');
    Route::get('reply/{comment_id}', [videoApi::class, 'getReplies']);
    Route::get('reply/highlighted/{comment_id}/{reply_id}', [videoApi::class, 'getRepliesWithHighlighted'])->middleware('signed')->name('replies.highlighted');
    Route::get('playlist', [videoApi::class, 'getPlaylists']);
    Route::get('playlist/{id}', [videoApi::class, 'getPlaylistVideos'])->middleware('signed')->name('playlist.videos');
    Route::get('review/{type}/{id}', [ReviewController::class, 'getReview']);
    Route::get('comment/post/{id}', [videoApi::class, 'getPostComments']);
  });
  Route::put('channel', [channelApi::class, 'update']);
  Route::post('subscribe/{channel_id}/{video_id?}', [channelApi::class, 'subscribe']);
  Route::post('unsubscribe/{channel_id}/{video_id?}', [channelApi::class, 'unsubscribe']);
  Route::post('video/upload', [videoApi::class, 'store']);
  Route::put('video/{id}', [videoApi::class, 'update']);
  Route::delete('video/{id}', [videoApi::class, 'destroy']);
  Route::post('view/{id}/{time}', [videoApi::class, 'setViewWatchTime']);
  Route::post('notification/hide/{notification_id}', [videoApi::class, 'hideNotification']);
  Route::post('comment/{video_id}', [videoApi::class, 'createComment']);
  Route::put('comment/{comment_id}', [videoApi::class, 'updateComment']);
  Route::delete('comment/{comment_id}', [videoApi::class, 'removeComment']);
  Route::post('reply/{comment_id}', [videoApi::class, 'postReply']);
  Route::delete('reply/{comment_id}', [videoApi::class, 'removeReply']);
  Route::post('heart/{type}/{id}', [videoApi::class, 'giveHeart']);
  Route::delete('history/{history_id}', [videoApi::class, 'removeHistory']);
  Route::post('playlist', [videoApi::class, 'createPlaylist']);
  Route::put('playlist/{id}', [videoApi::class, 'updatePlaylist']);
  Route::delete('playlist/{id}', [videoApi::class, 'removePlaylist']);
  Route::post('playlist/{id}', [videoApi::class, 'savePlaylist']);
  Route::delete('playlist/saved/{id}', [videoApi::class, 'removeSavedPlaylist']);
  Route::post('playlist/{playlist_id}/{video_id}', [videoApi::class, 'addVideoToPlaylist']);
  Route::delete('playlist/{playlist_id}/{video_id}', [videoApi::class, 'removeVideoFromPlaylist']);
  Route::post('watch-later/{video_id}', [videoApi::class, 'addVideoToWatchLater']);
  Route::delete('watch-later/{video_id}', [videoApi::class, 'removeVideoFromWatchLater']);
  Route::post('report/{type}/{id}', [ReportController::class, 'report']);
  Route::post('post', [videoApi::class, 'createPost']);
  Route::put('post/{id}', [videoApi::class, 'updatePost']);
  Route::delete('post/{id}', [videoApi::class, 'deletePost']);
  Route::post('comment/post/{id}', [videoApi::class, 'createPostComment']);
  Route::post('vote/{id}', [videoApi::class, 'votePoll']);
  Route::post('review/{type}/{id}', [ReviewController::class, 'review']);
  Route::group([
    'prefix' => 'dashboard',
    'middleware' => 'wrapApiData'
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