<?php

declare(strict_types=1);

namespace App\Support\ForumContent\Plugins;

use App\Support\ForumContent\RawContentPlugin;

final class EmojiPlugin implements RawContentPlugin
{
    /** @var array<string, string> */
    private const EMOJI_MAP = [
        ':smile:' => '😄',
        ':thumbsup:' => '👍',
        ':heart:' => '❤️',
        ':fire:' => '🔥',
        ':laugh:' => '😂',
        ':sad:' => '😢',
        ':wink:' => '😉',
        ':rocket:' => '🚀',
    ];

    public function apply(string $text): string
    {
        return str_replace(array_keys(self::EMOJI_MAP), array_values(self::EMOJI_MAP), $text);
    }
}
