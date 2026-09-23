<?php

namespace App\Http\Controllers;

use App\Mail\BoardInvitationMail;
use App\Models\Board;
use App\Models\BoardInvitation;
use App\Models\BoardMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BoardMemberController extends Controller
{
    /** List the board's members and pending invitations (owner only). */
    public function index(Board $board)
    {
        $this->ensureBoardOwner($board);

        $members = $board->memberUsers()->get()->map(fn (User $u) => [
            'user_id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->pivot->role,
        ]);

        $invitations = $board->invitations()
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get(['id', 'email', 'created_at', 'expires_at']);

        return response()->json([
            'owner' => [
                'user_id' => $board->user_id,
                'name' => optional($board->user)->name,
                'email' => optional($board->user)->email,
            ],
            'members' => $members,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Invite a user (by email) to the board. Mirrors AdminController::inviteUser:
     * provisions an account when the email is unknown, and emails the invitation
     * best-effort (the accept link + any temp password are also returned so the
     * owner can share them manually when mail transport is the `log` driver).
     */
    public function invite(Request $request, Board $board)
    {
        $this->ensureBoardOwner($board);

        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
        ]);
        $email = $validated['email'];

        if (strcasecmp($email, $request->user()->email) === 0) {
            throw ValidationException::withMessages(['email' => 'You already own this board.']);
        }

        $existing = User::where('email', $email)->first();

        if ($existing && $board->members()->where('user_id', $existing->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'This person is already a member of the board.']);
        }

        $isNewAccount = $existing === null;
        $temporaryPassword = null;

        if ($isNewAccount) {
            $temporaryPassword = Str::password(12);
            $user = User::create([
                'name' => Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make($temporaryPassword),
                'is_admin' => false,
            ]);
        } else {
            $user = $existing;
        }

        // Reuse a pending invitation for this board+email if one exists, else create.
        $invitation = $board->invitations()
            ->whereNull('accepted_at')
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        $token = Str::random(48);
        if ($invitation) {
            $invitation->update([
                'token' => $token,
                'invited_by' => $request->user()->id,
                'expires_at' => now()->addDays(14),
            ]);
        } else {
            $invitation = $board->invitations()->create([
                'email' => $email,
                'token' => $token,
                'role' => 'member',
                'invited_by' => $request->user()->id,
                'expires_at' => now()->addDays(14),
            ]);
        }

        $acceptUrl = rtrim(config('app.url'), '/').'/invite/'.$token;
        $emailSent = true;

        try {
            Mail::to($email)->send(new BoardInvitationMail(
                boardName: $board->name,
                invitedBy: $request->user()->name,
                acceptUrl: $acceptUrl,
                email: $email,
                isNewAccount: $isNewAccount,
                temporaryPassword: $temporaryPassword,
            ));
        } catch (\Throwable $e) {
            // Never fail the invite because mail transport is down — the owner
            // still gets the link (and any temp password) to share manually.
            $emailSent = false;
            Log::warning('Board invite email failed to send', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'invitation' => $invitation->only(['id', 'email', 'created_at', 'expires_at']),
            'email_sent' => $emailSent,
            'is_new_account' => $isNewAccount,
            'temporary_password' => $temporaryPassword,
            'accept_url' => $acceptUrl,
        ], 201);
    }

    /** Remove a member from the board (owner only). */
    public function revoke(Board $board, User $user)
    {
        $this->ensureBoardOwner($board);

        $board->members()->where('user_id', $user->id)->delete();

        return response()->json(['success' => true]);
    }

    /** Cancel a pending invitation (owner only). */
    public function cancelInvitation(Board $board, BoardInvitation $invitation)
    {
        $this->ensureBoardOwner($board);

        abort_unless($invitation->board_id === $board->id, 404);
        $invitation->delete();

        return response()->json(['success' => true]);
    }

    /** Invitation details for the accept screen (any authenticated user). */
    public function showInvitation(string $token)
    {
        $invitation = BoardInvitation::where('token', $token)->firstOrFail();
        $board = Board::withoutGlobalScope('owner')->findOrFail($invitation->board_id);

        return response()->json([
            'board_name' => $board->name,
            'invited_by' => optional($invitation->inviter)->name,
            'email' => $invitation->email,
            'accepted' => $invitation->accepted_at !== null,
            'expired' => $invitation->isExpired(),
        ]);
    }

    /**
     * Accept an invitation. Requires the logged-in user's email to match the
     * invited email (this is why the accept link routes through login). Grants
     * membership and returns the now-accessible board.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = BoardInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'This invitation has expired.']);
        }

        $user = $request->user();
        abort_unless(strcasecmp($user->email, $invitation->email) === 0, 403, 'This invitation was sent to a different email address.');

        BoardMember::firstOrCreate(
            ['board_id' => $invitation->board_id, 'user_id' => $user->id],
            ['role' => $invitation->role],
        );

        if ($invitation->accepted_at === null) {
            $invitation->update(['accepted_at' => now()]);
        }

        // The board is now accessible via the membership row just created; load it
        // without the ownership scope to be robust within this same request.
        $board = Board::withoutGlobalScope('owner')->findOrFail($invitation->board_id);

        return response()->json($board);
    }
}
