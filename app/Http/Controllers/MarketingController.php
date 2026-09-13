<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\StudyProgram;
use App\Models\University;

class MarketingController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            return redirect('/admin');
        }

        return view('marketing', [
            'university' => University::query()->first(),
            'stats' => [
                'students' => StudentProfile::query()->count(),
                'active_students' => StudentEnrollment::query()->where('status', 'active')->count(),
                'programs' => StudyProgram::query()->count(),
                'courses' => Course::query()->count(),
            ],
        ]);
    }
}
