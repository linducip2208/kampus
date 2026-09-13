<?php

namespace App\Filament\Resources\BlogPosts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Judul')->searchable()->sortable()->limit(50),
            TextColumn::make('category.name')->label('Kategori')->placeholder('Tanpa kategori'),
            TextColumn::make('author.name')->label('Penulis'),
            IconColumn::make('is_published')->label('Terbit')->boolean(),
            TextColumn::make('published_at')->label('Dipublikasikan')->dateTime('d M Y H:i')->sortable(),
        ])->filters([])->recordActions([EditAction::make()])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ])->defaultSort('created_at', 'desc');
    }
}
