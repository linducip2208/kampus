<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudyProgram;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExecutiveStats extends StatsOverviewWidget
{
    use DashboardWidgetFilter;
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '45s';

    protected function getStats(): array
    {
        $outstanding = StudentInvoice::query()->selectRaw('SUM(total_amount - paid_amount) as total')->value('total') ?: 0;
        return [
            Stat::make('Mahasiswa aktif', StudentEnrollment::where('status', 'active')->count())->description('Terdaftar semester berjalan')->color('success')->icon('heroicon-o-academic-cap'),
            Stat::make('Program studi', StudyProgram::count())->description('Lintas struktur organisasi')->color('info')->icon('heroicon-o-building-library'),
            Stat::make('Pendapatan tercatat', 'Rp '.number_format((float) Payment::where('status', 'paid')->sum('amount'), 0, ',', '.'))->description('Payment ledger')->color('warning')->icon('heroicon-o-banknotes'),
            Stat::make('Piutang berjalan', 'Rp '.number_format((float) $outstanding, 0, ',', '.'))->description('Perlu ditindaklanjuti')->color('danger')->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
