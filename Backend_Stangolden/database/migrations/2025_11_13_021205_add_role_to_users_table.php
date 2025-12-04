<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // role (existing intent) + extra peserta fields
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('siswa')->index()->after('email');
            }

            if (! Schema::hasColumn('users', 'approved')) {
                $table->boolean('approved')->default(false)->index()->after('role');
            }

            if (! Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(false)->index()->after('approved');
            }

            if (! Schema::hasColumn('users', 'expires_at')) {
                $table->date('expires_at')->nullable()->after('active');
            }

            if (! Schema::hasColumn('users', 'upkp')) {
                $table->boolean('upkp')->default(false)->after('expires_at');
            }

            if (! Schema::hasColumn('users', 'tugas_belajar')) {
                $table->boolean('tugas_belajar')->default(false)->after('upkp');
            }

            // phone & city (if not present)
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // drop in reverse order where needed
            if (Schema::hasColumn('users', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'tugas_belajar')) {
                $table->dropColumn('tugas_belajar');
            }
            if (Schema::hasColumn('users', 'upkp')) {
                $table->dropColumn('upkp');
            }
            if (Schema::hasColumn('users', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('users', 'active')) {
                try { $table->dropIndex(['active']); } catch (\Throwable $e) {}
                $table->dropColumn('active');
            }
            if (Schema::hasColumn('users', 'approved')) {
                try { $table->dropIndex(['approved']); } catch (\Throwable $e) {}
                $table->dropColumn('approved');
            }
            if (Schema::hasColumn('users', 'role')) {
                try { $table->dropIndex(['role']); } catch (\Throwable $e) {}
                $table->dropColumn('role');
            }
        });
    }
};