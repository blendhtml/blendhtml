<?php

namespace Blendhtml\Core\Component;

use Blendhtml\Core\Context;
use Blendhtml\Core\Vendor;
use RuntimeException;

class TemplateResolver
{
    /**
     * Find the nearest template starting from the current page
     * and walking up to the site root.
     *
     * Returns twig path or null.
     */
    public static function resolve(string $componentRef): string
    {
        $root = rtrim(Context::root(), '/');
        $dir  = $root . '/' . rtrim(Context::page(), '/');

        while (str_starts_with($dir, $root)) {

            $candidate = $dir . '/bhtml/' . $componentRef . '/template.html.twig';

            if (file_exists($candidate)) {
                return self::toTwigPath($candidate);
            }

            if ($dir === $root) {
                break;
            }

            $dir = dirname($dir);
        }

        /**
         * Vendor fallback.
         */
        $candidate =
            Vendor::componentsPath()
            . '/'
            . $componentRef
            . '/template.html.twig';

        if (file_exists($candidate)) {
            return '@components/'
                . $componentRef
                . '/template.html.twig';
        }

        throw new RuntimeException("Component '{$componentRef}' not found");
    }

    private static function toTwigPath(string $absolutePath): string
    {
        return str_replace(
            rtrim(Context::root(), '/') . '/',
            '',
            $absolutePath
        );
    }
}
