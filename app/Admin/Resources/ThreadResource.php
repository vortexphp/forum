<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\Category;
use App\Models\Thread;
use App\Models\User;
use Vortex\Admin\Forms\BelongsToSelectField;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\MarkdownField;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Forms\ToggleField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Columns\BelongsToColumn;
use Vortex\Admin\Tables\Columns\BelongsToImageColumn;
use Vortex\Admin\Tables\Columns\DatetimeColumn;
use Vortex\Admin\Tables\Columns\NumericColumn;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Tables\Columns\ToggleColumn;
use Vortex\Admin\Tables\DeleteAction;
use Vortex\Admin\Tables\EditAction;
use Vortex\Admin\Tables\SelectFilter;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TextFilter;

final class ThreadResource extends Resource
{
    public static function model(): string
    {
        return Thread::class;
    }

    public static function slug(): string
    {
        return 'threads';
    }

    public static function label(): string
    {
        return 'Thread';
    }

    public static function pluralLabel(): string
    {
        return 'Threads';
    }

    public static function navigationIcon(): ?string
    {
        return 'chat';
    }

    public static function table(): Table
    {
        return Table::make(
            NumericColumn::make('id', 'ID', 0)->sortable()->alwaysVisible(),
            BelongsToColumn::make('category', 'Category', 'name', 40)->sortable('category_id'),
            BelongsToImageColumn::make('author', 'Avatar')
                ->size(36, 36)
                ->openOriginalInNewTab()
                ->collapsedByDefault(),
            BelongsToColumn::make('author', 'Author', 'name', 28, 'user_id')->sortable('user_id'),
            TextColumn::make('title', 'Title', 56)->sortable()->alwaysVisible(),
            TextColumn::make('slug', 'Slug', 32)->sortable()->collapsedByDefault(),
            ToggleColumn::make('is_pinned', 'Pinned')->labels('Pinned', 'No', '—')->sortable(),
            ToggleColumn::make('is_locked', 'Locked')->labels('Locked', 'Open', '—')->sortable(),
            NumericColumn::make('reply_count', 'Replies', 0)->sortable(),
            DatetimeColumn::make('last_post_at', 'Last post', 'Y-m-d H:i')->sortable(),
            DatetimeColumn::make('created_at', 'Created', 'Y-m-d H:i')->sortable()->collapsedByDefault(),
        )->withFilters(
            TextFilter::make('title', 'Title contains'),
            TextFilter::make('slug', 'Slug contains'),
            SelectFilter::make(
                'is_pinned',
                ['1' => 'Pinned', '0' => 'Not pinned'],
                'Pinned',
            ),
            SelectFilter::make(
                'is_locked',
                ['1' => 'Locked', '0' => 'Open'],
                'Locked',
            ),
        )->withGlobalSearch(['title', 'slug', 'body'])
            ->withEmptyMessage('No threads yet.')
            ->withActions(
                EditAction::make('Edit'),
                DeleteAction::make('Delete'),
            );
    }

    /**
     * @return array{column: string, direction: string}|null
     */
    public static function defaultTableSort(): ?array
    {
        return ['column' => 'last_post_at', 'direction' => 'desc'];
    }

    public static function form(): Form
    {
        return Form::make(
            BelongsToSelectField::make('category_id', Category::class, 'Category', 'name', 'id')
                ->orderBy('sort_order', 'ASC')
                ->withoutEmptyOption(),
            BelongsToSelectField::make('user_id', User::class, 'Author', 'name', 'id')
                ->orderBy('name', 'ASC')
                ->withoutEmptyOption(),
            TextField::make('title'),
            TextField::make('slug'),
            MarkdownField::make('body', 'Opening post')->minHeight(280),
            ToggleField::make('is_pinned', 'Pinned in category'),
            ToggleField::make('is_locked', 'Locked (no replies)'),
        );
    }
}
