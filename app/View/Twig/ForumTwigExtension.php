<?php

declare(strict_types=1);

namespace App\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ForumTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('avatar_color', [self::class, 'avatarColor']),
        ];
    }

    public static function avatarColor(?string $name): string
    {
        $palette = [
            '#0ea5e9',
            '#14b8a6',
            '#22c55e',
            '#84cc16',
            '#eab308',
            '#f97316',
            '#ef4444',
            '#ec4899',
            '#a855f7',
            '#6366f1',
            '#3b82f6',
            '#10b981',
        ];

        $normalized = trim((string) $name);
        if ($normalized === '') {
            return $palette[0];
        }

        $first = '';
        if (function_exists('mb_substr')) {
            $first = (string) mb_substr($normalized, 0, 1, 'UTF-8');
            if (function_exists('mb_strtoupper')) {
                $first = (string) mb_strtoupper($first, 'UTF-8');
            } else {
                $first = strtoupper($first);
            }
        } else {
            $first = strtoupper(substr($normalized, 0, 1));
        }

        $code = 0;
        if ($first !== '' && function_exists('mb_ord')) {
            $code = (int) mb_ord($first, 'UTF-8');
        } elseif ($first !== '') {
            $code = (int) ord($first);
        }

        return $palette[$code % count($palette)];
    }
}
