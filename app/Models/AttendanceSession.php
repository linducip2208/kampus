<?php

namespace App\Models;

class AttendanceSession extends CampusModel
{
    protected $casts = ['expires_at' => 'datetime'];
    public function lectureMeeting() { return $this->belongsTo(LectureMeeting::class); }
    public function attendances() { return $this->hasMany(StudentAttendance::class); }
}
