<?php

namespace App\Filament\Admin\Resources\KbCategories\Pages;

use App\Filament\Admin\Resources\KbCategories\KbCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKbCategory extends CreateRecord
{
    protected static string $resource = KbCategoryResource::class;
}
