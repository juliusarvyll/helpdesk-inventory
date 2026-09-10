<?php

namespace App\Filament\Admin\Resources\KbArticles\Pages;

use App\Filament\Admin\Resources\KbArticles\KbArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKbArticles extends ListRecords
{
    protected static string $resource = KbArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
