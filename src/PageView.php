<?php

namespace Blendhtml\Core;

class PageView
{
    public static function render(
        Page $page,
        array $data = []
    ): string {

        $twig = Twig::instance();

        $layout = self::findLayout(
            $page->directory
        );

        if ($layout === null) {
            return $twig->render(
                '@blendhtml/layout.html.twig',
                $data
            );
        }

        return $twig->render(
            self::relativePath(
                $layout,
                Context::root()
            ),
            $data
        );
    }

    private static function findLayout(
        string $directory
    ): ?string {

        $current = rtrim($directory, '/');
        $root = rtrim(Context::root(), '/');

        while (true) {

            $layout =
                $current
                . '/layout.html.twig';

            if (is_file($layout)) {
                return $layout;
            }

            if ($current === $root) {
                return null;
            }

            $parent = dirname(
                $current
            );

            if ($parent === $current) {
                return null;
            }

            $current = $parent;
        }
    }

    private static function relativePath(
        string $path,
        string $pagesDirectory
    ): string {

        return ltrim(
            substr(
                $path,
                strlen(
                    rtrim(
                        $pagesDirectory,
                        '/'
                    )
                )
            ),
            '/'
        );
    }
}
