<?php

declare(strict_types=1);

namespace App\Support\ForumContent\Plugins;

use App\Support\ForumContent\RawContentPlugin;

final class BbCodePlugin implements RawContentPlugin
{
    public function apply(string $text): string
    {
        $text = preg_replace('/\\[b\\](.*?)\\[\\/b\\]/is', '**$1**', $text) ?? $text;
        $text = preg_replace('/\\[i\\](.*?)\\[\\/i\\]/is', '*$1*', $text) ?? $text;
        $text = preg_replace('/\\[s\\](.*?)\\[\\/s\\]/is', '~~$1~~', $text) ?? $text;
        $text = preg_replace('/\\[code\\](.*?)\\[\\/code\\]/is', "`$1`", $text) ?? $text;
        $text = preg_replace('/\\[quote\\](.*?)\\[\\/quote\\]/is', '> $1', $text) ?? $text;
        $text = preg_replace('/\\[url=(https?:\\/\\/[^\\]]+)\\](.*?)\\[\\/url\\]/is', '[$2]($1)', $text) ?? $text;

        return preg_replace('/\\[u\\](.*?)\\[\\/u\\]/is', '<u>$1</u>', $text) ?? $text;
    }
}
