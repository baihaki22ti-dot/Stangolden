<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\TryoutAttempt;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttemptController extends Controller
{
    private const CATEGORY_TIMER = [
        'tpa-verbal'    => 45 * 60,
        'tpa-numerik'   => 45 * 60,
        'tpa-figural'   => 30 * 60,
        'tbi-structure' => 13 * 60,
        'tbi-reading'   => 30 * 60,
        'tskkwk'        => 60 * 60,
    ];

    private const POINTS_PER_QUESTION = [
        'tpa-verbal'    => 2.5,
        'tpa-numerik'   => 2.5,
        'tpa-figural'   => 2.5,
        'tbi-structure' => 5.0,
        'tbi-reading'   => 3.3,
        'tskkwk'        => 1.6,
    ];

    public function start(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'bank_id' => 'required|integer|exists:question_banks,id',
        ]);

        $bank = QuestionBank::findOrFail($data['bank_id']);
        $category = strtolower($bank->category ?? '');

        $existing = TryoutAttempt::where('user_id', $user->id)
            ->where('bank_id', $bank->id)
            ->whereNull('finished_at')
            ->first();

        if ($existing) {
            return response()->json([
                'attempt_id' => $existing->id,
                'bank_id' => $bank->id,
                'category' => $existing->category,
                'started_at' => $existing->started_at,
                'deadline_at' => $existing->deadline_at,
                'duration_seconds' => $existing->duration_seconds,
                'total_questions' => $existing->total_questions,
            ], 200);
        }

        $duration = self::CATEGORY_TIMER[$category] ?? (30 * 60);
        $totalQuestions = Question::where('bank_id', $bank->id)->count();

        $now = CarbonImmutable::now();
        $attempt = TryoutAttempt::create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'category' => $category,
            'total_questions' => $totalQuestions,
            'duration_seconds' => $duration,
            'started_at' => $now,
            'deadline_at' => $now->addSeconds($duration),
        ]);

        return response()->json([
            'attempt_id' => $attempt->id,
            'bank_id' => $bank->id,
            'category' => $category,
            'started_at' => $attempt->started_at,
            'deadline_at' => $attempt->deadline_at,
            'duration_seconds' => $attempt->duration_seconds,
            'total_questions' => $totalQuestions,
        ], 201);
    }

    public function show(TryoutAttempt $attempt, Request $request)
    {
        $this->authorizeAttempt($attempt, $request->user()->id);
        return response()->json(['attempt' => $attempt]);
    }

    public function finish(TryoutAttempt $attempt, Request $request)
    {
        $this->authorizeAttempt($attempt, $request->user()->id);

        if ($attempt->finished_at) {
            return response()->json(['message' => 'Attempt already finished'], 409);
        }

        // IZINKAN choice berupa string atau array (agar tidak error Array to string)
        $payload = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer',
            'answers.*.choice' => 'nullable', // bisa string atau array
            'answers.*.status' => 'required|string|in:confirmed,skipped,none',
        ]);

        $answers = collect($payload['answers'])->keyBy('question_id');

        // Ambil kunci jawaban dari DB (answer_key adalah array JSON seperti ["D"])
        $questions = Question::whereIn('id', $answers->keys())
            ->where('bank_id', $attempt->bank_id)
            ->get(['id', 'answer_key']);

        $correct = 0; $wrong = 0; $unanswered = 0;

        foreach ($answers as $qid => $ans) {
            $status = $ans['status'] ?? 'none';
            if ($status === 'none') { $unanswered++; continue; }
            if ($status === 'skipped') { $wrong++; continue; }

            $q = $questions->firstWhere('id', (int)$qid);

            // Normalisasi answer_key (array/string) -> ambil satu kunci utama
            $key = '';
            $keyRaw = $q?->answer_key;
            if (is_array($keyRaw)) {
                foreach ($keyRaw as $k) {
                    if (is_string($k) && $k !== '') { $key = $k; break; }
                }
            } elseif (is_string($keyRaw)) {
                $dec = json_decode($keyRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
                    foreach ($dec as $k) {
                        if (is_string($k) && $k !== '') { $key = $k; break; }
                    }
                } else {
                    $key = $keyRaw;
                }
            }
            $key = strtoupper(trim((string)$key));

            // Normalisasi choice dari FE (string/array) → ambil elemen pertama
            $choiceRaw = $ans['choice'] ?? '';
            if (is_array($choiceRaw)) {
                $first = array_values(array_filter($choiceRaw, fn($v) => is_scalar($v) && $v !== ''))[0] ?? '';
                $choice = strtoupper(trim((string)$first));
            } else {
                $choice = strtoupper(trim((string)$choiceRaw));
            }

            if ($key === '') { $wrong++; continue; }
            if ($choice !== '' && $choice === $key) { $correct++; }
            else { $wrong++; }
        }

        // Tambah unanswered jika payload kurang dari total_questions
        $countProvided = $correct + $wrong + $unanswered;
        if ($countProvided < $attempt->total_questions) {
            $unanswered += ($attempt->total_questions - $countProvided);
        }

        // Skor per kategori (maks 100)
        $perPoint = self::POINTS_PER_QUESTION[$attempt->category] ?? 2.5;
        $score = min(100, $correct * $perPoint);

        $now = CarbonImmutable::now();
        $auto = $now->greaterThan($attempt->deadline_at);

        DB::transaction(function () use ($attempt, $answers, $correct, $wrong, $unanswered, $score, $now, $auto) {
            $attempt->update([
                'finished_at' => $now,
                'auto_submit' => $auto,
                'correct_count' => $correct,
                'wrong_count' => $wrong,
                'unanswered_count' => $unanswered,
                'score' => $score,
                'raw_answers' => $answers->values()->all(), // kolom JSON
            ]);
        });

        return response()->json([
            'attempt_id' => $attempt->id,
            'bank_id' => $attempt->bank_id,
            'category' => $attempt->category,
            'finished_at' => $now,
            'auto_submit' => $auto,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'score' => round($score, 2),
        ]);
    }

    public function myAttempts(Request $request)
    {
        $user = $request->user();
        $seriesId = $request->integer('series_id');

        $q = TryoutAttempt::query()
            ->with('bank:id,series_id,category,title')
            ->where('user_id', $user->id);

        if ($seriesId) {
            $q->whereHas('bank', fn ($bq) => $bq->where('series_id', $seriesId));
        }

        $attempts = $q->orderByDesc('id')->get();

        return response()->json(['data' => $attempts]);
    }

    private function authorizeAttempt(TryoutAttempt $attempt, int $userId): void
    {
        abort_if($attempt->user_id !== $userId, 403, 'Forbidden');
    }
}