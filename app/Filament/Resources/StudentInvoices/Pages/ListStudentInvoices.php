<?php

namespace App\Filament\Resources\StudentInvoices\Pages;

use App\Filament\Resources\StudentInvoices\StudentInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentInvoices extends ListRecords
{
    protected static string $resource = StudentInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
