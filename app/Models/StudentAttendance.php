<?php

namespace App\Models;

class StudentAttendance extends CampusModel
{
    protected $casts = ['recorded_at' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
