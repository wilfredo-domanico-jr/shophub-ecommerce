<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // A real (throwaway) bcrypt hash checked on login misses so unknown
    // emails take as long as wrong passwords — no timing-based enumeration.
    private const TIMING_HASH = '$2y$12$dxjrDWvk..9pcoRtkc6wZOpqwg6Ru8sMQrqP9pKVGF3x0xIgd8XUi';

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        // The column default already makes new accounts customers; set it
        // explicitly so a mis-migrated environment can't hand out admin.
        $user->is_admin = false;
        $user->save();
        $user->refresh(); // response carries DB defaults (phone, address, ...)

        $token = $user->createToken('vue-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Social-only accounts have a null password — they can't log in here.
        // The miss path still burns a hash check to keep timing uniform.
        $hash = ($user && $user->password) ? $user->password : self::TIMING_HASH;

        if (! Hash::check($credentials['password'], $hash) || ! $user || ! $user->password) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('vue-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
