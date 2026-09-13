<?php

namespace App\Filament\Resources\CourseOfferings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class CourseOfferingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')->relationship('semester', 'name')->searchable()->preload()->required(),
                Select::make('course_id')->relationship('course', 'name')->searchable()->preload()->required(),
                Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'])->required(),
            ]);
    }
}
