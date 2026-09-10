<?php

namespace App\Filament\Admin\Resources\ServiceCategories\Pages;

use App\Filament\Admin\Resources\ServiceCategories\ServiceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceCategories extends ListRecords
{
    protected static string $resource = ServiceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
