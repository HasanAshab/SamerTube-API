<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerifyEmailController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\DashboardController;

// Endpoints to Verify email
Route::group([
  'prefix' => 'email/verify',
  'middleware' => ['throttle:6,1'],
  'controller' => VerifyEmailController::class,
  'as' => 'verification.'
], function ($router) {
  Route::get('/{id}/{hash}', 'verify')->middleware(['signed'])->name('verify');
  Route::post('/verify/resend', 'resend')->middleware(['auth:sanctum'])->name('send');
});
// Endpoints for Authenticating user and admin
Route::group([
  'prefix' => 'auth',
  'middleware' => ['throttle:10,1'],
  'controller' => AuthController::class
], function ($router) {
  Route::post('register', 'register');
  Route::post('login', 'login');
  Route::get('google', 'googleRedirect');
  Route::get('google/callback', 'loginWithGoogle');
  Route::post('forgot-password', 'sentForgotPasswordLink');
  Route::post('reset-password', 'resetPassword');
  Route::middleware('auth:sanctum')->group(function () {
    Route::get('profile', 'profile')->middleware('wrapApiData');
    Route::get('is-admin', 'isAdmin')->middleware('wrapApiData');
    Route::post('change-password', 'changePassword');
    Route::post('refresh', 'refresh');
    Route::post('logout', 'logout');
    Route::post('logout-all', 'logoutAllDevices');
    Route::delete('delete', 'destroy');
  });
});

// Endpoints for Admin services
Route::group([
  'prefix' => 'c-panel',
  'middleware' => ['auth:sanctum', 'admin'],
  'controller' => AdminController::class
], function ($router) {
  Route::post('make-admin/{id}', 'makeAdmin');
  Route::post('notify', 'sentNotification');
  Route::delete('user/{id}', 'removeUser');
  Route::delete('notification/{id}', 'removeNotification');
  Route::apiResource('category', CategoryController::class)->only(['store', 'destroy']);
  Route::group([
    'prefix' => 'dashboard',
    'middleware' => 'wrapApiData'
  ], function ($router) {
    Route::controller(DashboardController::class)->group(function () {
      Route::get('/', 'getAdminDashboard');
      Route::get('admins', 'getAdmins');
      Route::get('users', 'getUsers');
      Route::get('users/active', 'getActiveUsers');
      Route::get('users/new', 'getNewUsers');
    });
    Route::controller(ReportController::class)->group(function () {
      Route::get('reports', 'getReports');
      Route::get('reports/{type}/{id}', 'getContentReports')->where('id', '[0-9]+');
      Route::get('reports/{type}/top', 'getTopReportedContent');
    });
  });
});


//Endpoints for necessary app services
Route::get('file/{id}', FileController::class)->middleware(['signed', 'throttle:10,1'])->name('file.serve');
Route::get('app/name', fn() => config('app.name'));
Route::get('categories', [CategoryController::class, 'index']);


//Endpoints for guest users
Route::middleware('wrapApiData')->group(function () {
  Route::get('channel/{id}', [ChannelController::class, 'show'])->name('channel.show');
  Route::get('videos/channel/{id?}', [VideoController::class, 'getChannelVideos']);
  Route::get('posts/channel/{id?}', [PostController::class, 'getChannelPosts']);
  Route::get('explore', [VideoController::class, 'explore']);
  Route::get('video/watch/{id}', [VideoController::class, 'watch'])->middleware('signed')->name('video.watch');
  Route::get('search/{term?}', [SearchController::class, 'search']);
  Route::get('suggestions/{query?}', [SearchController::class, 'suggestions']);
});


// Endpoints for authenticated users
Route::middleware(['auth:sanctum', 'verified', 'throttle:50,1'])->group(function () {
  Route::middleware('wrapApiData')->group(function () {
    Route::get('channel', [ChannelController::class, 'index']);
    Route::get('subscriptions', [ChannelController::class, 'subscriptions']);
    Route::get('notification', [NotificationController::class, 'getNotifications']);
    Route::get('video/liked', [VideoController::class, 'getLikedVideos'])->name('videos.liked');
    Route::get('watch-later', [PlaylistController::class, 'getWatchLaterVideos'])->name('watchLater.videos');
    Route::get('comment/highlighted/{video_id}/{comment_id}', [CommentController::class, 'getCommentsWithHighlighted'])->middleware('signed')->name('comments.highlighted');
    Route::get('reply/{id}', [ReplyController::class, 'getReplies']);
    Route::get('reply/highlighted/{comment_id}/{reply_id}', [ReplyController::class, 'getRepliesWithHighlighted'])->middleware('signed')->name('replies.highlighted');
    Route::get('playlist', [PlaylistController::class, 'index']);
    Route::get('playlist/{id}', [PlaylistController::class, 'getPlaylistVideos'])->middleware('signed')->name('playlist.videos');
    Route::get('review/{type}/{id}', [ReviewController::class, 'getReview']);
    Route::get('comment/{type}/{id}', [CommentController::class, 'getComments']);
    Route::get('vote/{id}', [PostController::class, 'getVotedPoll']);
  });
  
  Route::controller(ChannelController::class)->group(function () {
    Route::put('channel', 'update');
    Route::post('subscribe/{channel_id}/{video_id?}', 'subscribe');
    Route::post('unsubscribe/{channel_id}/{video_id?}', 'unsubscribe');
  });
  
  Route::post('view/{id}/{time}', [VideoController::class, 'setViewWatchTime']);
  Route::apiResource('video', VideoController::class)->only(['store', 'update', 'destroy']);

  Route::apiResource('history', HistoryController::class)->only(['index', 'destroy']);
  Route::post('notification/hide/{notification_id}', [NotificationController::class, 'hideNotification']);
    
  Route::controller(CommentController::class)->group(function () {
    Route::get('comment/{type}/{id}', 'index');
    Route::post('comment/{type}/{id}', 'store');
    Route::put('comment/{type}/{id}', 'update');
    Route::delete('comment/{type}/{id}', 'destroy');
    Route::post('heart/comment/{id}', 'giveHeart');
  });
  
  Route::controller(ReplyController::class)->group(function () {
    Route::get('comment/{id}', 'index');
    Route::post('reply/{id}', 'store');
    Route::put('reply/{id}', 'update');
    Route::delete('reply/{id}', 'destroy');
    Route::post('heart/reply/{id}', 'giveHeart');
  });
  
  Route::controller(PlaylistController::class)->group(function () {
    Route::post('playlist/{id}', 'savePlaylist');
    Route::delete('playlist/saved/{id}', 'removeSavedPlaylist');
    Route::post('playlist/{playlist_id}/{video_id}', 'addVideoToPlaylist');
    Route::delete('playlist/{playlist_id}/{video_id}', 'removeVideoFromPlaylist');
    Route::post('watch-later/{video_id}', 'addVideoToWatchLater');
    Route::delete('watch-later/{video_id}', 'removeVideoFromWatchLater');
    Route::apiResource('playlist', PlaylistController::class)->only(['store', 'update', 'destroy']);
  });
  
  Route::post('report/{type}/{id}', [ReportController::class, 'report']);
  
  Route::post('vote/{id}', [PostController::class, 'votePoll']);
  Route::apiResource('post', PostController::class)->only(['store', 'update', 'destroy']);
  
  Route::post('review/{type}/{id}', ReviewController::class);
  
  Route::group([
    'prefix' => 'dashboard',
    'middleware' => 'wrapApiData',
    'controller' => DashboardController::class
  ], function ($router) {
    Route::get('overview', 'getChannelOverview');
    Route::get('audience', 'getChannelAudience');
    Route::get('video/{video_id}/overview', 'getVideoOverview');
    Route::get('video/{video_id}/engagement', 'getVideoEngagement');
    Route::get('video/{video_id}/perfomance', 'getVideoPerfomance');
    Route::get('video/{video_id}/audience', 'getVideoAudience');
    Route::get('videos/previous/rankedby/views', 'getPreviousRankedVideos');
  });
});


use App\Mail\VideoUploadedMail;
use Illuminate\Support\Facades\Mail;

Route::get('test/{user}', function (App\Models\User $user){
  return $user;
});