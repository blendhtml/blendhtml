<?php

namespace Blendhtml\Core;

use Blendhtml\Core\Component\Renderer;

final class PageBuilder
{
    private const PAGE_AREAS = [
        'header',
        'main',
        'footer',
        'modals'
    ];

    public static function render(array $context = []): string
    {
        $root = rtrim(Context::root(), '/');
        $page = trim(Context::page(), '/');

        $directory = $page === ''
            ? $root
            : $root . '/' . $page;

        $indexFile = $directory . '/index.html.twig';
        $pageJsonFile = $directory . '/page.json';

        if (
            is_file($indexFile)
            && is_file($pageJsonFile)
        ) {
            throw new \LogicException(
                "page.json cannot exist along with index.html.twig: {$directory}"
            );
        }

        if (is_file($indexFile)) {
            return Twig::instance()->render(
                $page === ''
                    ? 'index.html.twig'
                    : $page . '/index.html.twig',
                $context
            );
        }

        return self::renderPage(
            PageJsonCascade::resolve(
                $directory,
                $root
            ),
            $context
        );
    }

    private static function renderPage(
        array $definition,
        array $context = []
    ): string
    {
        $chunks = [];

        foreach (self::PAGE_AREAS as $area) {
            if (!array_key_exists($area, $definition)) {
                continue;
            }

            $chunks[] = self::renderArea(
                $area,
                $definition[$area],
                $context
            );
        }

        foreach ($definition as $key => $value) {
            if (is_int($key)) {
                $chunks[] = self::renderListItem(
                    $value,
                    $context
                );
                continue;
            }

            $name = self::normalizeName($key);

            if (
                $name === ''
                || self::isPageArea($name)
            ) {
                continue;
            }

            $chunks[] = Renderer::render(
                $name,
                is_array($value) ? $value : [],
                $context
            );
        }

        return self::joinChunks($chunks);
    }

    private static function renderDefinition(
        array $definition,
        array $context = []
    ): string
    {
        if (self::isComponentTuple($definition)) {
            return self::renderComponentTuple(
                $definition,
                $context
            );
        }

        $chunks = [];

        foreach ($definition as $key => $value) {
            if (is_int($key)) {
                $chunks[] = self::renderListItem(
                    $value,
                    $context
                );
                continue;
            }

            $name = self::normalizeName($key);

            if ($name === '') {
                continue;
            }

            $chunks[] = Renderer::render(
                $name,
                is_array($value) ? $value : [],
                $context
            );
        }

        return self::joinChunks($chunks);
    }

    private static function renderArea(
        string $area,
        mixed $value,
        array $context = []
    ): string {
        if (is_string($value)) {
            $componentRef = trim($value);

            $html = $componentRef === ''
                ? ''
                : Renderer::render(
                    $componentRef,
                    [],
                    $context
                );
        } elseif (is_array($value)) {
            $html = self::renderDefinition(
                $value,
                $context
            );
        } else {
            $html = '';
        }

        if (
            $area === 'main'
            && trim($html) !== ''
        ) {
            return '<main>' . "\n"
                . trim($html)
                . "\n" . '</main>';
        }

        return $html;
    }

    private static function renderListItem(
        mixed $value,
        array $context = []
    ): string
    {
        if (is_string($value)) {
            $componentRef = trim($value);

            return $componentRef === ''
                ? ''
                : Renderer::render(
                    $componentRef,
                    [],
                    $context
                );
        }

        if (is_array($value)) {
            if (self::isComponentTuple($value)) {
                return self::renderComponentTuple(
                    $value,
                    $context
                );
            }

            return self::renderDefinition(
                $value,
                $context
            );
        }

        return '';
    }

    private static function isComponentTuple(array $value): bool
    {
        return array_is_list($value)
            && isset($value[0])
            && is_string($value[0]);
    }

    private static function renderComponentTuple(
        array $value,
        array $context = []
    ): string
    {
        $componentRef = trim($value[0]);

        if ($componentRef === '') {
            return '';
        }

        $props = [];

        if (
            array_key_exists(1, $value)
            && is_array($value[1])
        ) {
            $props = $value[1];
        }

        return Renderer::render(
            $componentRef,
            $props,
            $context
        );
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);

        if (str_starts_with($name, '!')) {
            $name = substr($name, 1);
        }

        return trim($name);
    }

    private static function isPageArea(string $name): bool
    {
        return in_array(
            $name,
            self::PAGE_AREAS,
            true
        );
    }

    private static function joinChunks(array $chunks): string
    {
        $chunks = array_values(
            array_filter(
                array_map(
                    static fn(string $chunk): string => trim($chunk),
                    $chunks
                ),
                static fn(string $chunk): bool => $chunk !== ''
            )
        );

        return implode(
            "\n\n",
            $chunks
        );
    }
}
