<?php

namespace App\Filament\Resources\StudyPlans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudyPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_enrollment_id')->relationship('enrollment', 'id')->searchable()->preload()->required(),
                Select::make('semester_id')->relationship('semester', 'name')->searchable()->preload()->required(),
                Select::make('status')->options(['draft' => 'Draft', 'submitted' => 'Submitted', 'approved' => 'Approved', 'revision' => 'Revision', 'rejected' => 'Rejected', 'finalized' => 'Finalized', 'locked' => 'Locked'])->required(),
                TextInput::make('total_credits')->numeric()->disabled()->dehydrated(false),
                Textarea::make('advisor_note')->label('Catatan dosen wali')->columnSpanFull(),
            ]);
    }
}
