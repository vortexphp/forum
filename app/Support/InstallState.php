<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;
use Vortex\AppContext;
use Vortex\Database\Connection;
use Vortex\Database\Schema\SchemaMigrator;

final class InstallState
{
    public static function isInstalled(): bool
    {
        try {
            $db = self::connection();
            $db->selectOne('SELECT 1 FROM users LIMIT 1');
            $db->selectOne('SELECT 1 FROM categories LIMIT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public static function install(): int
    {
        $migrator = new SchemaMigrator(self::basePath(), self::connection());

        return $migrator->up();
    }

    private static function connection(): Connection
    {
        return AppContext::container()->make(Connection::class);
    }

    private static function basePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
