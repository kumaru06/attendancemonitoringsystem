<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        $this->backfillSchools();

        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['school_id', 'name']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['student_number']);
            $table->unique(['school_id', 'student_number']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'student_number']);
            $table->unique('student_number');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'name']);
            $table->unique('name');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::dropIfExists('schools');
    }

    private function backfillSchools(): void
    {
        $hasRows = DB::table('users')->where('role', 'admin')->exists()
            || DB::table('sections')->exists()
            || DB::table('students')->exists()
            || DB::table('attendances')->exists();

        if (! $hasRows) {
            return;
        }

        $admin = DB::table('users')->where('role', 'admin')->orderBy('id')->first();
        $schoolName = $admin->name ?? 'Default School';

        $schoolId = DB::table('schools')->insertGetId([
            'name' => $schoolName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')
            ->whereIn('role', ['admin', 'scanner'])
            ->whereNull('school_id')
            ->update(['school_id' => $schoolId]);

        DB::table('sections')->whereNull('school_id')->update(['school_id' => $schoolId]);
        DB::table('students')->whereNull('school_id')->update(['school_id' => $schoolId]);
        DB::table('attendances')->whereNull('school_id')->update(['school_id' => $schoolId]);
    }
};
