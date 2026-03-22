<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function exists()
    {
        return response()->json([
            'exists' => Admin::count() > 0,
        ]);
    }

    public function signup(Request $request)
    {
        if (Admin::count() > 0) {
            return response()->json(['error' => 'Admin already exists'], 403);
        }

        $validated = $request->validate([
            'username' => 'required|string|unique:admins,username',
            'password' => 'required|string|min:8',
        ]);

        $admin = new Admin();
        $admin->username = $validated['username'];
        $admin->password = Hash::make($validated['password']);
        $admin->api_token = Str::random(60);
        $admin->save();

        return response()->json([
            'token' => $admin->api_token,
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('username', $validated['username'])->first();

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $admin->api_token = Str::random(60);
        $admin->save();

        return response()->json([
            'token' => $admin->api_token,
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $admin = $this->getAdminFromToken($request);

        if (! $admin) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
            ],
        ]);
    }

    private function getAdminFromToken(Request $request): ?Admin
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);

        if (! $token) {
            return null;
        }

        return Admin::where('api_token', $token)->first();
    }
}
