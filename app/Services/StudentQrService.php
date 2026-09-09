<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentQrCredential;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class StudentQrService
{
    public function issue(Student $student): StudentQrCredential
    {
        return DB::transaction(function () use ($student): StudentQrCredential {
            $this->revokeCurrent($student);

            $token = $this->generateToken();

            return StudentQrCredential::query()->create([
                'student_id' => $student->id,
                'token_hash' => $this->hashToken($token),
                'encrypted_token' => Crypt::encryptString($token),
                'revoked_at' => null,
            ]);
        });
    }

    public function replace(Student $student): StudentQrCredential
    {
        return $this->issue($student);
    }

    public function findCurrentByToken(string $token): ?StudentQrCredential
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        return StudentQrCredential::query()
            ->with('student.section')
            ->where('token_hash', $this->hashToken($token))
            ->whereNull('revoked_at')
            ->first();
    }

    public function decryptToken(StudentQrCredential $credential): string
    {
        return Crypt::decryptString($credential->encrypted_token);
    }

    public function svg(string $token, int $size = 320): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 2),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($token);
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function revokeCurrent(Student $student): void
    {
        StudentQrCredential::query()
            ->where('student_id', $student->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
