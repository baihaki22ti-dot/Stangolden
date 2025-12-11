<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParticipantQuestionController extends Controller
{
    public function index(QuestionBank $bank, Request $request)
    {
        try {
            // Ambil kolom yang aman untuk peserta (tanpa answer_key)
            $raw = Question::where('bank_id', $bank->id)
                ->orderBy('id')
                ->get(['id', 'text', 'media', 'options', 'type', 'difficulty', 'tags']);

            // Normalisasi
            $questions = $raw->map(function ($q) {
                $opts = $q->options;

                // Pastikan options berbentuk array [{id,text}]
                if (is_string($opts)) {
                    $decoded = json_decode($opts, true);
                    $opts = (json_last_error() === JSON_ERROR_NONE) ? $decoded : [];
                } elseif (!is_array($opts)) {
                    $opts = [];
                }

                // Jika options berupa object, ubah ke array
                if (isset($opts) && !array_is_list($opts)) {
                    $tmp = [];
                    foreach ($opts as $k => $v) {
                        $tmp[] = ['id' => (string)$k, 'text' => (string)$v];
                    }
                    $opts = $tmp;
                }

                return [
                    'id'        => $q->id,
                    'content'   => (string)($q->text ?? ''), // gunakan kolom text
                    'media'     => $q->media,                // jika ada
                    'options'   => $opts,                    // array [{id,text}]
                    'type'      => (string)($q->type ?? 'mcq'),
                    'difficulty'=> (string)($q->difficulty ?? ''),
                    'tags'      => $q->tags ?? [],
                ];
            });

            return response()->json([
                'bank_id' => $bank->id,
                'category' => $bank->category,
                'count' => $questions->count(),
                'questions' => $questions,
            ]);
        } catch (\Throwable $e) {
            Log::error('ParticipantQuestionController index error: '.$e->getMessage(), ['bank_id' => $bank->id]);
            return response()->json(['message' => 'Failed to load questions'], 500);
        }
    }
}