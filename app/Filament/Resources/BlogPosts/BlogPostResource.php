<?php

namespace App\Filament\Resources\BlogPosts;

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Filament\Resources\BlogPosts\Schemas\BlogPostForm;
use App\Filament\Resources\BlogPosts\Tables\BlogPostsTable;
use App\Models\BlogPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static \UnitEnum|string|null $navigationGroup = '📈 Laporan';
    protected static ?int $navigationSort = 20;
    protected static ?string $navigationLabel = 'Artikel Blog';

    public static function form(Schema $schema): Schema { return BlogPostForm::configure($schema); }
    public static function table(Table $table): Table { return BlogPostsTable::configure($table); }
    public static function getPages(): array
    {
        return ['index' => ListBlogPosts::route('/'), 'create' => CreateBlogPost::route('/create'), 'edit' => EditBlogPost::route('/{record}/edit')];
    }
}
