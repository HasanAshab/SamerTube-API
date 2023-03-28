<?php

namespace App\Providers;

use App\Events\Posted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPostedToSubscribers
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Posted  $event
     * @return void
     */
    public function handle(Posted $event)
    {
        //
    }
}
