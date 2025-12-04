<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Gunakan nama tabel konsisten: 'feedbacks'
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();

            // Foreign key ke users.id, nullable, null on delete (jika user dihapus maka user_id jadi null)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Kolom utama
            $table->string('category')->default('umum');   // 'bug' | 'fitur' | 'umum'
            $table->string('title');                       // judul feedback
            $table->text('message');                       // deskripsi
            $table->string('priority')->default('medium'); // 'low'|'medium'|'high'
            $table->boolean('resolved')->default(false);   // status selesai

            // Lampiran
            $table->string('attachment_path')->nullable(); // path file (opsional)
            $table->string('attachment_name')->nullable(); // nama file asli (opsional)
            // Opsional jika ingin URL langsung disimpan
            $table->string('attachment_url')->nullable();

            $table->timestamps();

            // Index gabungan untuk query/filter cepat
            $table->index(['category', 'priority', 'resolved']);
            // Index user_id untuk filter milik user
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        // Pastikan nama tabel sama dengan yang dibuat di up()
        Schema::dropIfExists('feedbacks');
    }
};