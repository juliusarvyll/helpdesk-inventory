<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TagService
{
    public function create(array $data): Tag
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Tag::create($data);
    }

    public function update(Tag $tag, array $data): Tag
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $tag->update($data);

        return $tag->fresh();
    }

    public function delete(Tag $tag): bool
    {
        return $tag->delete();
    }

    public function syncTags(Model $model, array $tagIds): void
    {
        /** @phpstan-ignore method.notFound */
        $model->tags()->sync($tagIds);
    }

    public function attachTags(Model $model, array $tagIds): void
    {
        /** @phpstan-ignore method.notFound */
        $model->tags()->attach($tagIds);
    }

    public function detachTags(Model $model, array $tagIds): void
    {
        /** @phpstan-ignore method.notFound */
        $model->tags()->detach($tagIds);
    }
}
