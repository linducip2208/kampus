<?php

namespace App\Services\Learning;

use App\Models\AuditLog;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizLifecycleService
{
    public function publish(Quiz $quiz, User $actor): Quiz
    {
        return $this->transition($quiz, $actor, 'published', ['draft', 'closed'], function (Quiz $locked) {
            if ($locked->questions()->count() < 1) {
                throw ValidationException::withMessages(['quiz' => 'Quiz minimal memiliki satu soal untuk dipublish.']);
            }
            if ($locked->ends_at && $locked->starts_at && $locked->ends_at <= $locked->starts_at) {
                throw ValidationException::withMessages(['ends_at' => 'Waktu selesai harus setelah waktu mulai.']);
            }
        });
    }

    public function close(Quiz $quiz, User $actor): Quiz
    {
        return $this->transition($quiz, $actor, 'closed', ['published', 'open']);
    }

    public function reopen(Quiz $quiz, User $actor): Quiz
    {
        return $this->transition($quiz, $actor, 'open', ['closed', 'published']);
    }

    public function toDraft(Quiz $quiz, User $actor): Quiz
    {
        return $this->transition($quiz, $actor, 'draft', ['published', 'closed', 'open'], function (Quiz $locked) {
            if ($locked->attempts()->exists()) {
                throw ValidationException::withMessages(['quiz' => 'Quiz yang sudah memiliki attempt tidak dapat kembali ke draft.']);
            }
        });
    }

    private function transition(Quiz $quiz, User $actor, string $to, array $from, ?callable $guard = null): Quiz
    {
        return DB::transaction(function () use ($quiz, $actor, $to, $from, $guard) {
            $locked = Quiz::query()->with('classSection.lecturers.employee')->lockForUpdate()->findOrFail($quiz->id);
            if (! in_array($locked->status, $from, true)) {
                throw ValidationException::withMessages(['quiz' => "Transisi dari {$locked->status} ke {$to} tidak diizinkan."]);
            }
            $lecturerId = $actor->employee?->lecturerProfile?->id;
            if (! $actor->hasRole('super_admin') && (! $lecturerId || ! $locked->classSection->lecturers->contains('id', $lecturerId))) {
                throw ValidationException::withMessages(['authorization' => 'Hanya dosen pengampu yang dapat mengubah lifecycle quiz.']);
            }
            if ($guard) {
                $guard($locked);
            }
            $locked->forceFill(['status' => $to])->save();

            AuditLog::query()->create([
                'user_id' => $actor->id, 'event' => 'quiz.lifecycle_changed', 'module' => 'learning',
                'entity_type' => Quiz::class, 'entity_id' => $locked->id,
                'new_values' => ['status' => $to],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $locked->fresh();
        });
    }
}
