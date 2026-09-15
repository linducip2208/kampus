<?php

namespace App\Services\Library;

use App\Models\AuditLog;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryService
{
    public const FINE_PER_DAY = 2000;

    public function addBook(string $universityId, array $data): LibraryBook
    {
        return LibraryBook::query()->create([
            'university_id' => $universityId,
            'title' => trim($data['title']),
            'author' => $data['author'] ?? null,
            'isbn' => $data['isbn'] ?? null,
            'publisher' => $data['publisher'] ?? null,
            'published_year' => $data['published_year'] ?? null,
            'category' => $data['category'] ?? 'general',
            'shelf' => $data['shelf'] ?? null,
            'copies_total' => $data['copies_total'] ?? 1,
            'copies_available' => $data['copies_total'] ?? 1,
        ]);
    }

    public function borrow(LibraryBook $book, StudentEnrollment $enrollment, User $actor, int $loanDays = 14): LibraryLoan
    {
        return DB::transaction(function () use ($book, $enrollment, $actor, $loanDays) {
            $locked = LibraryBook::query()->lockForUpdate()->findOrFail($book->id);
            if ($locked->copies_available < 1) {
                throw ValidationException::withMessages(['book' => 'Stok buku habis.']);
            }
            $active = LibraryLoan::query()->where('student_enrollment_id', $enrollment->id)->where('status', 'borrowed')->count();
            if ($active >= 3) {
                throw ValidationException::withMessages(['loan' => 'Maksimal 3 pinjaman aktif.']);
            }

            $loan = LibraryLoan::query()->create([
                'library_book_id' => $locked->id,
                'student_enrollment_id' => $enrollment->id,
                'borrowed_at' => now(),
                'due_at' => now()->addDays($loanDays),
                'status' => 'borrowed',
            ]);
            $locked->decrement('copies_available');
            $this->audit($actor, $loan, 'library.borrowed', ['book_id' => $locked->id]);

            return $loan->fresh();
        });
    }

    public function returnBook(LibraryLoan $loan, User $actor): LibraryLoan
    {
        return DB::transaction(function () use ($loan, $actor) {
            $locked = LibraryLoan::query()->with('book')->lockForUpdate()->findOrFail($loan->id);
            if ($locked->status !== 'borrowed') {
                throw ValidationException::withMessages(['loan' => 'Pinjaman sudah dikembalikan.']);
            }
            $lateDays = max(0, (int) now()->startOfDay()->diffInDays($locked->due_at->startOfDay(), false) * -1);
            $fine = $lateDays * self::FINE_PER_DAY;
            $locked->forceFill(['status' => 'returned', 'returned_at' => now(), 'fine_amount' => number_format($fine, 2, '.', '')])->save();
            $locked->book()->increment('copies_available');
            $this->audit($actor, $locked, 'library.returned', ['fine' => $fine, 'late_days' => $lateDays]);

            return $locked->fresh();
        });
    }

    private function audit(User $actor, LibraryLoan $loan, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'library',
            'entity_type' => LibraryLoan::class, 'entity_id' => $loan->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
