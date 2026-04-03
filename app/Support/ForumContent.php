<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\ForumContent\HtmlContentPlugin;
use App\Support\ForumContent\Plugins\BbCodePlugin;
use App\Support\ForumContent\Plugins\ClassicEmoticonPlugin;
use App\Support\ForumContent\Plugins\EmojiPlugin;
use App\Support\ForumContent\Plugins\MentionHighlightPlugin;
use App\Support\ForumContent\Plugins\VideoEmbedPlugin;
use App\Support\ForumContent\RawContentPlugin;
use League\CommonMark\CommonMarkConverter;

final class ForumContent
{
    public static function render(string $raw): string
    {
        $source = trim($raw);
        foreach (self::rawRenderPlugins() as $plugin) {
            $source = $plugin->apply($source);
        }

        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $html = (string) $converter->convert($source);
        foreach (self::htmlRenderPlugins() as $plugin) {
            $html = $plugin->apply($html);
        }

        return $html;
    }

    public static function normalizeMessageText(string $raw): string
    {
        $text = $raw;
        foreach (self::messageTextPlugins() as $plugin) {
            $text = $plugin->apply($text);
        }

        return $text;
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

    /**
     * @return list<RawContentPlugin>
     */
    private static function rawRenderPlugins(): array
    {
        return [
            new EmojiPlugin(),
            new ClassicEmoticonPlugin(),
            new BbCodePlugin(),
        ];
    }

    /**
     * @return list<RawContentPlugin>
     */
    private static function messageTextPlugins(): array
    {
        return [
            new EmojiPlugin(),
            new ClassicEmoticonPlugin(),
        ];
    }

    /**
     * @return list<HtmlContentPlugin>
     */
    private static function htmlRenderPlugins(): array
    {
        return [
            new VideoEmbedPlugin(),
            new MentionHighlightPlugin(),
        ];
    }
}
