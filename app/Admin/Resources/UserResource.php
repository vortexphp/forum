<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\User;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\DeleteAction;
use Vortex\Admin\Tables\EditAction;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TableColumn;
use Vortex\Admin\Tables\TextFilter;
use Vortex\Database\Model;

final class UserResource extends Resource
{
    public static function model(): string
    {
        return User::class;
    }

    public static function slug(): string
    {
        return 'users';
    }

    public static function label(): string
    {
        return 'User';
    }

    public static function pluralLabel(): string
    {
        return 'Users';
    }

    public static function table(): Table
    {
        return Table::make(
            TableColumn::make('id'),
            TableColumn::make('name'),
            TableColumn::make('email'),
            TableColumn::make('role'),
            TableColumn::make('created_at')->label('Joined'),
        )->withFilters(
            TextFilter::make('name', 'Name contains'),
            TextFilter::make('email', 'Email contains'),
        )->withActions(
            EditAction::make('Edit'),
            DeleteAction::make('Delete'),
        );
    }

    public static function form(): Form
    {
        return Form::make(
            TextField::make('name'),
            TextField::make('email'),
            TextField::make('password', 'Password (blank on edit = unchanged)'),
            TextField::make('avatar'),
            TextField::make('role'),
        );
    }

    /**
     * @param Model|null $record
     */
    public static function formValues(?Model $record): array
    {
        $values = parent::formValues($record);
        if ($record !== null) {
            $values['password'] = '';
        }

        return $values;
    }
}
