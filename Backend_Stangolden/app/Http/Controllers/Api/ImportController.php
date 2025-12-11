<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\QuestionBank;
use App\Models\Question;

class ImportController extends Controller
{
    public function importCsv(Request $request)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:20480',
            'bank_id' => 'nullable|integer|exists:question_banks,id',
            'series_id' => 'nullable|integer|exists:tryout_series,id',
            'category' => 'nullable|in:tskkwk,tpa-verbal,tpa-numerik,tpa-figural,tbi-structure,tbi-reading',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Validasi file
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 422);
        }
        $uploadedFile = $request->file('file');
        if (!$uploadedFile->isValid()) {
            return response()->json(['message' => 'Invalid upload'], 422);
        }

        // Pastikan direktori ada
        $importsDir = storage_path('app/imports');
        if (!is_dir($importsDir)) {
            @mkdir($importsDir, 0777, true);
        }

        // Simpan file ke storage secara defensif (Windows-friendly)
        $uniqueName = uniqid('csv_', true) . '.csv';
        $relativePath = 'imports/' . $uniqueName;
        $fullPath = storage_path('app/' . $relativePath);

        $tmp = $uploadedFile->getRealPath();
        $contents = @file_get_contents($tmp);
        @file_put_contents($fullPath, $contents);
        if (!file_exists($fullPath)) {
            Log::error('Failed to store CSV', ['path' => $fullPath]);
            return response()->json(['message' => 'Failed to store uploaded file'], 500);
        }

        // Tentukan bank
        if (!empty($data['bank_id'])) {
            $bank = QuestionBank::findOrFail($data['bank_id']);
        } else {
            if (empty($data['series_id']) || empty($data['category'])) {
                return response()->json(['message' => 'series_id dan category wajib jika bank_id tidak diberikan'], 422);
            }
            $bank = QuestionBank::create([
                'series_id' => (int)$data['series_id'],
                'category' => $data['category'],
                'title' => $data['title'] ?? ('Import ' . date('Y-m-d H:i')),
                'description' => $data['description'] ?? 'Auto-import from CSV',
                'is_active' => true,
            ]);
        }
        $bank->update(['file_path' => $relativePath]);

        // Baca CSV
        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            return response()->json(['message' => 'Tidak bisa membuka file CSV'], 500);
        }

        $headers = [];
        $rowNum = 0;
        $created = 0;

        // Baca header
        if (($headers = fgetcsv($handle)) === false) {
            fclose($handle);
            return response()->json(['message' => 'CSV kosong atau header tidak terbaca'], 422);
        }
        // Normalisasi header ke lowercase
        $headers = array_map(function ($h) {
            return strtolower(trim($h));
        }, $headers);

        // Helper ambil kolom berdasarkan nama
        $indexOf = function (string $name) use ($headers) {
            $i = array_search($name, $headers, true);
            return $i === false ? null : $i;
        };

        // Pastikan kolom wajib ada
        foreach (['number','type','text','option_a','option_b','option_c','option_d','option_e','answer'] as $reqCol) {
            if (!in_array($reqCol, $headers, true)) {
                fclose($handle);
                return response()->json(['message' => 'Kolom wajib hilang: '.$reqCol], 422);
            }
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            $get = function (string $name) use ($row, $indexOf) {
                $i = $indexOf($name);
                return $i === null ? null : (isset($row[$i]) ? trim((string)$row[$i]) : null);
            };

            $type = strtolower($get('type') ?? 'mcq');
            $text = $get('text');
            $optA = $get('option_a');
            $optB = $get('option_b');
            $optC = $get('option_c');
            $optD = $get('option_d');
            $optE = $get('option_e');
            $ans  = strtoupper($get('answer') ?? '');
            $diff = strtolower($get('difficulty') ?? '');
            $tagsStr = $get('tags') ?? '';
            $expl = $get('explanation') ?? null;

            if (!$text || !$optA || !$optB || !$optC || !$optD || !$optE || !in_array($ans, ['A','B','C','D','E'])) {
                Log::warning('Baris dilewati (data tidak lengkap)', ['rowNum' => $rowNum + 1, 'text' => $text, 'ans' => $ans]);
                continue;
            }

            $options = [
                ['id' => 'A', 'text' => $optA],
                ['id' => 'B', 'text' => $optB],
                ['id' => 'C', 'text' => $optC],
                ['id' => 'D', 'text' => $optD],
                ['id' => 'E', 'text' => $optE],
            ];

            $tags = $tagsStr ? array_values(array_filter(array_map('trim', explode(',', $tagsStr)))) : [];

            $question = [
                'bank_id' => $bank->id,
                'type' => in_array($type, ['mcq']) ? $type : 'mcq',
                'text' => $text,
                'media' => null,
                'options' => $options,
                'answer_key' => [$ans],
                'difficulty' => in_array($diff, ['easy','medium','hard']) ? $diff : null,
                'tags' => $tags,
                'explanation' => $expl ?: null,
                'created_by' => optional($request->user())->id,
                'is_active' => true,
            ];

            try {
                Question::create($question);
                $created++;
            } catch (\Throwable $e) {
                Log::error('Gagal create question dari CSV', ['rowNum' => $rowNum + 1, 'error' => $e->getMessage()]);
            }
        }

        fclose($handle);

        Log::info('Import CSV result', ['bank_id' => $bank->id, 'created' => $created]);

        return response()->json([
            'message' => 'Imported from CSV',
            'bank_id' => $bank->id,
            'created' => $created,
            'file_path' => $relativePath,
        ], 200);
    }
}