<?php

namespace Blendhtml\Core;

use Blendhtml\Assets\AssetVendor;
use Blendhtml\Core\Component\Renderer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Twig
{
    private static ?Environment $instance = null;
    private static ?string $pagesDirectory = null;

    public static function init(string $pagesDirectory): void
    {
        $pagesDirectory = rtrim($pagesDirectory, '/');

        if (
            self::$instance
            && self::$pagesDirectory === $pagesDirectory
        ) {
            return;
        }

        $loader = new FilesystemLoader($pagesDirectory);

        $loader->addPath(
            dirname(__DIR__) . '/twig',
            'blendhtml'
        );

        $loader->addPath(
            Vendor::componentsPath(),
            'components'
        );

        $twig = new Environment($loader);

        // -------------------------
        // GLOBAL FUNCTION: bhtml
        // -------------------------
        $twig->addFunction(
            new TwigFunction(
                'bhtml',
                function (string $componentRef, array $props = []) {
                    return new \Twig\Markup(
                        Renderer::render(
                            $componentRef,
                            $props
                        ),
                        'UTF-8'
                    );
                }
            )
        );

        // -------------------------
        // GLOBAL FUNCTION: tailwind
        // -------------------------
        $twig->addFunction(
            new TwigFunction(
                'tailwind',
                function (string $bhtmlPage) {
                    $css = '/bhtml/' . trim($bhtmlPage, '/') . '/tailwind.css';
                    
                    if (file_exists(dirname(__DIR__, 4) . '/htdocs' . $css)) {
                        return new \Twig\Markup(
                            '<link rel="stylesheet" href="' . $css . '">',
                            'UTF-8'
                        );
                    }

                    return new \Twig\Markup(
                        '<script src="https://cdn.tailwindcss.com"></script>',
                        'UTF-8'
                    );
                }
            )
        );

        // -------------------------
        // GLOBAL FUNCTION: asset
        // -------------------------
        $twig->addFunction(
            new TwigFunction(
                'asset',
                function (string $asset, ?string $version = null) {
                    return new \Twig\Markup(AssetVendor::embed($asset, $version), 'UTF-8');
                }
            )
        );

        self::$instance = $twig;
        self::$pagesDirectory = $pagesDirectory;
    }

    public static function instance(): Environment
    {
        if (!self::$instance) {
            throw new \LogicException('Twig not initialized');
        }

        return self::$instance;
    }
}
