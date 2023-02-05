<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\URL;
use App\Rules\CSVRule;
use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Subscriber;
use App\Models\Notification;
use App\Mail\SubscribedMail;
use Mail;

class channelApi extends Controller
{

  protected $maxDataPerRequest = 20;

  //Get own channel details
  public function index(Request $request) {
    return [
      'success' => true,
      'author' => true,
      'channel' => $request->user()->channel
    ];
  }

  //Get any channel details
  public function show($id) {
    $channel = Channel::find($id);
    $is_author = (auth()->id() === $channel->id);
    return [
      'success' => true,
      'author' => $is_author,
      'channel' => $channel
    ];
  }

  // Update a channel
  public function update(Request $request) {
    $request->validate([
      'name' => 'bail|required|string|between:2,100',
      'description' => 'bail|required|string|max:500',
      'tags' => ['bail', new CSVRule()],
      'logo' => 'image'
    ]);
    $id = $request->user()->id;
    $channel = Channel::find($id);
    $channel->name = $request->name;
    $channel->description = $request->description;
    if($request->file('logo') !== null){
    if ($channel->logo_path !== null) {
      $this->clear($channel->logo_path);
    }
    $channel->logo_path = $this->upload($request->file('logo'));
    $channel->logo_url = URL::signedRoute('file.serve', ['type' => 'logo', 'id' => $id]);
    }
    if($request->tags !== null){
      $channel->setTags(explode(',', $request->tags));
    }
    $result = $channel->save();
    if ($result) {
      return ['success' => true,
        'message' => 'Channel successfully updated!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to update channel!'
    ], 451);
  }

  // Subscribe a channel
  public function subscribe($channel_id, $video_id = null) {
    $user_id = auth()->user()->id;
    if ($channel_id == $user_id) {
      return response()->json([
        'success' => false,
        'message' => 'You haven\'t permition to subscribe or unsubscribe your own channel!'
      ], 406);
    }
    if (Subscriber::where('channel_id', $channel_id)->where('subscriber_id', $user_id)->exists()) {
      return response()->json([
        'success' => false,
        'message' => 'Channel already exist in your subscriptions!'
      ], 406);
    }
    $channel = Channel::find($channel_id);
    $deleted_subscribe = Subscriber::onlyTrashed()->where('channel_id', $channel_id)->where('subscriber_id', $user_id)->first();
    if ($deleted_subscribe) {
      $deleted_subscribe->video_id = $video_id;
      $deleted_subscribe->status = 1;
      $deleted_subscribe->deleted_at = null;
      $result = $deleted_subscribe->save();
      if ($result) {
        $channel->increment('total_subscribers', 1);
        return ['success' => true,
          'message' => 'Subscribed!'];
      }
      return response()->json(['success' => false], 451);
    }
    $subscriber = new Subscriber;
    $subscriber->channel_id = $channel_id;
    $subscriber->video_id = $video_id;
    $result = $subscriber->save();
    if ($result) {
      $channel->increment('total_subscribers', 1);
      $notification = new Notification;
      $notification->from = $user_id;
      $notification->for = $channel_id;
      $notification->url = route('channel.show', ['id' => $user_id]);
      $notification->logo_url = auth()->user()->channel->logo_url;
      $notification->type = "subscribe";
      $notification->text = "New subscriber: ".auth()->user()->channel->name;
      $notification->save();
      $data = [
        'subject' => $notification->text,
        'subscriber_name' => auth()->user()->channel->name,
        'subscriber_logo_url' => auth()->user()->channel->logo_url,
        'link' => $notification->url
      ];
      Mail::to($channel->user->email)->send(new SubscribedMail($data));
      return ['success' => true,
        'message' => 'Subscribed!'];
    }
    return response()->json(['success' => false], 451);
  }

  // Unsubscribe a channel
  public function unsubscribe($channel_id, $video_id = null) {
    $user_id = auth()->user()->id;
    if ($channel_id == $user_id) {
      return response()->json([
        'success' => false,
        'message' => 'You haven\'t permition to subscribe or unsubscribe your own channel!'
      ], 406);
    }
    $subscriber = Subscriber::where('channel_id', $channel_id)->where('subscriber_id', $user_id)->first();
    if (!$subscriber) {
      return response()->json([
        'success' => false,
        'message' => 'Channel not exist in your subscriptions!'
      ], 406);
    }
    $channel = Channel::find($channel_id);
    $subscriber->update(['video_id' => $video_id, 'status' => -1]);
    $result = $subscriber->delete();
    if ($result) {
      $channel->decrement('total_subscribers', 1);
      return ['success' => true,
        'message' => 'Unsubscribed!'];
    }
    return response()->json(['success' => false], 451);
  }

  // Get videos of a channel [no param for own videos]
  public function getChannelVideos(Request $request, $id = null) {
    $id = $id??$request->user()->id;
    $video_query = ($request->user()->is_admin || $request->user()->id === $id)
      ?Video::where('channel_id', $id)->latest()
      :Video::where('channel_id', $id)->where('visibility', 'public')->latest();
    if(isset($request->limit)){
      $offset = isset($request->offset)
        ?$request->offset
        :0;
      $video_query->offset($offset)->limit($request->limit);
    }
    $videos = $video_query->get();
    return [
      'success' => true,
      'videos' => $videos
    ];
  }

  // Get all Subscribed channel id and name
  public function subscriptions(Request $request) {
    $id = $request->user()->id;
    $subscriptions = Subscriber::where('subscriber_id', $id)->join('channels', 'channels.id', '=', 'subscribers.channel_id')->orderByDesc('total_subscribers')->get(['channel_id', 'name', 'logo_url']);
    return $subscriptions;
  }

  // Save a uploaded file to server storage
  protected function upload($file) {
    $file_extention = $file->extension();
    $file_name = time().$file->getClientOriginalName();
    return $file->storeAs("uploads/logo", $file_name, 'public');
  }

  // Clear Unimportant files from server storage
  protected function clear($path) {
    return unlink(storage_path("app/public/$path"));
  }
}