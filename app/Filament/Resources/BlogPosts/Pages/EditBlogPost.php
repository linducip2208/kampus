<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['author_id'] ??= auth()->id();
        if (($data['is_published'] ?? false) && empty($data['published_at'])) $data['published_at'] = now();
        return $data;
    }
}
