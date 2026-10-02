<?php

namespace App\Http\Controllers\Api\V2;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = $request->loginIdentifier();

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Akun Anda tidak aktif. Hubungi administrator.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $abilities = match ($user->role) {
            UserRole::Admin => ['*'],
            UserRole::Reviewer => ['survey:read', 'survey:review', 'template:read'],
            UserRole::Surveyor => ['survey:read', 'survey:write', 'template:read'],
        };

        $deviceName = $request->string('device_name', 'Mobile App')->toString();
        $token = $user->createToken($deviceName, $abilities)->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'organization' => $user->organization,
                    'phone' => $user->phone,
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Berhasil keluar.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'organization' => $user->organization,
                'phone' => $user->phone,
                'last_login_at' => $user->last_login_at?->toISOString(),
            ],
        ]);
    }
}
