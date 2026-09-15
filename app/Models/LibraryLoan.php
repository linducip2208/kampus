<?php

namespace App\Models;

class LibraryLoan extends CampusModel
{
    protected $casts = ['borrowed_at' => 'datetime', 'due_at' => 'datetime', 'returned_at' => 'datetime', 'fine_amount' => 'decimal:2'];

    public function book()
    {
        return $this->belongsTo(LibraryBook::class, 'library_book_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
