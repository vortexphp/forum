<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\Post;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextareaField;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\DeleteAction;
use Vortex\Admin\Tables\EditAction;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TableColumn;
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

    public static function table(): Table
    {
        return Table::make(
            TableColumn::make('id'),
            TableColumn::make('thread_id')->label('Thread'),
            TableColumn::make('user_id')->label('User'),
            TableColumn::make('body'),
            TableColumn::make('created_at')->label('Created'),
        )->withFilters(
            TextFilter::make('body', 'Body contains'),
        )->withActions(
            EditAction::make('Edit'),
            DeleteAction::make('Delete'),
        );
    }

    public static function form(): Form
    {
        return Form::make(
            TextField::make('thread_id'),
            TextField::make('user_id'),
            TextareaField::make('body'),
            TextField::make('is_edited'),
            TextField::make('edited_at'),
        );
    }
}
