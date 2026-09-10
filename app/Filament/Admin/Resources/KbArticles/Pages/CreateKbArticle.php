<?php

namespace App\Filament\Admin\Resources\KbArticles\Pages;

use App\Filament\Admin\Resources\KbArticles\KbArticleResource;
use App\Services\KnowledgeBaseService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateKbArticle extends CreateRecord
{
    protected static string $resource = KbArticleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(KnowledgeBaseService::class)->createArticle($data, auth()->guard()->user());
    }
}
