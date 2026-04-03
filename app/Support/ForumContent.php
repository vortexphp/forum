<?php

declare(strict_types=1);

namespace App\Support;

use League\CommonMark\CommonMarkConverter;

final class ForumContent
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

    public static function render(string $raw): string
    {
        $source = self::normalize($raw);
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $html = (string) $converter->convert($source);

        return self::highlightMentions($html);
    }

    public static function normalizeMessageText(string $raw): string
    {
        $text = $raw;
        $text = str_replace(array_keys(self::EMOJI_MAP), array_values(self::EMOJI_MAP), $text);

        return self::convertClassicEmoticons($text);
    }

    /**
     * @return list<string>
     */
    public static function extractMentions(string $raw): array
    {
        preg_match_all('/(^|\\s)@([A-Za-z0-9_]{3,32})/u', $raw, $matches);
        $names = [];
        foreach ($matches[2] ?? [] as $name) {
            $norm = strtolower(trim((string) $name));
            if ($norm !== '') {
                $names[$norm] = true;
            }
        }

        return array_keys($names);
    }

    private static function normalize(string $raw): string
    {
        $text = trim($raw);
        $text = str_replace(array_keys(self::EMOJI_MAP), array_values(self::EMOJI_MAP), $text);
        $text = self::convertClassicEmoticons($text);
        $text = preg_replace('/\\[b\\](.*?)\\[\\/b\\]/is', '**$1**', $text) ?? $text;
        $text = preg_replace('/\\[i\\](.*?)\\[\\/i\\]/is', '*$1*', $text) ?? $text;
        $text = preg_replace('/\\[s\\](.*?)\\[\\/s\\]/is', '~~$1~~', $text) ?? $text;
        $text = preg_replace('/\\[code\\](.*?)\\[\\/code\\]/is', "`$1`", $text) ?? $text;
        $text = preg_replace('/\\[quote\\](.*?)\\[\\/quote\\]/is', '> $1', $text) ?? $text;
        $text = preg_replace('/\\[url=(https?:\\/\\/[^\\]]+)\\](.*?)\\[\\/url\\]/is', '[$2]($1)', $text) ?? $text;
        $text = preg_replace('/\\[u\\](.*?)\\[\\/u\\]/is', '<u>$1</u>', $text) ?? $text;

        return $text;
    }

    private static function highlightMentions(string $html): string
    {
        return preg_replace(
            '/(^|\\s)@([A-Za-z0-9_]{3,32})/u',
            '$1<span class="forum-mention">@$2</span>',
            $html
        ) ?? $html;
    }

    private static function convertClassicEmoticons(string $text): string
    {
        foreach (self::CLASSIC_EMOTICON_MAP as $needle => $emoji) {
            $pattern = '/(^|\\s)' . preg_quote($needle, '/') . '(?=\\s|$)/u';
            $text = preg_replace($pattern, '$1' . $emoji, $text) ?? $text;
        }

        return $text;
    }
}
