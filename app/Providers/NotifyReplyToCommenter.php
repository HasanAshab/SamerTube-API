<?php

namespace App\Providers;

use App\Events\Replied;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyReplyToCommenter
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
     * @param  \App\Events\Replied  $event
     * @return void
     */
    public function handle(Replied $event)
    {
        //
    }
}
