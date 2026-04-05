<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\Category;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\NumberField;
use Vortex\Admin\Forms\TextareaField;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Forms\ToggleField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Columns\ColorColumn;
use Vortex\Admin\Tables\Columns\ToggleColumn;
use Vortex\Admin\Tables\Columns\NumericColumn;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Tables\DeleteAction;
use Vortex\Admin\Tables\EditAction;
use Vortex\Admin\Tables\SelectFilter;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TextFilter;

final class CategoryResource extends Resource
{
    public static function model(): string
    {
        return Category::class;
    }

    public static function slug(): string
    {
        return 'categories';
    }

    public static function label(): string
    {
        return 'Category';
    }

    public static function pluralLabel(): string
    {
        return 'Categories';
    }

    public static function table(): Table
    {
        return Table::make(
            NumericColumn::make('id', 'ID', 0)->sortable()->alwaysVisible(),
            TextColumn::make('name', 'Name', 48)->sortable()->alwaysVisible(),
            TextColumn::make('slug', 'Slug', 36)->sortable()->collapsedByDefault(),
            TextColumn::make('icon', 'Icon', 6)->collapsedByDefault(),
            ColorColumn::make('color', 'Color')->collapsedByDefault()->sortable(),
            NumericColumn::make('sort_order', 'Order', 0)->sortable(),
            ToggleColumn::make('is_locked', 'Locked')->labels('Locked', 'Open', '—')->sortable(),
        )->withFilters(
            TextFilter::make('name', 'Name contains'),
            SelectFilter::make(
                'is_locked',
                ['1' => 'Locked', '0' => 'Open'],
                'Locked',
            ),
        )->withGlobalSearch(['name', 'slug', 'description'])
            ->withEmptyMessage('No categories yet.')
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
        return ['column' => 'sort_order', 'direction' => 'asc'];
    }

    public static function form(): Form
    {
        return Form::make(
            TextField::make('name'),
            TextField::make('slug'),
            TextField::make('icon', 'Icon (emoji or short code)'),
            TextField::make('color', 'Color (hex, e.g. #10b981)'),
            TextareaField::make('description'),
            NumberField::make('sort_order', 'Sort order')->integer()->min(0),
            ToggleField::make('is_locked', 'Locked (no new threads)'),
        );
    }
}
