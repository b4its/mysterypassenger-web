<?php

namespace App\Http\Controllers\Api\Legacy;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LegacyAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $identifier = (string) ($request->input('username') ?? $request->input('email') ?? $request->input('login'));
        $password = (string) $request->input('password');

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password salah',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak aktif',
            ], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $abilities = match ($user->role) {
            UserRole::Admin => ['*'],
            UserRole::Reviewer => ['survey:read', 'survey:review', 'template:read'],
            UserRole::Surveyor => ['survey:read', 'survey:write', 'template:read'],
        };

        $token = $user->createToken('v1-legacy-app', $abilities)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role === UserRole::Surveyor ? 'pengguna' : $user->role->value,
            ],
            'token' => $token,
        ]);
    }
}
