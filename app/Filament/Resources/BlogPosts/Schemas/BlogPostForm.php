<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Konten artikel')->schema([
                TextInput::make('title')->label('Judul')->required()->maxLength(255),
                TextInput::make('slug')->label('Slug URL')->required()->unique(ignoreRecord: true)->maxLength(255),
                Select::make('category_id')->label('Kategori')->relationship('category', 'name')->searchable()->preload(),
                RichEditor::make('content')->label('Isi artikel')->columnSpanFull()->required(),
                Textarea::make('excerpt')->label('Ringkasan')->rows(3)->columnSpanFull(),
                FileUpload::make('featured_image')->label('Gambar unggulan')->image()->disk('public')->directory('blog'),
            ])->columns(2),
            Section::make('Publikasi & SEO')->schema([
                Toggle::make('is_published')->label('Publikasikan')->default(false),
                DateTimePicker::make('published_at')->label('Tanggal publikasi')->seconds(false),
                TextInput::make('meta_title')->label('Meta title')->maxLength(255),
                Textarea::make('meta_description')->label('Meta description')->rows(2)->maxLength(255),
            ])->columns(2),
        ]);
    }
}
