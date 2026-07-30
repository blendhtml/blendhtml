<?php

namespace BlendHtml\Core;

use BlendHtml\Core\Component\Cascade;

class PageController
{
    public static function execute(Page $page): array
    {
        $data = [];

        $pagesDirectory = dirname(getcwd());

        $relativeDirectory = substr(
            $page->directory,
            strlen($pagesDirectory)
        );

        $segments = array_values(
            array_filter(
                explode(
                    '/',
                    trim(
                        $relativeDirectory,
                        '/'
                    )
                )
            )
        );

        $current = rtrim(
            $pagesDirectory,
            '/'
        );

        foreach ($segments as $segment) {

            $current .= '/' . $segment;

            $file =
                $current
                . '/controller.php';

            if (!is_file($file)) {
                continue;
            }

            $result = require $file;

            if (!is_array($result)) {
                continue;
            }

            $data = Cascade::merge(
                $data,
                $result
            );
            
        }

        return $data;
    }
}