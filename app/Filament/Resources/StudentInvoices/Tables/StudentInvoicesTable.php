<?php

namespace App\Filament\Resources\StudentInvoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class StudentInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('Nomor invoice')->searchable()->sortable(),
                TextColumn::make('enrollment.studentProfile.full_name')->label('Mahasiswa')->searchable(),
                TextColumn::make('total_amount')->label('Total')->money('IDR')->sortable(),
                TextColumn::make('paid_amount')->label('Terbayar')->money('IDR')->sortable(),
                TextColumn::make('outstanding_amount')->label('Piutang')->state(fn ($record) => $record->outstanding_amount)->money('IDR')->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('due_on')->label('Jatuh tempo')->date()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
