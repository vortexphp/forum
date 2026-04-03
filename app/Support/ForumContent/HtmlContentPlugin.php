<?php

declare(strict_types=1);

namespace App\Support\ForumContent;

interface HtmlContentPlugin
{
    public function apply(string $html): string;
}
