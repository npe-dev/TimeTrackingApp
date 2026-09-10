<?php

namespace App\Http\Controllers;

use App\Mail\UserInvitedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * List every user in the app with their last-login timestamp.
     */
    public function users()
    {
        $users = User::query()
            ->orderByDesc('last_login_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_admin', 'last_login_at', 'created_at']);

        return response()->json($users);
    }

    /**
     * Invite (create) a new user. Admin supplies a name + email and either a
     * temporary password or lets one be generated. The account is created
     * immediately as a normal member; the credentials are emailed (best effort)
     * and also returned so the admin can share them directly — useful when mail
     * is not really delivering (e.g. the `log` mailer).
     */
    public function inviteUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'nullable|string|min:8|max:255',
        ]);

        $temporaryPassword = $validated['password'] ?? Str::password(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'is_admin' => false,
        ]);

        $loginUrl = rtrim(config('app.url'), '/').'/login';
        $emailSent = true;

        try {
            Mail::to($user->email)->send(new UserInvitedMail(
                name: $user->name,
                email: $user->email,
                temporaryPassword: $temporaryPassword,
                loginUrl: $loginUrl,
                invitedBy: $request->user()->name,
            ));
        } catch (\Throwable $e) {
            // Never fail the invite because mail transport is down — the admin
            // still gets the credentials in the response to share manually.
            $emailSent = false;
            Log::warning('Invite email failed to send', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'is_admin', 'last_login_at', 'created_at']),
            'temporary_password' => $temporaryPassword,
            'login_url' => $loginUrl,
            'email_sent' => $emailSent,
        ], 201);
    }
}
