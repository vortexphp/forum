<?php

declare(strict_types=1);

namespace App\Support\ForumContent\Plugins;

use App\Support\ForumContent\RawContentPlugin;

final class ClassicEmoticonPlugin implements RawContentPlugin
{
    /** @var array<string, string> */
    private const CLASSIC_EMOTICON_MAP = [
        ':-D' => '😄',
        ':D' => '😄',
        ':-P' => '😛',
        ':P' => '😛',
        ':-p' => '😛',
        ':p' => '😛',
        ':-)' => '🙂',
        ':)' => '🙂',
        ':-(' => '🙁',
        ':(' => '🙁',
        ';-)' => '😉',
        ';)' => '😉',
        ":'(" => '😢',
        'XD' => '😂',
        'xD' => '😂',
        '<3' => '❤️',
    ];

    public function apply(string $text): string
    {
        foreach (self::CLASSIC_EMOTICON_MAP as $needle => $emoji) {
            $pattern = '/(^|\\s)' . preg_quote($needle, '/') . '(?=\\s|$)/u';
            $text = preg_replace($pattern, '$1' . $emoji, $text) ?? $text;
        }

        return $text;
    }
}
