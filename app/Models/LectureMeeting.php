<?php

namespace App\Models;

class LectureMeeting extends CampusModel
{
    protected $casts = ['meeting_date' => 'date'];
    public function classSection() { return $this->belongsTo(ClassSection::class); }
    public function attendanceSessions() { return $this->hasMany(AttendanceSession::class); }
}
