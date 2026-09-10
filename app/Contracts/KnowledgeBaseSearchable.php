<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface KnowledgeBaseSearchable
{
    public function search(string $query, array $options = []): Collection;
}
