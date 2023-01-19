<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Video;
use App\Models\View;

class UpdateAnalytics extends Command
{
  /**
  * The name and signature of the console command.
  *
  * @var string
  */
  protected $signature = 'update:analytics';

  /**
  * The console command description.
  *
  * @var string
  */
  protected $description = 'Update Watch time and Average view duration';

  /**
  * Execute the console command.
  *
  * @return int
  */
  public function handle() {
    $this->info('Fetching all videos...');
    $videos = Video::all();
    $this->info('Analyzing videos...');
    foreach ($videos as $video) {
      $views = View::where('video_id', $video->id);
      $watch_time = ($views->sum('view_duration'))/3600;
      $average_view_duration = ($views->avg('view_duration')*100)/$video->getAttributes()['duration'];
      $video->watch_time = $watch_time;
      $video->average_view_duration = $average_view_duration;
      if ($video->save()) {
        $this->info('video_id:'.$video->id.' => watch_time:'.$watch_time.', average_view_duration:'.$average_view_duration);
      }
    }
    $this->info('All videos are now Up to date!');
    $this->info('Fetching all channels...');
    $channels = Channel::all();
    $this->info('Analyzing channels...');
    foreach ($channels as $channel){
      $total_views = Video::where('channel_id', $channel->id)->sum('view_count');
      $total_watch_time = Video::where('channel_id', $channel->id)->sum('watch_time');
      $channels->total_views = $total_views;
      $channels->total_watch_time = $total_watch_time;
      if ($video->save()){
        $this->info('channel_id:'.$channel->id.' => total_watch_time:'.$total_watch_time.', total_views:'.$total_views);
      }
    }
    $this->info('All channels are now Up to date!');
    return Command::SUCCESS;
  }
}