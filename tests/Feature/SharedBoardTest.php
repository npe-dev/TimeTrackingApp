<?php

namespace Tests\Feature;

use App\Mail\BoardInvitationMail;
use App\Models\Board;
use App\Models\BoardInvitation;
use App\Models\BoardMember;
use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SharedBoardTest extends TestCase
{
    use RefreshDatabase;

    private function makeBoard(User $owner): Board
    {
        $board = Board::create(['name' => 'Shared', 'user_id' => $owner->id]);
        Project::create(['board_id' => $board->id, 'name' => 'General', 'user_id' => $owner->id]);
        Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);

        return $board;
    }

    public function test_invite_provisions_a_new_user_and_sends_mail(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        $board = $this->makeBoard($owner);

        $this->actingAs($owner)
            ->postJson("/api/boards/{$board->id}/invitations", ['email' => 'bob@example.com'])
            ->assertCreated()
            ->assertJson(['is_new_account' => true, 'email_sent' => true]);

        $this->assertDatabaseHas('users', ['email' => 'bob@example.com']);
        $this->assertDatabaseHas('board_invitations', ['board_id' => $board->id, 'email' => 'bob@example.com']);
        Mail::assertSent(BoardInvitationMail::class);
    }

    public function test_invite_of_existing_user_does_not_create_account(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        $bob = User::factory()->create(['email' => 'bob@example.com']);
        $board = $this->makeBoard($owner);

        $this->actingAs($owner)
            ->postJson("/api/boards/{$board->id}/invitations", ['email' => 'bob@example.com'])
            ->assertCreated()
            ->assertJson(['is_new_account' => false]);

        $this->assertEquals(1, User::where('email', 'bob@example.com')->count());
    }

    public function test_member_cannot_invite(): void
    {
        // A member can see the board but must not be able to invite others (403);
        // a total stranger can't even resolve the board (404), which is fine too.
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $board = $this->makeBoard($owner);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $member->id, 'role' => 'member']);

        $this->actingAs($member)
            ->postJson("/api/boards/{$board->id}/invitations", ['email' => 'x@example.com'])
            ->assertForbidden();

        $stranger = User::factory()->create();
        $this->actingAs($stranger)
            ->postJson("/api/boards/{$board->id}/invitations", ['email' => 'x@example.com'])
            ->assertNotFound();
    }

    public function test_accept_grants_access_and_scopes_visibility(): void
    {
        $owner = User::factory()->create();
        $bob = User::factory()->create(['email' => 'bob@example.com']);
        $stranger = User::factory()->create();
        $board = $this->makeBoard($owner);

        $invitation = BoardInvitation::create([
            'board_id' => $board->id,
            'email' => 'bob@example.com',
            'token' => 'tok-123',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(14),
        ]);

        $this->actingAs($bob)
            ->postJson('/api/invitations/tok-123/accept')
            ->assertSuccessful()
            ->assertJson(['id' => $board->id]);

        $this->assertDatabaseHas('board_members', ['board_id' => $board->id, 'user_id' => $bob->id]);

        // Bob now sees the board; a stranger does not.
        $bobBoards = collect($this->actingAs($bob)->getJson('/api/boards')->json())->pluck('id');
        $this->assertContains($board->id, $bobBoards);

        $strangerBoards = collect($this->actingAs($stranger)->getJson('/api/boards')->json())->pluck('id');
        $this->assertNotContains($board->id, $strangerBoards);
    }

    public function test_accept_with_wrong_email_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $dave = User::factory()->create(['email' => 'dave@example.com']);
        $board = $this->makeBoard($owner);

        BoardInvitation::create([
            'board_id' => $board->id,
            'email' => 'carol@example.com',
            'token' => 'tok-abc',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(14),
        ]);

        $this->actingAs($dave)
            ->postJson('/api/invitations/tok-abc/accept')
            ->assertForbidden();
    }

    public function test_accept_expired_invitation_fails(): void
    {
        $owner = User::factory()->create();
        $bob = User::factory()->create(['email' => 'bob@example.com']);
        $board = $this->makeBoard($owner);

        BoardInvitation::create([
            'board_id' => $board->id,
            'email' => 'bob@example.com',
            'token' => 'tok-old',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($bob)
            ->postJson('/api/invitations/tok-old/accept')
            ->assertStatus(422);
    }

    public function test_member_can_work_the_board_but_not_manage_it(): void
    {
        $owner = User::factory()->create();
        $bob = User::factory()->create();
        $board = $this->makeBoard($owner);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $bob->id, 'role' => 'member']);
        $column = $board->columns()->first();
        $project = $board->projects()->first();

        // Member CAN create a task and see the board's projects.
        $this->actingAs($bob)
            ->postJson('/api/tasks', ['column_id' => $column->id, 'project_id' => $project->id, 'title' => 'Bob task'])
            ->assertSuccessful();

        $projectIds = collect($this->actingAs($bob)->getJson("/api/projects?board_id={$board->id}")->json())->pluck('id');
        $this->assertContains($project->id, $projectIds);

        // Member CANNOT manage board settings / projects / labels / automations.
        $this->actingAs($bob)->putJson("/api/boards/{$board->id}", ['name' => 'Hijack'])->assertForbidden();
        $this->actingAs($bob)->postJson('/api/projects', ['board_id' => $board->id, 'name' => 'X'])->assertForbidden();
        $this->actingAs($bob)->postJson("/api/boards/{$board->id}/labels", ['name' => 'L'])->assertForbidden();
        $this->actingAs($bob)->getJson("/api/automations?board_id={$board->id}")->assertForbidden();
        $this->actingAs($bob)->postJson('/api/automations', ['board_id' => $board->id, 'actions' => []])->assertForbidden();
        $this->actingAs($bob)->patchJson("/api/boards/{$board->id}/report-toggle")->assertForbidden();
    }

    public function test_timer_and_reports_are_per_user_on_a_shared_board(): void
    {
        $owner = User::factory()->create();
        $bob = User::factory()->create();
        $board = $this->makeBoard($owner);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $bob->id, 'role' => 'member']);
        $column = $board->columns()->first();
        $project = $board->projects()->first();
        $task = Task::create(['column_id' => $column->id, 'project_id' => $project->id, 'title' => 'Card', 'position' => 0]);

        // Owner and Bob each log time on the same card.
        TimeEntry::create([
            'task_id' => $task->id, 'project_id' => $project->id, 'user_id' => $owner->id,
            'start_time' => now()->subHours(2), 'end_time' => now()->subHour(),
        ]);
        TimeEntry::create([
            'task_id' => $task->id, 'project_id' => $project->id, 'user_id' => $bob->id,
            'start_time' => now()->subMinutes(30), 'end_time' => now(),
        ]);

        // Each sees only their own entries on the card.
        $this->assertCount(1, $this->actingAs($owner)->getJson("/api/tasks/{$task->id}/time-entries")->json());
        $this->assertCount(1, $this->actingAs($bob)->getJson("/api/tasks/{$task->id}/time-entries")->json());

        // And their own totals in the report summary.
        $ownerReport = $this->actingAs($owner)->getJson("/api/reports/summary?board_id={$board->id}")->json();
        $bobReport = $this->actingAs($bob)->getJson("/api/reports/summary?board_id={$board->id}")->json();
        $this->assertEquals(1, $ownerReport['total_entries']);
        $this->assertEquals(1, $bobReport['total_entries']);
    }
}
