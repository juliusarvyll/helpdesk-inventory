<?php

namespace App\Filament\Admin\Resources\ServiceCategories\Pages;

use App\Filament\Admin\Resources\ServiceCategories\ServiceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceCategory extends CreateRecord
{
    protected static string $resource = ServiceCategoryResource::class;
}
