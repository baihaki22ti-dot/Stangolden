<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema; // PENTING: untuk cek kolom
use Illuminate\Support\Facades\Log;    // PENTING: untuk Log::error
use App\Models\TryoutGroup;
use App\Models\TryoutSeries;
use App\Models\TryoutSession;
use App\Models\SessionQuestion;
use App\Models\Question;
use App\Models\QuestionBank;


class TryoutController extends Controller
{
    public function listGroups(Request $req)
    {
        $domain = $req->query('domain');
        $q = TryoutGroup::query()->orderByDesc('created_at');
        if ($domain) $q->where('domain', $domain);
        $items = $q->get();
        return response()->json($items);
    }

    public function createGroup(Request $req)
    {
        $data = $req->validate([
            'domain' => 'required|in:upkp,tubel',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $group = TryoutGroup::create($data);
        return response()->json(['message'=>'Group created','data'=>$group], 201);
    }

    public function updateGroup(Request $req, TryoutGroup $group)
    {
        $data = $req->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $group->update($data);
        return response()->json(['message'=>'Group updated','data'=>$group]);
    }

    public function destroyGroup(TryoutGroup $group)
    {
        $group->delete();
        return response()->json(['message'=>'Group deleted']);
    }

    public function listSeries(TryoutGroup $group)
    {
        try {
            $q = TryoutSeries::query()->where('group_id', $group->id);

            if (Schema::hasColumn('tryout_series', 'number')) {
                $q->orderBy('number');
            } elseif (Schema::hasColumn('tryout_series', 'created_at')) {
                $q->orderBy('created_at');
            } else {
                $q->orderBy('id');
            }

            $items = $q->get();
            return response()->json($items, 200);
        } catch (\Throwable $e) {
            Log::error('listSeries error: '.$e->getMessage(), ['group_id' => $group->id]);
            return response()->json([], 200);
        }
    }

    public function createSeries(Request $req, TryoutGroup $group)
    {
        $data = $req->validate([
            'number' => 'required|integer|min:1',     // perbaikan: validasi number
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'open_at' => 'nullable|date',
            'close_at' => 'nullable|date',
        ]);
        $data['group_id'] = $group->id;
        $series = TryoutSeries::create($data);
        return response()->json(['message'=>'Series created','data'=>$series], 201);
    }

    public function updateSeries(Request $req, TryoutSeries $series)
    {
        $data = $req->validate([
            'number' => 'sometimes|integer|min:1',    // perbaikan: validasi number
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'open_at' => 'nullable|date',
            'close_at' => 'nullable|date',
        ]);
        $series->update($data);
        return response()->json(['message'=>'Series updated','data'=>$series]);
    }

    public function destroySeries(TryoutSeries $series)
    {
        $series->delete();
        return response()->json(['message'=>'Series deleted']);
    }

    public function listSessions(TryoutSeries $series)
    {
        try {
            $items = $series->sessions()
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            Log::info('listSessions ok', ['series_id' => $series->id, 'count' => $items->count()]);

            // Kembalikan array JSON langsung; frontend membaca r.data sebagai array
            return response()->json($items, 200);
        } catch (\Throwable $e) {
            Log::error('listSessions error: '.$e->getMessage(), ['series_id' => $series->id ?? null]);
            return response()->json([], 200);
        }
    }

    public function createSession(TryoutSeries $series, Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'order' => 'nullable|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $session = TryoutSession::firstOrCreate(
            ['series_id' => $series->id, 'key' => $data['key']],
            [
                'series_id' => $series->id,
                'key' => $data['key'],
                'title' => $data['title'],
                'order' => $data['order'] ?? 0,
                'duration_minutes' => $data['duration_minutes'] ?? 30,
                'passing_score' => $data['passing_score'] ?? 70,
                'is_active' => $data['is_active'] ?? true,
            ]
        );

        return response()->json($session, 201);
    }

    public function updateSession(Request $req, TryoutSession $session)
    {
        $data = $req->validate([
            'title' => 'sometimes|string|max:255',
            'order' => 'nullable|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);
        $session->update($data);
        return response()->json(['message'=>'Session updated','data'=>$session]);
    }

    public function destroySession(TryoutSession $session)
    {
        $session->delete();
        return response()->json(['message'=>'Session deleted']);
    }

    public function getGroup(TryoutGroup $group)
    {
        return response()->json($group);
    }

    public function generateSessionQuestions(Request $req, TryoutSession $session)
    {
        $data = $req->validate([
            'bank_id' => 'required|integer|exists:question_banks,id',
            'count' => 'required|integer|min:1|max:200',
            'difficulty_mix' => 'nullable|array',
            'points' => 'nullable|numeric',
        ]);

        $bank = QuestionBank::findOrFail($data['bank_id']);

        // Validasi kesesuaian series dan kategori
        if ((int)$bank->series_id !== (int)$session->series_id) {
            return response()->json(['message' => 'Bank tidak berada pada series yang sama dengan session ini'], 422);
        }
        if ($bank->category !== $session->key) {
            return response()->json(['message' => 'Kategori bank tidak sesuai dengan key session'], 422);
        }
    }
}