<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\Tag;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Columns\DatetimeColumn;
use Vortex\Admin\Tables\Columns\NumericColumn;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Tables\DeleteAction;
use Vortex\Admin\Tables\EditAction;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TextFilter;

final class TagResource extends Resource
{
    public static function model(): string
    {
        return Tag::class;
    }

    public static function slug(): string
    {
        return 'tags';
    }

    public static function label(): string
    {
        return 'Tag';
    }

    public static function pluralLabel(): string
    {
        return 'Tags';
    }

    public static function table(): Table
    {
        return Table::make(
            NumericColumn::make('id', 'ID', 0)->sortable()->alwaysVisible(),
            TextColumn::make('name', 'Name', 40)->sortable()->alwaysVisible(),
            TextColumn::make('slug', 'Slug', 36)->sortable()->collapsedByDefault(),
            DatetimeColumn::make('created_at', 'Created', 'Y-m-d H:i')->sortable(),
        )->withFilters(
            TextFilter::make('name', 'Name contains'),
        )->withGlobalSearch(['name', 'slug'])
            ->withEmptyMessage('No tags yet.')
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
        return ['column' => 'name', 'direction' => 'asc'];
    }

    public static function form(): Form
    {
        return Form::make(
            TextField::make('name'),
            TextField::make('slug'),
        );
    }
}
