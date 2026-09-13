<?php

namespace App\Filament\Widgets;

use App\Models\StudentEnrollment;
use App\Models\StudyProgram;
use Filament\Widgets\ChartWidget;

class AcademicPulse extends ChartWidget
{
    use DashboardWidgetFilter;
    protected static ?int $sort = 2;
    protected ?string $heading = 'Mahasiswa aktif per program studi';
    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $programs = StudyProgram::withCount(['enrollments as active_count' => fn ($q) => $q->where('status', 'active')])->get();
        return ['datasets' => [['label' => 'Mahasiswa aktif', 'data' => $programs->pluck('active_count')->all(), 'backgroundColor' => ['#2563eb', '#06b6d4', '#f59e0b', '#8b5cf6']]], 'labels' => $programs->pluck('name')->all()];
    }

    protected function getType(): string { return 'bar'; }
}
