<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\URL;
use App\Rules\CSVRule;
use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\Subscriber;
use App\Models\Notification;
use App\Mail\SubscribedMail;
use Mail;

class ChannelController extends Controller
{

  //Get own channel details
  public function index(Request $request) {
    return $request->user()->channel;
  }

  //Get any channel details
  public function show($id) {
    $channel = Channel::find($id);
    $channel->author = auth()->check() && auth()->id() === $channel->id;
    return $channel;
  }

  // Update a channel
  public function update(Request $request) {
    $request->validate([
      'name' => 'bail|required|string|between:2,100',
      'description' => 'bail|required|string|max:500',
      'country' => 'min:2',
      'tags' => ['bail', new CSVRule()],
      'logo' => 'image'
    ]);
    $id = $request->user()->id;
    $channel = Channel::find($id);
    $channel->name = $request->name;
    $channel->description = $request->description;
    if(!is_null($request->country)){
      $channel->country = $request->country;
    }
    if($request->file('logo') !== null){
    $channel->removeFiles('logo');
    $logo_url = $channel->attachFile('logo', $request->file('logo'), true);
    $channel->logo_url = $logo_url;
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
    ], 422);
  }

  // Subscribe a channel
  public function subscribe($channel_id, $video_id = null) {
    $user_id = auth()->user()->id;
    if ($channel_id == $user_id) {
      abort(405);
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
      return response()->json(['success' => false], 422);
    }
    $subscriber = new Subscriber;
    $subscriber->channel_id = $channel_id;
    $subscriber->video_id = $video_id;
    $result = $subscriber->save();
    if ($result) {
      return ['success' => true, 'message' => 'Subscribed!'];
    }
    return response()->json(['success' => false], 422);
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
    return response()->json(['success' => false], 422);
  }

  // Get all Subscribed channel id and name
  public function subscriptions(Request $request) {
    $id = $request->user()->id;
    $subscriptions = Subscriber::where('subscriber_id', $id)->join('channels', 'channels.id', '=', 'subscribers.channel_id')->orderByDesc('total_subscribers')->get(['channel_id', 'name', 'logo_url']);
    return $subscriptions;
  }
}