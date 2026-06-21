<?php

namespace Tests\Feature;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StopStaleTimersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stops_a_running_timer_with_a_stale_heartbeat(): void
    {
        config(['timer.idle_minutes' => 10]);
        $user = User::factory()->create();

        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'description' => 'abandoned',
            'start_time' => now()->subHours(2),
            'last_heartbeat' => now()->subMinutes(15),
        ]);

        $this->artisan('timers:stop-stale')->assertSuccessful();

        $entry->refresh();
        $this->assertNotNull($entry->end_time);
        // Stop is backdated to the last heartbeat, not "now".
        $this->assertEqualsWithDelta(
            now()->subMinutes(15)->timestamp,
            $entry->end_time->timestamp,
            2
        );
    }

    public function test_it_leaves_a_recently_active_timer_running(): void
    {
        config(['timer.idle_minutes' => 10]);
        $user = User::factory()->create();

        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'start_time' => now()->subMinutes(30),
            'last_heartbeat' => now()->subMinutes(2),
        ]);

        $this->artisan('timers:stop-stale')->assertSuccessful();

        $this->assertNull($entry->refresh()->end_time);
    }

    public function test_it_does_not_touch_already_stopped_entries(): void
    {
        config(['timer.idle_minutes' => 10]);
        $user = User::factory()->create();

        $end = now()->subDay();
        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'start_time' => now()->subDays(1)->subHour(),
            'end_time' => $end,
            'last_heartbeat' => now()->subDays(1)->subHour(),
        ]);

        $this->artisan('timers:stop-stale')->assertSuccessful();

        $this->assertEqualsWithDelta($end->timestamp, $entry->refresh()->end_time->timestamp, 2);
    }
}
