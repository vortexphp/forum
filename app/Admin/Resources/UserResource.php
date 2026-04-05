<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\User;
use Vortex\Admin\Forms\EmailField;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\PasswordField;
use Vortex\Admin\Forms\SelectField;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Forms\UploadField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Columns\BadgeColumn;
use Vortex\Admin\Tables\Columns\DatetimeColumn;
use Vortex\Admin\Tables\Columns\EmailColumn;
use Vortex\Admin\Tables\Columns\ImageColumn;
use Vortex\Admin\Tables\Columns\NumericColumn;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Tables\DeleteRowAction;
use Vortex\Admin\Tables\EditRowAction;
use Vortex\Admin\Tables\SelectFilter;
use Vortex\Admin\Tables\Table;
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

    public static function navigationIcon(): ?string
    {
        return 'users';
    }

    public static function table(): Table
    {
        return Table::make(
            NumericColumn::make('id', 'ID', 0)->sortable()->alwaysVisible(),
            ImageColumn::make('avatar', 'Avatar')
                ->size(40, 40)
                ->openOriginalInNewTab()
                ->collapsedByDefault(),
            TextColumn::make('name', 'Name', 36)->sortable()->alwaysVisible(),
            EmailColumn::make('email', 'Email')->sortable()->collapsedByDefault(),
            BadgeColumn::make('role', 'Role', [
                'member' => ['label' => 'Member', 'tone' => 'neutral'],
                'moderator' => ['label' => 'Moderator', 'tone' => 'success'],
            ])->sortable(),
            DatetimeColumn::make('created_at', 'Joined', 'Y-m-d')->sortable(),
        )->withFilters(
            TextFilter::make('name', 'Name contains'),
            TextFilter::make('email', 'Email contains'),
            SelectFilter::make(
                'role',
                ['member' => 'Member', 'moderator' => 'Moderator'],
                'Role',
            ),
        )->withGlobalSearch(['name', 'email'])
            ->withEmptyMessage('No users yet.')
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
            TextField::make('name'),
            EmailField::make('email'),
            PasswordField::make('password', 'Password (blank on edit = unchanged)'),
            UploadField::make('avatar', 'Avatar image')
                ->to('uploads/avatars')
                ->maxKb(2048)
                ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp', 'gif'])
                ->accept('image/jpeg,image/png,image/webp,image/gif'),
            SelectField::make('role', [
                'member' => 'Member',
                'moderator' => 'Moderator',
            ], 'Role')->withoutEmptyOption(),
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
