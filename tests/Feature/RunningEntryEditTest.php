<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunningEntryEditTest extends TestCase
{
    use RefreshDatabase;

    private function runningEntry(): array
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work', 'user_id' => $user->id]);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha', 'user_id' => $user->id]);
        $other = Project::create(['board_id' => $board->id, 'name' => 'Beta', 'user_id' => $user->id]);
        $entry = TimeEntry::create([
            'project_id' => $project->id,
            'description' => 'Working',
            'start_time' => now()->subMinutes(10),
            'user_id' => $user->id,
        ]);

        return [$user, $entry, $project, $other];
    }

    public function test_running_entry_only_changes_its_start_time(): void
    {
        [$user, $entry, $project, $other] = $this->runningEntry();
        $newStart = now()->subHour()->startOfMinute();

        $this->actingAs($user)->putJson("/api/entries/{$entry->id}", [
            'start_time' => $newStart->toIso8601String(),
            'project_id' => $other->id,
            'description' => 'Changed',
            'end_time' => now()->toIso8601String(),
        ])->assertSuccessful();

        $entry->refresh();
        $this->assertTrue($entry->start_time->equalTo($newStart));
        $this->assertNull($entry->end_time);
        $this->assertEquals($project->id, $entry->project_id);
        $this->assertEquals('Working', $entry->description);
    }

    public function test_running_entry_start_time_cannot_be_in_the_future(): void
    {
        [$user, $entry] = $this->runningEntry();

        $this->actingAs($user)->putJson("/api/entries/{$entry->id}", [
            'start_time' => now()->addHour()->toIso8601String(),
        ])->assertStatus(422);
    }
}
