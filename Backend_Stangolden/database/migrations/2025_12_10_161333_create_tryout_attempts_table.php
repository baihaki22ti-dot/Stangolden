<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tryout_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('bank_id');
            $table->string('category', 50)->index();
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);

            // gunakan datetime (nullable) agar tidak perlu default value
            $table->dateTime('started_at')->nullable();
            $table->dateTime('deadline_at')->nullable();
            $table->dateTime('finished_at')->nullable();

            $table->boolean('auto_submit')->default(false);

            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('wrong_count')->default(0);
            $table->unsignedInteger('unanswered_count')->default(0);
            $table->decimal('score', 6, 2)->default(0);

            $table->json('raw_answers')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'bank_id']);

            // FK opsional:
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('bank_id')->references('id')->on('question_banks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tryout_attempts');
    }
};