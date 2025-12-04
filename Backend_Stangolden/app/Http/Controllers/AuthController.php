<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // POST /api/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','confirmed', Password::min(6)],
            'phone' => ['nullable','string','max:50'],
            'city' => ['nullable','string','max:150'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'siswa',
            'approved' => false,
            'active' => false,
            'upkp' => false,
            'tugas_belajar' => false,
            'expires_at' => null,
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil. Tunggu persetujuan admin untuk mengaktifkan akun Anda.',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name
            ]
        ], 201);
    }

    // POST /api/login
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'Kredensial tidak valid'], 422);
        }

        // Blokir jika expired KECUALI admin
        if ($user->isExpired() && !$user->isAdmin()) {
            return response()->json(['message' => 'Akun telah kedaluwarsa. Silakan hubungi admin.'], 403);
        }

        // Cek aktivasi/ACC (opsional, tetap diberlakukan untuk admin jika Anda ingin; hapus jika admin harus selalu lolos)
        if (isset($user->activated) && !$user->activated) {
            return response()->json(['message' => 'Akun belum diaktivasi.'], 403);
        }
        if (isset($user->approved) && !$user->approved) {
            return response()->json(['message' => 'Akun belum di-ACC.'], 403);
        }
        if (isset($user->active) && !$user->active) {
            return response()->json(['message' => 'Akun nonaktif.'], 403);
        }

        if (!Auth::attempt($validated)) {
            return response()->json(['message' => 'Kredensial tidak valid'], 422);
        }

        // Contoh: Sanctum
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'expires_at' => optional($user->expiryDate())->toDateTimeString(),
                'approved' => (bool)($user->approved ?? false),
                'activated' => (bool)($user->activated ?? false),
                'active' => (bool)($user->active ?? false),
                'role' => $user->role ?? null,
                'is_admin' => (bool)($user->is_admin ?? false),
            ],
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        if ($request->user() && method_exists($request->user(), 'currentAccessToken') && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }
        auth()->logout();
        return response()->json(['message' => 'Logged out']);
    }

    // GET /api/user
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}