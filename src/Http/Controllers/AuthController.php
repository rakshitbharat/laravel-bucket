<?php

namespace LaraBucket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    /**
     * Handle admin login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $adminEmail = config('larabucket.server.admin_email', 'super@admin.com');
        $adminPassword = config('larabucket.server.admin_password', 'password');

        if ($request->input('email') !== $adminEmail || $request->input('password') !== $adminPassword) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Generate a simple token based on credentials
        $token = hash_hmac('sha256', $adminEmail, $adminPassword);

        return response()->json([
            'user' => [
                'id' => 'u1',
                'name' => 'Super Administrator',
                'email' => $adminEmail,
            ],
            'token' => $token,
        ]);
    }
}
