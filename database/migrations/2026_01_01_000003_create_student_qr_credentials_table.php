<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_qr_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('encrypted_token');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_qr_credentials');
    }
};
