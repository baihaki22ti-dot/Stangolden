<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    protected function transform(Module $m): array
    {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'group' => $m->group,
            'sub_group' => $m->sub_group,
            'description' => $m->description,
            'pdf_url' => $m->pdf_path ? url(Storage::url($m->pdf_path)) : null,
            'pdf_name' => $m->pdf_original_name,
            'pdf_size' => $m->pdf_size,
            'created_at' => optional($m->created_at)->toDateTimeString(),
            'updated_at' => optional($m->updated_at)->toDateTimeString(),
        ];
    }

    // GET /api/admin/modules?group=...&sub_group=...&q=...
    public function index(Request $request)
    {
        $group = $request->query('group');
        $sub = $request->query('sub_group');
        $q = trim((string) $request->query('q', ''));

        $query = Module::query()->orderByDesc('created_at');

        if ($group) $query->where('group', $group);
        if ($sub) $query->where('sub_group', $sub);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $modules = $query->get();
        return response()->json($modules->map(fn($m) => $this->transform($m)));
    }

    // POST /api/admin/modules
    public function store(Request $request)
    {
        // Normalisasi slug untuk konsistensi
        if ($request->has('group')) {
            $request->merge(['group' => Str::slug((string)$request->input('group'), '-')]);
        }
        if ($request->has('sub_group')) {
            $request->merge(['sub_group' => Str::slug((string)$request->input('sub_group'), '-')]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group' => 'required|string|in:upkp,tugas-belajar',
            'sub_group' => 'nullable|string',
            'description' => 'nullable|string',
            'pdf' => 'required|file|mimes:pdf|max:51200',
        ]);

        $file = $request->file('pdf');
        $path = $file->store('modules', 'public');

        $module = Module::create([
            'name' => $validated['name'],
            'group' => $validated['group'],
            'sub_group' => $validated['sub_group'] ?? null,
            'description' => $validated['description'] ?? null,
            'pdf_path' => $path,
            'pdf_original_name' => $file->getClientOriginalName(),
            'pdf_size' => $file->getSize(),
        ]);

        return response()->json(['message' => 'Modul dibuat', 'data' => $this->transform($module)], 201);
    }

    // PUT /api/admin/modules/{module}
    public function update(Request $request, Module $module)
    {
        // Bersihkan dan normalisasi
        if ($request->has('group')) {
            $group = (string)$request->input('group');
            if ($group === '') {
                $request->request->remove('group');
            } else {
                $request->merge(['group' => Str::slug($group, '-')]);
            }
        }
        if ($request->has('sub_group')) {
            $sub = (string)$request->input('sub_group');
            $request->merge(['sub_group' => $sub === '' ? null : Str::slug($sub, '-')]);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'group' => 'sometimes|in:upkp,tugas-belajar',
            'sub_group' => 'sometimes|nullable|string',
            'description' => 'nullable|string',
            'pdf' => 'nullable|file|mimes:pdf|max:51200',
        ]);

        foreach (['name','group','sub_group','description'] as $key) {
            if (array_key_exists($key, $validated)) {
                $module->{$key} = $validated[$key];
            }
        }

        if ($request->hasFile('pdf')) {
            if ($module->pdf_path && Storage::disk('public')->exists($module->pdf_path)) {
                Storage::disk('public')->delete($module->pdf_path);
            }
            $file = $request->file('pdf');
            $path = $file->store('modules', 'public');
            $module->pdf_path = $path;
            $module->pdf_original_name = $file->getClientOriginalName();
            $module->pdf_size = $file->getSize();
        }

        $module->save();

        return response()->json(['message' => 'Modul diperbarui', 'data' => $this->transform($module)]);
    }

    // DELETE /api/admin/modules/{module}
    public function destroy(Module $module)
    {
        try {
            if ($module->pdf_path && Storage::disk('public')->exists($module->pdf_path)) {
                Storage::disk('public')->delete($module->pdf_path);
            }
            $module->delete();

            return response()->json(['message' => 'Modul dihapus']);
        } catch (\Throwable $e) {
            Log::error('ModuleController@destroy: '.$e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Gagal menghapus modul',
                'detail' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}