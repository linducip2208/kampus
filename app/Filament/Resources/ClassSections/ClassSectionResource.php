<?php

namespace App\Filament\Resources\ClassSections;

use App\Filament\Resources\ClassSections\Pages\CreateClassSection;
use App\Filament\Resources\ClassSections\Pages\EditClassSection;
use App\Filament\Resources\ClassSections\Pages\ListClassSections;
use App\Filament\Resources\ClassSections\Schemas\ClassSectionForm;
use App\Filament\Resources\ClassSections\Tables\ClassSectionsTable;
use App\Models\ClassSection;
use BackedEnum;
use App\Filament\Resources\CampusResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClassSectionResource extends CampusResource
{
    protected static ?string $model = ClassSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;
    protected static \UnitEnum|string|null $navigationGroup = '🎓 Akademik';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Kelas & Jadwal';

    public static function form(Schema $schema): Schema
    {
        return ClassSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassSectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassSections::route('/'),
            'create' => CreateClassSection::route('/create'),
            'edit' => EditClassSection::route('/{record}/edit'),
        ];
    }
}
