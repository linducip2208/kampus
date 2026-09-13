<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Curriculum;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\StudyPlan;
use App\Models\StudyProgram;
use App\Models\University;
use App\Policies\CampusPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability, array $arguments): ?bool {
            if (! $user instanceof \App\Models\User) {
                return null;
            }

            return app(CampusPolicy::class)->allows($user, $ability, $arguments[0] ?? null);
        });
    }
}
