<?php

namespace Blendhtml\Core;

class PageLocator
{
    public function __construct(
        private readonly string $pagesDirectory
    ) {
    }

    public function findByRoute(string $url): ?Page
    {
        $path = trim(
            parse_url($url, PHP_URL_PATH) ?? '',
            '/'
        );

        $segments = array_values(
            array_filter(
                explode('/', $path)
            )
        );

        $segments = array_map(
            [$this, 'normalize'],
            $segments
        );

        $directory =
            rtrim($this->pagesDirectory, '/')
            . (
                $segments === []
                    ? ''
                    : '/' . implode('/', $segments)
            );

        if (!is_dir($directory)) {
            return null;
        }

        $hasIndex = is_file($directory . '/index.html.twig');
        $hasController = is_file($directory . '/controller.php');
        $hasPageJson = is_file($directory . '/page.json');

        if (
            $hasIndex
            && $hasPageJson
        ) {
            throw new \LogicException(
                "page.json cannot exist along with index.html.twig: {$directory}"
            );
        }

        if (
            !$hasIndex
            && !$hasController
            && !$hasPageJson
        ) {
            return null;
        }

        $pageName = array_pop($segments);

        return new Page(
            implode('/', $segments),
            $pageName ?? '',
            $directory,
            rtrim($this->pagesDirectory, '/')
        );
    }

    private function normalize(string $value): string
    {
        if (str_contains($value, '.')) {
            [$first, $rest] = explode(
                '.',
                $value,
                2
            );

            return
                str_replace(
                    ' ',
                    '',
                    ucwords(
                        str_replace(
                            '-',
                            ' ',
                            $first
                        )
                    )
                )
                . '_'
                . strtolower($rest);
        }

        return str_replace(
            ' ',
            '',
            ucwords(
                str_replace(
                    '-',
                    ' ',
                    $value
                )
            )
        );
    }
}
