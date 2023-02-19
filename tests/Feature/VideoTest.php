<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use App\Models\User;
use App\Models\Channel;
use App\Models\Video;
use App\Models\File;
use App\Models\Comment;
use App\Models\Review;
use App\Models\Category;
use App\Jobs\PublishVideo;
use Illuminate\Support\Facades\Artisan;

beforeEach(function() {
  $this->user = User::factory()->create();
  Channel::factory()->create(['id' => $this->user->id]);
  
  $this->admin = User::factory()->create(['is_admin' => 1]);
  Channel::factory()->create(['id' => $this->admin->id]);
});

afterEach(function (){
  Artisan::call('clear:uploads');
});
