<?php

namespace App\Providers;

use App\Providers\Searched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PushSearchHistory
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
     * @param  \App\Providers\Searched  $event
     * @return void
     */
    public function handle(Searched $event)
    {
        //
    }
}
