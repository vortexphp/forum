<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\Tag;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\DeleteAction;
use Vortex\Admin\Tables\EditAction;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TableColumn;
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
            TableColumn::make('id'),
            TableColumn::make('name'),
            TableColumn::make('slug'),
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
        );
    }
}
