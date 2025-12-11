<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\BrevoMailService;
use App\Notifications\AccountApproved;
use Carbon\Carbon;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $q = $request->query('q');
            $category = $request->query('category');

            $query = User::query()->where('role', 'siswa');

            if ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            }

            if ($category) {
                if ($category === 'upkp') $query->where('upkp', true);
                elseif ($category === 'tugas-belajar') $query->where('tugas_belajar', true);
                elseif ($category === 'both') $query->where('upkp', true)->where('tugas_belajar', true);
            }

            $users = $query->orderByDesc('created_at')->get();

            // Bentuk data konsisten dengan frontend
            $data = $users->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'categories' => [
                        'upkp' => (bool) $u->upkp,
                        'tugasBelajar' => (bool) $u->tugas_belajar,
                    ],
                    'expiresAt' => $u->expires_at ? ($u->expires_at instanceof \Carbon\Carbon ? $u->expires_at->toDateString() : (string) $u->expires_at) : null,
                    'approved' => (bool) $u->approved,
                    'active' => (bool) $u->active,
                    'createdAt' => $u->created_at ? $u->created_at->toDateTimeString() : null,
                ];
            });

            $counts = [
                'total' => User::where('role', 'siswa')->count(),
                'pending' => User::where('role', 'siswa')->where('approved', false)->count(),
                'active' => User::where('role', 'siswa')->where('active', true)->count(),
            ];

            return response()->json(['data' => $data, 'counts' => $counts]);
        } catch (\Throwable $e) {
            Log::error('AdminUserController@index error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Server error saat memuat users',
                'detail' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // PUT /api/admin/users/{user}
    public function update(Request $request, User $user)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'upkp' => 'sometimes|boolean',
                'tugas_belajar' => 'sometimes|boolean',
                'expires_at' => 'nullable|date',
                'approved' => 'sometimes|boolean',
                'active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            if (array_key_exists('name', $data)) $user->name = $data['name'];
            if (array_key_exists('email', $data)) $user->email = $data['email'];
            if (array_key_exists('upkp', $data)) $user->upkp = (bool) $data['upkp'];
            if (array_key_exists('tugas_belajar', $data)) $user->tugas_belajar = (bool) $data['tugas_belajar'];
            if (array_key_exists('approved', $data)) $user->approved = (bool) $data['approved'];
            if (array_key_exists('active', $data)) $user->active = (bool) $data['active'];

            if (array_key_exists('expires_at', $data)) {
                $user->expires_at = $data['expires_at'] ? Carbon::parse($data['expires_at']) : null;
            }

            $user->save();

            return response()->json(['message' => 'User diperbarui', 'user' => $user]);
        } catch (\Throwable $e) {
            Log::error('AdminUserController@update error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Server error saat update user',
                'detail' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // POST /api/admin/users/{user}/approve

    // public function approve(User $user)
    // {
    //     try {
    //         $user->approved = true;
    //         $user->save();
    //         return response()->json(['message' => 'User di-approve']);
    //     } catch (\Throwable $e) {
    //         Log::error('AdminUserController@approve error: ' . $e->getMessage(), ['exception' => $e]);
    //         return response()->json(['message' => 'Server error saat approve', 'detail' => config('app.debug') ? $e->getMessage() : null], 500);
    //     }
    // }

    // public function approve(User $user, Request $request)
    // {
    //     // Update status user
    //     $user->approved = true;
    //     // opsional: aktifkan akun saat di-ACC
    //     if (is_null($user->active)) {
    //         $user->active = true;
    //     }
    //     // opsional: set expiry jika dikirim dari request
    //     if ($request->filled('expires_at')) {
    //         $user->expires_at = $request->date('expires_at');
    //     }
    //     // simpan kategori jika ada
    //     if ($request->has('upkp')) {
    //         $user->upkp = (bool) $request->boolean('upkp');
    //     }
    //     if ($request->has('tugas_belajar')) {
    //         $user->tugas_belajar = (bool) $request->boolean('tugas_belajar');
    //     }

    //     $user->save();

    //     // Kirim notifikasi email
    //     $loginUrl = config('app.frontend_url') ?? url('/login');

    //     try {
    //         $user->notify(new AccountApproved(
    //             user: $user,
    //             loginUrl: $loginUrl,
    //             expiresAt: optional($user->expires_at)->toDateString(),
    //             upkp: (bool)($user->upkp ?? false),
    //             tugasBelajar: (bool)($user->tugas_belajar ?? false)
    //         ));
    //     } catch (\Throwable $e) {
    //         report($e); // jangan gagalkan approve hanya karena email gagal
    //     }

    //     return response()->json(['message' => 'User approved']);
    // }


public function approve(User $user, Request $request, BrevoMailService $brevo)
{
    // Update status user
    $user->approved = true;

    if (is_null($user->active)) {
        $user->active = true;
    }

    if ($request->filled('expires_at')) {
        $user->expires_at = $request->date('expires_at');
    }

    if ($request->has('upkp')) {
        $user->upkp = (bool) $request->boolean('upkp');
    }

    if ($request->has('tugas_belajar')) {
        $user->tugas_belajar = (bool) $request->boolean('tugas_belajar');
    }

    $user->save();

    // Build data email
    $loginUrl = config('app.frontend_url') ?? url('/login');

    $html = view('emails.account_approved', [
        'user' => $user,
        'loginUrl' => $loginUrl,
        'expiresAt' => optional($user->expires_at)->toDateString(),
        'upkp' => (bool) ($user->upkp ?? false),
        'tugasBelajar' => (bool) ($user->tugas_belajar ?? false),
    ])->render();

    try {
        // Kirim via Brevo API
        $brevo->send(
            $user->email,
            'Akun Anda Telah Disetujui - STANGOLDEN',
            $html
        );
    } catch (\Throwable $e) {
        report($e); // jangan gagalkan proses approve
    }

    return response()->json(['message' => 'User approved']);
}


    // POST /api/admin/users/{user}/revoke
    public function revoke(User $user)
    {
        try {
            $user->approved = false;
            $user->save();
            return response()->json(['message' => 'User di-revoke']);
        } catch (\Throwable $e) {
            Log::error('AdminUserController@revoke error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Server error saat revoke', 'detail' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    // POST /api/admin/users/{user}/toggle-active
    public function toggleActive(User $user)
    {
        try {
            $user->active = !$user->active;
            $user->save();
            return response()->json(['message' => 'Status active di-toggle', 'active' => $user->active]);
        } catch (\Throwable $e) {
            Log::error('AdminUserController@toggleActive error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Server error saat toggle active', 'detail' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    // POST /api/admin/users/{user}/expiry
    public function setExpiry(Request $request, User $user)
    {
        try {
            $validator = Validator::make($request->all(), [
                'expires_at' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();
            $user->expires_at = $data['expires_at'] ? Carbon::parse($data['expires_at']) : null;
            $user->save();

            return response()->json(['message' => 'Expiry diset', 'expires_at' => $user->expires_at]);
        } catch (\Throwable $e) {
            Log::error('AdminUserController@setExpiry error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Server error saat set expiry', 'detail' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    // DELETE /api/admin/users/{user}
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(['message' => 'User dihapus']);
        } catch (\Throwable $e) {
            Log::error('AdminUserController@destroy error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Server error saat menghapus user', 'detail' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }
}