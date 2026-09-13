<?php

namespace App\Filament\Resources\Applicants;

use App\Filament\Resources\Applicants\Pages\CreateApplicant;
use App\Filament\Resources\Applicants\Pages\EditApplicant;
use App\Filament\Resources\Applicants\Pages\ListApplicants;
use App\Filament\Resources\Applicants\Schemas\ApplicantForm;
use App\Filament\Resources\Applicants\Tables\ApplicantsTable;
use App\Models\Applicant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApplicantResource extends Resource
{
    protected static ?string $model = Applicant::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static \UnitEnum|string|null $navigationGroup = '🎓 PMB';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Calon Mahasiswa';

    public static function form(Schema $schema): Schema { return ApplicantForm::configure($schema); }
    public static function table(Table $table): Table { return ApplicantsTable::configure($table); }
    public static function getPages(): array { return ['index' => ListApplicants::route('/'), 'create' => CreateApplicant::route('/create'), 'edit' => EditApplicant::route('/{record}/edit')]; }
}
