<?php

namespace App\Filament\Resources\Applicants\Tables;

use App\Models\Applicant;
use App\Services\Admission\AdmissionWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApplicantsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('registration_number')->label('Pendaftaran')->searchable()->sortable(),
            TextColumn::make('name')->label('Nama')->searchable()->sortable(),
            TextColumn::make('admissionPath.name')->label('Jalur'),
            TextColumn::make('email')->label('Email')->searchable(),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state) => str_replace('_', ' ', ucfirst($state))),
            TextColumn::make('created_at')->label('Daftar')->date('d M Y')->sortable(),
        ])->recordActions([
            EditAction::make(),
            Action::make('submit')->label('Kirim')->icon('heroicon-o-paper-airplane')->color('info')->requiresConfirmation()->visible(fn (Applicant $record) => $record->status === 'draft')->action(fn (Applicant $record) => app(AdmissionWorkflowService::class)->transition($record, 'submitted', auth()->user())),
        ])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])->defaultSort('created_at', 'desc');
    }
}
