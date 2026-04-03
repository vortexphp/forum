<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class ForumSetting extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['setting_key', 'setting_value'];

    protected static bool $timestamps = true;

    public static function value(string $key, ?string $default = null): ?string
    {
        $row = static::query()->where('setting_key', $key)->first();
        if ($row === null) {
            return $default;
        }

        $value = $row->setting_value ?? null;

        return $value === null ? $default : (string) $value;
    }

    public static function setValue(string $key, ?string $value): void
    {
        $existing = static::query()->where('setting_key', $key)->first();
        if ($existing !== null) {
            $existing->update(['setting_value' => $value]);

            return;
        }

        static::create([
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
    }
}
