<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class ContentFlag extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['reporter_id', 'target_type', 'target_id', 'reason', 'status'];

    public static function hasUserFlagged(int $userId, string $targetType, int $targetId): bool
    {
        return static::query()
            ->where('reporter_id', $userId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->exists();
    }
}
