<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Vortex\Admin\Forms\BelongsToSelectField;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\MarkdownField;
use Vortex\Admin\Forms\ToggleField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Columns\BelongsToColumn;
use Vortex\Admin\Tables\Columns\BelongsToImageColumn;
use Vortex\Admin\Tables\Columns\DatetimeColumn;
use Vortex\Admin\Tables\Columns\NumericColumn;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Tables\Columns\ToggleColumn;
use Vortex\Admin\Tables\DeleteRowAction;
use Vortex\Admin\Tables\EditRowAction;
use Vortex\Admin\Tables\SelectFilter;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TextFilter;

final class PostResource extends Resource
{
    public static function model(): string
    {
        return Post::class;
    }

    public static function slug(): string
    {
        return 'posts';
    }

    public static function label(): string
    {
        return 'Post';
    }

    public static function pluralLabel(): string
    {
        return 'Posts';
    }

    public static function navigationIcon(): ?string
    {
        return 'document';
    }

    public static function table(): Table
    {
        return Table::make(
            NumericColumn::make('id', 'ID', 0)->sortable()->alwaysVisible(),
            BelongsToColumn::make('thread', 'Thread', 'title', 52)->sortable('thread_id'),
            BelongsToImageColumn::make('author', 'Avatar')
                ->size(36, 36)
                ->openOriginalInNewTab()
                ->collapsedByDefault(),
            BelongsToColumn::make('author', 'Author', 'name', 32, 'user_id')->sortable('user_id'),
            TextColumn::make('body', 'Preview', 72)->sortable()->collapsedByDefault(),
            ToggleColumn::make('is_edited', 'Edited')->labels('Edited', 'No', '—')->sortable(),
            DatetimeColumn::make('created_at', 'Posted', 'Y-m-d H:i')->sortable(),
            DatetimeColumn::make('edited_at', 'Edited at', 'Y-m-d H:i')->sortable()->collapsedByDefault(),
        )->withFilters(
            TextFilter::make('body', 'Body contains'),
            SelectFilter::make(
                'is_edited',
                ['1' => 'Edited', '0' => 'Original'],
                'Edited',
            ),
        )->withGlobalSearch(['body'])
            ->withEmptyMessage('No posts yet.')
            ->withActions(
                EditRowAction::make('Edit'),
                DeleteRowAction::make('Delete'),
            );
    }

    /**
     * @return array{column: string, direction: string}|null
     */
    public static function defaultTableSort(): ?array
    {
        return ['column' => 'created_at', 'direction' => 'desc'];
    }

    public static function form(): Form
    {
        return Form::make(
            BelongsToSelectField::make('thread_id', Thread::class, 'Thread', 'title', 'id')
                ->orderBy('id', 'DESC')
                ->withoutEmptyOption(),
            BelongsToSelectField::make('user_id', User::class, 'Author', 'name', 'id')
                ->orderBy('name', 'ASC')
                ->withoutEmptyOption(),
            MarkdownField::make('body', 'Body')->minHeight(280),
            ToggleField::make('is_edited', 'Marked as edited'),
        );
    }
}
