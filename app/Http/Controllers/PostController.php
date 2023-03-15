<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Poll;
use App\Models\Vote;
use App\Jobs\PublishPost;


class PostController extends Controller
{

  // Create a Community Post
  public function store(Request $request) {
    $request->validate([
      'content' => 'required|string',
      'visibility' => 'required|in:public,scheduled',
      'publish_at' => 'date_format:Y-m-d H:i:s|after_or_equal:'.date(DATE_ATOM),
      'type' => 'required|in:text,text_poll,image_poll,shared',
      'shared_id' => 'exists:posts,id',
      'images' => 'array|min:1|max:5',
      'polls' => 'array|min:2|max:5',
      'poll_images' => 'array|min:2|max:5',
    ]);
    if (!$request->user()->can('create', [Post::class])) {
      abort(405);
    }
    if ($request->type === 'text_poll') {
      $post = Post::create($request->merge(['total_votes' => 0])->only(['content', 'visibility', 'type', 'total_votes']));
      $pollsData = [];
      foreach ($request->polls as $pollName) {
        $pollData = [
          'post_id' => $post->id,
          'name' => $pollName
        ];
        array_push($pollsData, $pollData);
      }
      Poll::insert($pollsData);
    } else if ($request->type === 'image_poll') {
      $post = Post::create($request->merge(['total_votes' => 0])->only(['content', 'visibility', 'type', 'total_votes']));
      $pollsData = [];
      for ($i = 0; $i < count($request->polls); $i++) {
        $poll = new Poll;
        $poll->post_id = $post->id;
        $poll->name = $request->polls[$i];
        $poll->image_url = $poll->attachFile('poll_image-'.$i, $request->poll_images[$i]);
        $poll->save();
      }
    } else if ($request->type === 'text') {
      $post = Post::create($request->only(['content', 'visibility', 'type']));
      if (isset($request->images)) {
        $imageUrls = $post->attachFiles($request->images, true);
      }
    } else if ($request->type === 'shared') {
      $post = Post::create($request->only(['content', 'visibility', 'type', 'shared_id']));
    }

    if ($post) {
      if ($post->visibility === 'scheduled') {
        PublishPost::dispatch($post, true)->delay($request->publish_at);
      }
      return ['success' => true,
        'message' => 'Post successfully created!'];
    }
    return response()->json(['success' => $result,
      'message' => 'Failed to create post!'], 422);
  }

  // Update a Community Post
  public function update(Request $request, $id) {
    $validated = $request->validate([
      'content' => 'required|string'
    ]);
    $post = Post::find($id);
    if (!$request->user()->can('update', [Post::class, $post])) {
      abort(405);
    }
    $result = $post->update($validated);
    if ($result) {
      return ['success' => true,
        'message' => 'Post successfully updated!'];
    }
    return response()->json(['success' => $result,
      'message' => 'Failed to update post!'], 422);
  }

  // Delete a Community Post
  public function destroy($id) {
    $post = Post::find($id);
    if (!auth()->user()->can('delete', [Post::class, $post])) {
      abort(405);
    }
    $result = $post->delete();
    if ($result) {
      return ['success' => true,
        'message' => 'Post successfully updated!'];
    }
    return response()->json(['success' => $result,
      'message' => 'Failed to update post!'], 422);
  }

  // Vote a poll
  public function votePoll($id) {
    $poll = Poll::find($id);
    $post = $poll->post;
    if (!auth()->user()->can('vote', [Post::class, $post])) {
      abort(405);
    }
    $voted_poll = $this->getVotedPoll($post->id);
    if (is_null($voted_poll)) {
      $vote = Vote::create([
        'poll_id' => $id,
        'post_id' => $post->id,
      ]);
      $poll->increment('vote_count', 1);
      $post->increment('total_votes', 1);
    } else {
      Vote::where('voter_id', auth()->id())->where('post_id', $post->id)->delete();
      if ($voted_poll->id != $id) {
        $vote = Vote::create([
          'poll_id' => $id,
          'post_id' => $post->id,
        ]);
        $voted_poll->decrement('vote_count', 1);
        $poll->increment('vote_count', 1);
        $post->increment('total_votes', 1);
      } else {
        $voted_poll->decrement('vote_count', 1);
        $post->decrement('total_votes', 1);
      }
    }
    return ['success' => true];
  }

  // Get posts of a channel [no param for own posts]
  public function getChannelPosts(Request $request, $id = null) {
    $isLoggedIn = auth()->check();
    if (!$isLoggedIn && is_null($id)) {
      abort(405);
    }
    $id = $id??$request->user()->id;
    $post_query = Post::with('polls', 'polls.voted', 'reviewed')->where('channel_id', $id);

    if (!$isLoggedIn || !($request->user()->is_admin || $request->user()->id === $id)) {
      $post_query->where('visibility', 'public');
    }

    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $post_query->offset($offset)->limit($request->limit);
    }
    $posts = $post_query->latest()->get();
    $posts->each(function ($post) {
      if($post->total_votes > 0){
        $post->polls->each(function ($poll) use ($post){
          $poll->vote_rate = ($poll->vote_count*100)/$post->total_votes;
        });
      }
    });
    return $posts;
  }

  // Get which poll user is voted of a post
  protected function getVotedPoll($id) {
    $vote = Vote::where('voter_id',
      auth()->id())->where('post_id',
      $id)->first();
    return is_null($vote)
    ?null
    :$vote->poll;
  }
}