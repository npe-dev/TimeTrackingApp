<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureSessionAuth($request);

        return $request->user()->tokens()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'last_used_at', 'created_at']);
    }

    public function store(Request $request)
    {
        $this->ensureSessionAuth($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = $request->user()->createToken($validated['name']);

        return response()->json([
            'id' => $token->accessToken->id,
            'name' => $token->accessToken->name,
            // The plaintext token is returned only once, at creation time.
            'plain_text_token' => $token->plainTextToken,
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $this->ensureSessionAuth($request);

        $request->user()->tokens()->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Token management must come from the browser session, never from a bearer
     * token — so a leaked MCP token cannot mint or revoke other tokens.
     */
    private function ensureSessionAuth(Request $request): void
    {
        abort_if(
            $request->user()->currentAccessToken() instanceof PersonalAccessToken,
            403,
            'Token management is only available from a logged-in browser session.'
        );
    }
}
