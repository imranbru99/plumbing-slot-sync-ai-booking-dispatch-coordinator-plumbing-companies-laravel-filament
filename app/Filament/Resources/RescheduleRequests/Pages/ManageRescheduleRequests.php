<?php

namespace App\Filament\Resources\RescheduleRequests\Pages;

use App\Filament\Resources\RescheduleRequests\RescheduleRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRescheduleRequests extends ManageRecords
{
    protected static string $resource = RescheduleRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
