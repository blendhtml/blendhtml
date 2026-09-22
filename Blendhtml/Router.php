<?php

namespace Blendhtml\Core\Pages;

class Router
{
    public static function process(
        string $url
    ): ?string
    {
        return match ($url) {
            default => $url,
        };
    }
}