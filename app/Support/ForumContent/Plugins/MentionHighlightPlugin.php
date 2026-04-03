<?php

declare(strict_types=1);

namespace App\Support\ForumContent\Plugins;

use App\Support\ForumContent\HtmlContentPlugin;

final class MentionHighlightPlugin implements HtmlContentPlugin
{
    public function apply(string $html): string
    {
        return preg_replace(
            '/(^|\\s)@([A-Za-z0-9_]{3,32})/u',
            '$1<span class="forum-mention">@$2</span>',
            $html
        ) ?? $html;
    }
}
