<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        Schema::table('student_qr_credentials', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        Schema::table('student_qr_credentials', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::table('student_qr_credentials', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        Schema::table('student_qr_credentials', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });
    }
};
