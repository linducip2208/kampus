<?php

namespace App\Filament\Resources\Curricula\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class CurriculaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Kurikulum')->searchable()->sortable(),
                TextColumn::make('studyProgram.name')->label('Program studi')->searchable(),
                TextColumn::make('year')->label('Tahun')->sortable(),
                TextColumn::make('minimum_credits')->label('Minimum SKS')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
