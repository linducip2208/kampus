<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudyProgram;

class DashboardApiController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'data' => [
                'active_students' => StudentEnrollment::where('status', 'active')->count(),
                'programs' => StudyProgram::count(),
                'outstanding' => (float) StudentInvoice::selectRaw('SUM(total_amount - paid_amount) as total')->value('total'),
            ],
            'meta' => ['version' => 'v1'],
        ]);
    }
}
