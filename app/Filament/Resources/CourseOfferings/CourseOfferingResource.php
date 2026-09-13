<?php

namespace App\Filament\Resources\CourseOfferings;

use App\Filament\Resources\CourseOfferings\Pages\CreateCourseOffering;
use App\Filament\Resources\CourseOfferings\Pages\EditCourseOffering;
use App\Filament\Resources\CourseOfferings\Pages\ListCourseOfferings;
use App\Filament\Resources\CourseOfferings\Schemas\CourseOfferingForm;
use App\Filament\Resources\CourseOfferings\Tables\CourseOfferingsTable;
use App\Models\CourseOffering;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CourseOfferingResource extends Resource
{
    protected static ?string $model = CourseOffering::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;
    protected static \UnitEnum|string|null $navigationGroup = '🎓 Akademik';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Penawaran Kuliah';

    public static function form(Schema $schema): Schema
    {
        return CourseOfferingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseOfferingsTable::configure($table);
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
            'index' => ListCourseOfferings::route('/'),
            'create' => CreateCourseOffering::route('/create'),
            'edit' => EditCourseOffering::route('/{record}/edit'),
        ];
    }
}
