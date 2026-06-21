<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Idle threshold (minutes)
    |--------------------------------------------------------------------------
    |
    | A running timer whose last heartbeat is older than this is considered
    | abandoned (e.g. the browser tab was closed or crashed) and is stopped
    | server-side by the `timers:stop-stale` command.
    |
    | Keep this LONGER than the client-side idle modal (30 min idle + 2 min
    | grace = ~32 min) so that, when a tab is actually open, the user always
    | gets the "still working?" prompt first and the server only steps in
    | when there is no tab left to ask.
    |
    */

    'idle_minutes' => (int) env('TIMER_IDLE_MINUTES', 60),

];
