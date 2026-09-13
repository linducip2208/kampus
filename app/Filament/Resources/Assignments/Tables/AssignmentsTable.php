<?php

namespace App\Filament\Resources\Assignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Judul')->searchable()->sortable(),
            TextColumn::make('classSection.code')->label('Kelas')->sortable(),
            TextColumn::make('status')->label('Status')->badge(),
            TextColumn::make('due_at')->label('Batas waktu')->dateTime('d M Y H:i')->sortable(),
            TextColumn::make('submissions_count')->label('Pengumpulan')->counts('submissions'),
        ])->recordActions([EditAction::make()])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])->defaultSort('due_at', 'desc');
    }
}
