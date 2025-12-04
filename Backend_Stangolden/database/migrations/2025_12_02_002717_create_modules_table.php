<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');                   // nama modul
            $table->string('group');                  // 'upkp' | 'tugas-belajar'
            $table->string('sub_group')->nullable();  // contoh: 'wawasan-kebangsaan', 'tpa', 'tbi', dll
            $table->text('description')->nullable();  // deskripsi
            $table->string('pdf_path');               // path file di storage (public disk)
            $table->string('pdf_original_name');      // nama file asli
            $table->unsignedBigInteger('pdf_size');   // ukuran (bytes)
            $table->timestamps();

            $table->index(['group', 'sub_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};