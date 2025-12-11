<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    // Hierarki: Domain -> Groups -> Series -> Sessions
    Schema::create('tryout_groups', function (Blueprint $table) {
      $table->id();
      $table->enum('domain', ['upkp', 'tubel']);
      $table->string('name');           // "TRY OUT BESAR 1", "TRY OUT STARBUS"
      $table->text('description')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      $table->index(['domain', 'is_active']);
    });

    Schema::create('tryout_series', function (Blueprint $table) {
      $table->id();
      $table->foreignId('group_id')->constrained('tryout_groups')->cascadeOnDelete();
      $table->unsignedInteger('number')->default(1); // TO #1..N
      $table->string('title')->nullable();           // optional 'TO 1', dst
      $table->text('description')->nullable();
      $table->boolean('is_active')->default(true);
      $table->dateTime('open_at')->nullable();
      $table->dateTime('close_at')->nullable();
      $table->timestamps();

      $table->index(['group_id', 'is_active']);
      $table->index('number'); // agar orderBy('number') efisien
      // Opsional untuk mencegah duplikat nomor dalam satu group:
      // $table->unique(['group_id', 'number']);
    });

    Schema::create('tryout_sessions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('series_id')->constrained('tryout_series')->cascadeOnDelete();
      $table->enum('key', [
        'tskkwk',
        'tpa-verbal','tpa-numerik','tpa-figural',
        'tbi-structure','tbi-reading'
      ]);
      $table->string('title');              // "TO TSKKWK" / "TO TPA Verbal"
      $table->unsignedInteger('order')->default(1);
      $table->unsignedInteger('duration_minutes')->default(60);
      $table->decimal('passing_score', 8, 2)->nullable(); // opsional
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      $table->index(['series_id', 'key', 'is_active']);
    });

    // Bank soal per series & category (BUKAN lagi per domain/session_key)
    Schema::create('question_banks', function (Blueprint $table) {
      $table->id();
      $table->foreignId('series_id')->constrained('tryout_series')->cascadeOnDelete();
      $table->enum('category', [
        'tskkwk',
        'tpa-verbal','tpa-numerik','tpa-figural',
        'tbi-structure','tbi-reading'
      ]);
      $table->string('title');
      $table->text('description')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      $table->index(['series_id', 'category', 'is_active']);
    });

    Schema::create('questions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('bank_id')->constrained('question_banks')->cascadeOnDelete();
      $table->enum('type', ['mcq','truefalse','essay'])->default('mcq');
      $table->longText('text');
      $table->json('media')->nullable();
      $table->json('options')->nullable();
      $table->json('answer_key')->nullable();
      $table->enum('difficulty', ['easy','medium','hard'])->nullable();
      $table->json('tags')->nullable();
      $table->longText('explanation')->nullable();
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      $table->index(['bank_id','type','difficulty','is_active']);
    });

    // Snapshot soal untuk sesi spesifik dalam series
    Schema::create('session_questions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('session_id')->constrained('tryout_sessions')->cascadeOnDelete();
      $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
      $table->unsignedInteger('order')->default(1);
      $table->decimal('points', 6, 2)->default(1.00);
      $table->json('overrides')->nullable();
      $table->timestamps();

      $table->index(['session_id','order']);
      $table->index('question_id');
    });

    // Attempts & answers untuk pengerjaan user
    Schema::create('attempts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('session_id')->constrained('tryout_sessions')->cascadeOnDelete();
      $table->dateTime('started_at')->nullable();
      $table->dateTime('submitted_at')->nullable();
      $table->decimal('score', 8, 2)->default(0);
      $table->enum('status', ['in_progress','submitted','expired'])->default('in_progress');
      $table->timestamps();

      $table->unique(['user_id','session_id']); // 1 attempt per sesi (ubah jika perlu)
      $table->index(['session_id','status']);
    });

    Schema::create('attempt_answers', function (Blueprint $table) {
      $table->id();
      $table->foreignId('attempt_id')->constrained('attempts')->cascadeOnDelete();
      $table->foreignId('session_question_id')->constrained('session_questions')->cascadeOnDelete();
      $table->json('answer')->nullable();
      $table->boolean('is_correct')->default(false);
      $table->decimal('points_awarded', 6, 2)->default(0);
      $table->timestamps();

      $table->index(['attempt_id','session_question_id']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('attempt_answers');
    Schema::dropIfExists('attempts');
    Schema::dropIfExists('session_questions');
    Schema::dropIfExists('questions');
    Schema::dropIfExists('question_banks');
    Schema::dropIfExists('tryout_sessions');
    Schema::dropIfExists('tryout_series');
    Schema::dropIfExists('tryout_groups');
  }
};