<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FeedbackController extends Controller
{
    protected function transform(Feedback $fb): array
    {
        return [
            'id' => $fb->id,
            'user' => $fb->user ? [
                'id' => $fb->user->id,
                'name' => $fb->user->name,
                'email' => $fb->user->email,
            ] : null,
            'user_id' => $fb->user_id,
            'category' => $fb->category,
            'title' => $fb->title,
            'message' => $fb->message,
            'priority' => $fb->priority,
            'resolved' => (bool) $fb->resolved,
            'attachment_name' => $fb->attachment_name,
            'attachment_url' => $fb->attachment_path ? url(Storage::url($fb->attachment_path)) : null,
            'created_at' => optional($fb->created_at)->toDateTimeString(),
            'updated_at' => optional($fb->updated_at)->toDateTimeString(),
        ];
    }

    // GET /api/feedbacks?category=&priority=&unresolved=1&q=&user_id=
    public function index(Request $request)
    {
        $query = Feedback::query()->with('user')->orderByDesc('created_at');

        // Tentukan role admin
        $user = $request->user();
        $isAdmin = $user && (strtolower((string)($user->role ?? '')) === 'admin' || ($user->is_admin ?? false));

        // Admin: boleh melihat semua; dukungan filter user_id opsional
        if ($isAdmin) {
            if ($request->filled('user_id')) {
                $query->where('user_id', (int)$request->query('user_id'));
            }
        } else {
            // Siswa: hanya feedback miliknya, jika user tersedia
            if ($user?->id) {
                $query->where('user_id', $user->id);
            } else {
                // Jika tidak ada user (tidak auth), kembalikan kosong
                return response()->json([]);
            }
        }

        // Filter umum
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }
        if ($request->boolean('unresolved')) {
            $query->where('resolved', false);
        }
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('message', 'like', "%{$q}%");
            });
        }

        $items = $query->get();
        return response()->json($items->map(fn($f) => $this->transform($f)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'nullable|string|in:bug,fitur,umum',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|string|in:low,medium,high',
            'attachment' => 'nullable|file|max:5120',
        ]);

        $path = null; $name = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('feedbacks', 'public');
            $name = $file->getClientOriginalName();
        }

        $fb = Feedback::create([
            'user_id' => optional($request->user())->id, // pastikan route dilindungi auth agar terisi
            'category' => $validated['category'] ?? 'umum',
            'title' => $validated['title'],
            'message' => $validated['message'],
            'priority' => $validated['priority'] ?? 'medium',
            'resolved' => false,
            'attachment_path' => $path,
            'attachment_name' => $name,
        ]);

        return response()->json(['message' => 'Feedback dibuat', 'data' => $this->transform($fb)], 201);
    }

    public function show(Feedback $feedback)
    {
        $feedback->load('user');
        return response()->json($this->transform($feedback));
    }

    public function update(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'category' => 'sometimes|string|in:bug,fitur,umum',
            'title' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
            'priority' => 'sometimes|string|in:low,medium,high',
            'resolved' => 'sometimes|boolean',
            'attachment' => 'nullable|file|max:5120',
        ]);

        foreach (['category','title','message','priority','resolved'] as $key) {
            if (array_key_exists($key, $validated)) {
                $feedback->{$key} = $validated[$key];
            }
        }

        if ($request->hasFile('attachment')) {
            if ($feedback->attachment_path && Storage::disk('public')->exists($feedback->attachment_path)) {
                Storage::disk('public')->delete($feedback->attachment_path);
            }
            $file = $request->file('attachment');
            $feedback->attachment_path = $file->store('feedbacks', 'public');
            $feedback->attachment_name = $file->getClientOriginalName();
        }

        $feedback->save();

        return response()->json(['message' => 'Feedback diperbarui', 'data' => $this->transform($feedback)]);
    }

    public function destroy(Feedback $feedback)
    {
        if ($feedback->attachment_path && Storage::disk('public')->exists($feedback->attachment_path)) {
            Storage::disk('public')->delete($feedback->attachment_path);
        }
        $feedback->delete();

        return response()->json(['message' => 'Feedback dihapus']);
    }

    public function toggleResolved(Feedback $feedback)
    {
        $feedback->resolved = !$feedback->resolved;
        $feedback->save();

        return response()->json(['message' => 'Status diperbarui', 'data' => $this->transform($feedback)]);
    }
}