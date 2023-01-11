<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Subscriber;
use App\Models\Notification;

class channelApi extends Controller
{

  protected $maxDataPerRequest = 20;

  //Get own channel details
  public function index(Request $request) {
    return [
      'success' => true,
      'author' => true,
      'channel' => Channel::find($request->user()->id)
    ];
  }

  //Get any channel details
  public function show(Request $request, $id) {
    $channel = Channel::find($id);
    $user_id = $request->user()->id;
    $is_author = ($user_id === $channel->first()->id);
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
      'logo' => 'image'
    ]);
    $id = $request->user()->id;
    if ($request->file('logo') === null) {
      $result = Channel::find($id)->update($request->all());
      if ($result) {
        return ['success' => true,
          'message' => 'Channel successfully updated!'];
      }
      return response()->json([
        'success' => false,
        'message' => 'Failed to update channel!'
      ], 451);
    }
    $channel = Channel::find($id);
    $channel->name = $request->name;
    $channel->description = $request->description;
    if ($channel->logo_path !== null) {
      $this->clear($channel->logo_path);
    }
    $channel->logo_path = $this->upload($request->file('logo'));
    $channel->logo_url = URL::signedRoute('file.serve', ['type' => 'logo', 'id' => $id]);
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

  // Subscribe or Unsubscribe a channel
  public function handleSubscribe(Request $request, $channel_id) {
    $channel = Channel::find($channel_id);
    $user_id = $request->user()->id;
    if ($channel_id == $user_id) {
      return response()->json([
        'success' => false,
        'message' => 'Can\'t subscribe to your own channel'
      ], 406);
    }
    $old_subscribe = Subscriber::where('channel_id', $channel_id)->where('subscriber_id', $user_id);
    if (count($old_subscribe->get()) !== 0) {
      $result = $old_subscribe->delete();
      if ($result) {
        Channel::find($channel_id)->decrement('total_subscribers', 1);
        return ['success' => true,
          'message' => 'Unsubscribed'];
      }
      return response()->json(['success' => false], 451);
    }
    $subscriber = new Subscriber;
    $subscriber->subscriber_id = $user_id;
    $subscriber->channel_id = $channel_id;
    $result = $subscriber->save();
    if ($result) {
      $channel->increment('total_subscribers', 1);
      $subscriber_channel = Channel::find($user_id, ['name', 'logo_url']);
      $notification = new Notification;
      $notification->from = $user_id;
      $notification->for = $channel_id;
      $notification->url = route('channel.show', ['id' => $user_id]);
      $notification->logo_url = $subscriber_channel->logo_url;
      $notification->type = "subscribe";
      $notification->text = "New subscriber: ".$subscriber_channel->name;
      $notification->save();

      return ['success' => true,
        'message' => 'Subscribed'];
    }
    return response()->json(['success' => false], 451);
  }

  // Get videos of a channel [no param for own videos]
  public function getChannelVideos(Request $request, $id = null) {
    $id = $id??$request->user()->id;
    $videos = ($request->user()->tokenCan('admin') || $request->user()->id === $id)
      ?Video::where('channel_id', $id)->latest()->cursorPaginate($this->maxDataPerRequest)
      :Video::where('channel_id', $id)->where('visibility', 'public')->latest()->cursorPaginate($this->maxDataPerRequest);
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