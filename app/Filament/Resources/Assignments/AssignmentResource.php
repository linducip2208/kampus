<?php

namespace App\Filament\Resources\Assignments;

use App\Filament\Resources\Assignments\Pages\CreateAssignment;
use App\Filament\Resources\Assignments\Pages\EditAssignment;
use App\Filament\Resources\Assignments\Pages\ListAssignments;
use App\Filament\Resources\Assignments\Schemas\AssignmentForm;
use App\Filament\Resources\Assignments\Tables\AssignmentsTable;
use App\Models\Assignment;
use BackedEnum;
use App\Filament\Resources\CampusResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssignmentResource extends CampusResource
{
    protected static ?string $model = Assignment::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static \UnitEnum|string|null $navigationGroup = '🎓 Akademik';
    protected static ?int $navigationSort = 18;
    protected static ?string $navigationLabel = 'Tugas & Assignment';
    public static function form(Schema $schema): Schema { return AssignmentForm::configure($schema); }
    public static function table(Table $table): Table { return AssignmentsTable::configure($table); }
    public static function getPages(): array { return ['index' => ListAssignments::route('/'), 'create' => CreateAssignment::route('/create'), 'edit' => EditAssignment::route('/{record}/edit')]; }
}
