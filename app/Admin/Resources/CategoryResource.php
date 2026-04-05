<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\Category;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextareaField;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\DeleteAction;
use Vortex\Admin\Tables\EditAction;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TableColumn;
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
            TableColumn::make('id'),
            TableColumn::make('name'),
            TableColumn::make('slug'),
            TableColumn::make('sort_order'),
            TableColumn::make('is_locked')->label('Locked'),
        )->withFilters(
            TextFilter::make('name', 'Name contains'),
        )->withActions(
            EditAction::make('Edit'),
            DeleteAction::make('Delete'),
        );
    }

    public static function form(): Form
    {
        return Form::make(
            TextField::make('name'),
            TextField::make('slug'),
            TextField::make('icon'),
            TextField::make('color'),
            TextareaField::make('description'),
            TextField::make('sort_order'),
            TextField::make('is_locked'),
        );
    }
}
