<?php

declare(strict_types=1);

namespace App\Support\ForumContent;

interface RawContentPlugin
{
    public function apply(string $text): string;
}
