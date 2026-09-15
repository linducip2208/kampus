<?php

namespace App\Services\Documents;

use App\Models\AuditLog;
use App\Models\PrivateFile;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrivateFileService
{
    public function store(User $owner, string $label, string $path, ?string $mime = null, int $sizeBytes = 0, ?StudentEnrollment $enrollment = null, string $visibility = 'private'): PrivateFile
    {
        if (trim($label) === '' || trim($path) === '') {
            throw ValidationException::withMessages(['file' => 'Label dan path file wajib diisi.']);
        }
        if (! in_array($visibility, ['private', 'university', 'public'], true)) {
            throw ValidationException::withMessages(['visibility' => 'Visibilitas tidak valid.']);
        }

        $file = PrivateFile::query()->create([
            'owner_id' => $owner->id,
            'student_enrollment_id' => $enrollment?->id,
            'label' => trim($label),
            'path' => trim($path),
            'mime' => $mime,
            'size_bytes' => $sizeBytes,
            'visibility' => $visibility,
        ]);

        AuditLog::query()->create([
            'user_id' => $owner->id, 'event' => 'file.stored', 'module' => 'documents',
            'entity_type' => PrivateFile::class, 'entity_id' => $file->id,
            'new_values' => ['label' => $file->label, 'visibility' => $visibility],
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);

        return $file->fresh();
    }

    public function authorizeAccess(PrivateFile $file, User $user): PrivateFile
    {
        $file->loadMissing('owner.roles', 'enrollment.studentProfile');
        if ($file->visibility === 'public') {
            return $file;
        }
        if ($file->owner_id === $user->id || $user->hasRole('super_admin')) {
            return $file;
        }
        if ($file->visibility === 'university') {
            $ownerUniversities = $file->owner->roles()->get()->pluck('pivot.university_id')->filter()->unique();
            $userUniversities = $user->roles()->get()->pluck('pivot.university_id')->filter()->unique();
            if ($ownerUniversities->intersect($userUniversities)->isNotEmpty()) {
                return $file;
            }
        }
        if ($file->student_enrollment_id && $file->enrollment?->studentProfile?->user_id === $user->id) {
            return $file;
        }

        throw ValidationException::withMessages(['authorization' => 'Anda tidak berhak mengakses file ini.']);
    }

    public function delete(PrivateFile $file, User $actor): void
    {
        DB::transaction(function () use ($file, $actor) {
            $locked = PrivateFile::query()->lockForUpdate()->findOrFail($file->id);
            if ($locked->owner_id !== $actor->id && ! $actor->hasRole('super_admin')) {
                throw ValidationException::withMessages(['authorization' => 'Hanya pemilik yang dapat menghapus file.']);
            }
            $locked->delete();

            AuditLog::query()->create([
                'user_id' => $actor->id, 'event' => 'file.deleted', 'module' => 'documents',
                'entity_type' => PrivateFile::class, 'entity_id' => $locked->id,
                'new_values' => ['label' => $locked->label],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);
        });
    }
}
