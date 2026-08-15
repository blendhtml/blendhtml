<?php

namespace Blendhtml\Core;

use Blendhtml\Core\Component\Cascade;

class PageController
{
    public static function execute(Page $page): array
    {
        $data = [];

        $root = rtrim(
            $page->rootDirectory,
            '/'
        );

        $target = rtrim(
            $page->directory,
            '/'
        );

        $data = self::mergeFile(
            $data,
            $root . '/controller.php'
        );

        if ($target === $root) {
            return $data;
        }

        $relative = trim(
            substr($target, strlen($root)),
            '/'
        );

        $current = $root;

        foreach (explode('/', $relative) as $segment) {

            if ($segment === '') {
                continue;
            }

            $current .= '/' . $segment;

            $data = self::mergeFile(
                $data,
                $current . '/controller.php'
            );
        }

        return $data;
    }

    private static function mergeFile(
        array $data,
        string $file
    ): array {
        if (!is_file($file)) {
            return $data;
        }

        $result = require $file;

        if (!is_array($result)) {
            return $data;
        }

        return Cascade::merge(
            $data,
            $result
        );
    }
}
