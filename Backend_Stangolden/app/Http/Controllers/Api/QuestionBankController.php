<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionBank;
use App\Models\Question;

class QuestionBankController extends Controller
{
    // public function index(Request $req)
    // {
    //     $q = QuestionBank::query()->orderByDesc('created_at');

    //     // Filter baru: series_id & category
    //     if ($req->filled('series_id')) {
    //         $q->where('series_id', (int) $req->query('series_id'));
    //     }
    //     if ($req->filled('category')) {
    //         $q->where('category', $req->query('category'));
    //     }
    //     if ($req->filled('is_active')) {
    //         $q->where('is_active', filter_var($req->query('is_active'), FILTER_VALIDATE_BOOLEAN));
    //     }

    //     return response()->json($q->get());
    // }
    public function index(Request $request)
    {
        $q = QuestionBank::query();

        if ($request->filled('series_id')) {
            $q->where('series_id', $request->input('series_id'));
        }

        if ($request->filled('category')) {
            $q->where('category', $request->input('category'));
        }

        // hitung jumlah question tiap bank untuk kemudahan UI
        $items = $q->withCount('questions')->orderBy('id', 'desc')->get();

        return response()->json($items);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'series_id' => 'required|exists:tryout_series,id',
            'category'  => 'required|in:tskkwk,tpa-verbal,tpa-numerik,tpa-figural,tbi-structure,tbi-reading',
            'title'     => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $bank = QuestionBank::create($data);
        return response()->json(['message' => 'Bank created', 'data' => $bank], 201);
    }

    public function update(Request $req, QuestionBank $bank)
    {
        $data = $req->validate([
            'series_id' => 'sometimes|exists:tryout_series,id',
            'category'  => 'sometimes|in:tskkwk,tpa-verbal,tpa-numerik,tpa-figural,tbi-structure,tbi-reading',
            'title'     => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $bank->update($data);
        return response()->json(['message' => 'Bank updated', 'data' => $bank]);
    }

    public function destroy(QuestionBank $bank)
    {
        $bank->delete();
        return response()->json(['message' => 'Bank deleted']);
    }

    public function listQuestions(QuestionBank $bank)
    {
        return response()->json($bank->questions()->orderByDesc('created_at')->get());
    }

    public function storeQuestion(Request $req, QuestionBank $bank)
    {
        $data = $req->validate([
            'type' => 'required|in:mcq,truefalse,essay',
            'text' => 'required|string',
            'media' => 'nullable|array',
            'options' => 'nullable|array',
            'answer_key' => 'nullable|array',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'tags' => 'nullable|array',
            'explanation' => 'nullable|string',
        ]);

        $data['bank_id'] = $bank->id;
        $data['created_by'] = optional($req->user())->id;

        $q = Question::create($data);
        return response()->json(['message' => 'Question created', 'data' => $q], 201);
    }

    public function updateQuestion(Request $req, Question $question)
    {
        $data = $req->validate([
            'text' => 'sometimes|string',
            'media' => 'nullable|array',
            'options' => 'nullable|array',
            'answer_key' => 'nullable|array',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'tags' => 'nullable|array',
            'explanation' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $question->update($data);
        return response()->json(['message' => 'Question updated', 'data' => $question]);
    }

    public function destroyQuestion(Question $question)
    {
        $question->delete();
        return response()->json(['message' => 'Question deleted']);
    }
}