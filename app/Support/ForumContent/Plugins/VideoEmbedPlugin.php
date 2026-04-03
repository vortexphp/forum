<?php

declare(strict_types=1);

namespace App\Support\ForumContent\Plugins;

use App\Support\ForumContent\HtmlContentPlugin;

final class VideoEmbedPlugin implements HtmlContentPlugin
{
    public function apply(string $html): string
    {
        return preg_replace_callback(
            '#<p>\s*(.*?)\s*</p>#is',
            function (array $matches): string {
                $inner = trim((string) ($matches[1] ?? ''));
                $url = $this->extractUrlFromParagraph($inner);
                if ($url === null) {
                    return (string) $matches[0];
                }

                $embed = $this->buildEmbed($url);
                if ($embed === null) {
                    return (string) $matches[0];
                }

                return '<div class="forum-video-embed"><iframe src="' . $embed['url'] . '" title="' . $embed['title'] . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>';
            },
            $html
        ) ?? $html;
    }

    private function extractUrlFromParagraph(string $inner): ?string
    {
        if (preg_match('#^<a\s+href="([^"]+)".*?>.*</a>$#is', $inner, $link) === 1) {
            $url = html_entity_decode((string) ($link[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return $url !== '' ? $url : null;
        }

        $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return filter_var($text, FILTER_VALIDATE_URL) !== false ? $text : null;
    }

    /**
     * @return array{url: string, title: string}|null
     */
    private function buildEmbed(string $url): ?array
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');

        $youtubeId = $this->extractYouTubeId($host, $path, (string) ($parts['query'] ?? ''));
        if ($youtubeId !== null) {
            return ['url' => 'https://www.youtube.com/embed/' . $youtubeId, 'title' => 'YouTube video player'];
        }

        $vimeoId = $this->extractVimeoId($host, $path);
        if ($vimeoId !== null) {
            return ['url' => 'https://player.vimeo.com/video/' . $vimeoId, 'title' => 'Vimeo video player'];
        }

        $dailymotionId = $this->extractDailymotionId($host, $path);
        if ($dailymotionId !== null) {
            return ['url' => 'https://www.dailymotion.com/embed/video/' . $dailymotionId, 'title' => 'Dailymotion video player'];
        }

        $loomId = $this->extractLoomId($host, $path);
        if ($loomId !== null) {
            return ['url' => 'https://www.loom.com/embed/' . $loomId, 'title' => 'Loom video player'];
        }

        return null;
    }

    private function extractYouTubeId(string $host, string $path, string $queryString): ?string
    {
        if ($host === 'youtu.be' && $path !== '') {
            $id = explode('/', $path)[0] ?? '';

            return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1 ? $id : null;
        }

        if ($host !== 'www.youtube.com' && $host !== 'youtube.com' && $host !== 'm.youtube.com') {
            return null;
        }

        if ($path === 'watch') {
            $query = [];
            parse_str($queryString, $query);
            $id = (string) ($query['v'] ?? '');

            return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1 ? $id : null;
        }

        if (str_starts_with($path, 'shorts/')) {
            $id = explode('/', substr($path, strlen('shorts/')))[0] ?? '';

            return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1 ? $id : null;
        }

        return null;
    }

    private function extractVimeoId(string $host, string $path): ?string
    {
        if ($host === 'vimeo.com' && preg_match('/^[0-9]+$/', $path) === 1) {
            return $path;
        }

        if ($host === 'www.vimeo.com' && preg_match('/^[0-9]+$/', $path) === 1) {
            return $path;
        }

        if ($host === 'player.vimeo.com' && str_starts_with($path, 'video/')) {
            $id = explode('/', substr($path, strlen('video/')))[0] ?? '';

            return preg_match('/^[0-9]+$/', $id) === 1 ? $id : null;
        }

        return null;
    }

    private function extractDailymotionId(string $host, string $path): ?string
    {
        if ($host === 'dai.ly' && $path !== '') {
            $id = explode('/', $path)[0] ?? '';

            return preg_match('/^[A-Za-z0-9]+$/', $id) === 1 ? $id : null;
        }

        if ($host === 'www.dailymotion.com' || $host === 'dailymotion.com') {
            if (str_starts_with($path, 'video/')) {
                $id = explode('/', substr($path, strlen('video/')))[0] ?? '';

                return preg_match('/^[A-Za-z0-9]+$/', $id) === 1 ? $id : null;
            }
        }

        return null;
    }

    private function extractLoomId(string $host, string $path): ?string
    {
        if ($host !== 'loom.com' && $host !== 'www.loom.com') {
            return null;
        }

        if (str_starts_with($path, 'share/')) {
            $id = explode('/', substr($path, strlen('share/')))[0] ?? '';

            return preg_match('/^[A-Za-z0-9]+$/', $id) === 1 ? $id : null;
        }

        if (str_starts_with($path, 'embed/')) {
            $id = explode('/', substr($path, strlen('embed/')))[0] ?? '';

            return preg_match('/^[A-Za-z0-9]+$/', $id) === 1 ? $id : null;
        }

        return null;
    }
}
