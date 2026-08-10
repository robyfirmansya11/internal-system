<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login karyawan — return Bearer token Sanctum.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->with(['profile', 'departments'])
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Logout — hapus semua token aktif user.
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * Data profile user yang sedang login.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['profile', 'departments']);

        return response()->json($this->formatUser($user));
    }

    /**
     * Format response user — dipakai di login() dan me() supaya konsisten.
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'level' => $user->level?->value,
            'jabatan' => $user->jabatan,
            'department' => $user->departments->first()?->nama_department,
            'foto' => $user->profile?->foto
                ? asset('storage/'.$user->profile->foto)
                : null,
        ];
    }
}
