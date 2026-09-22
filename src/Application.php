<?php

declare(strict_types=1);

namespace Blendhtml\Core;

use Blendhtml\Assets\AssetProxy;
use Blendhtml\Core\Page\ComingSoon;
use Blendhtml\Core\Page\Page404;
use Blendhtml\Core\Page\Page500;
use RuntimeException;
use Throwable;

class Application
{
    private readonly string $pagesAbsoluteDir;
    private readonly string $vendorPagesAbsoluteDir;

    public function __construct()
    {
        date_default_timezone_set('UTC');

        // Default .env from root
        Env::load();

        $devMode = filter_var(getenv('DEV_MODE') ?? false, FILTER_VALIDATE_BOOLEAN);

        if (
            isset($_GET['dev:token'])
            && $_GET['dev:token'] === getenv('DEV_MODE_TOKEN')
        ) {
            $devMode = true;
        }

        Context::setDevMode($devMode);

        $pagesRelativeDir = getenv('BLENDHTML_PAGES') ?: 'Blendhtml';

        $this->pagesAbsoluteDir =
            dirname(getcwd())
            . '/'
            . $pagesRelativeDir;

        $this->vendorPagesAbsoluteDir =
            dirname(__DIR__)
            . '/Blendhtml';

        if (Context::devMode() === false) {
            error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        }
    }

    private function sanitizeUri(string $uri): string
    {
        // Remove duplicated '/'
        return preg_replace('#(?<!:)/{2,}#', '/', $uri);
    }

    private function stripLocaleFromUrl(string $url): array
    {
        $parts = explode('/', trim($url, '/'));

        if (!empty($parts)) {
            $locale = array_shift($parts);
            if (in_array($locale, Locales::list()) === false) {
                $parts = array_merge([$locale], $parts);
                $locale = null;
            }
        }

        return ['/' . implode('/', $parts), $locale];
    }

    private function resolveRoute(
        string $url,
        string $pagesAbsoluteDir
    ): ?string
    {
        $urlRewritten = false;

        $routesFile =
            $pagesAbsoluteDir
            . '/routes.json';

        if (file_exists($routesFile)) {

            $routes = json_decode(
                file_get_contents($routesFile),
                true
            );

            if (
                is_array($routes)
                && array_key_exists($url, $routes)
            ) {
                $url = $routes[$url];
                $urlRewritten = true;
            }
        }

        if ($urlRewritten === false) {

            $routerFile =
                $pagesAbsoluteDir
                . '/Router.php';

            if (file_exists($routerFile)) {
                require_once $routerFile;

                $url = $pagesAbsoluteDir === $this->pagesAbsoluteDir
                    ? \Blendhtml\Pages\Router::process($url)
                    : \Blendhtml\Core\Pages\Router::process($url);
            }
        }

        return $url;
    }

    private function isReservedBlendhtmlUrl(string $url): bool
    {
        $path = trim(
            parse_url($url, PHP_URL_PATH) ?? '',
            '/'
        );

        if ($path === '') {
            return false;
        }

        $segments = explode('/', $path);

        return strtolower($segments[0] ?? '') === 'blendhtml';
    }

    private function stripReservedBlendhtmlPrefix(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        $path = '/' . trim($path, '/');

        $segments = explode(
            '/',
            trim($path, '/')
        );

        if (
            strtolower($segments[0] ?? '') !== 'blendhtml'
        ) {
            return $path;
        }

        array_shift($segments);

        if ($segments === []) {
            return '/';
        }

        return '/' . implode('/', $segments);
    }

    private function assertReservedProjectPathsAreFree(): void
    {
        $path = rtrim($this->pagesAbsoluteDir, '/') . '/Blendhtml';

        if (file_exists($path)) {
            throw new \LogicException(
                "'/blendhtml/*' is reserved by Blendhtml. Remove project path: {$path}"
            );
        }
    }

    private function ensureLocale(?string $initialLocale = null): void
    {
        if (Context::localeOrNull() !== null) {
            return;
        }

        $envLocale = empty(getenv('LOCALE')) ? null : getenv('LOCALE');

        Context::setLocale(
            $initialLocale
            ?? $envLocale
            ?? 'en'
        );
    }

    public function render(string $uri): string
    {
        try {
            $this->assertReservedProjectPathsAreFree();

            $uri = $this->sanitizeUri($uri);

            $url = parse_url($uri, PHP_URL_PATH);

            if ($url === '/proxy') {
                AssetProxy::serve($_GET['src'] ?? '');
            }

            $query = parse_url($uri, PHP_URL_QUERY);

            [$urlWithoutLocale, $initialLocale] = $this->stripLocaleFromUrl($url);

            $uriWithoutLocale =
                empty($query)
                    ? $urlWithoutLocale
                    : $urlWithoutLocale . '?' . $query;

            $reservedBlendhtmlUrl =
                $this->isReservedBlendhtmlUrl($urlWithoutLocale);


            $route = $this->resolveRoute(
                $urlWithoutLocale,
                $this->pagesAbsoluteDir
            );

            $activePagesDir = $this->pagesAbsoluteDir;

            if ($route === $urlWithoutLocale) {

                $vendorRoute = $this->resolveRoute(
                    $urlWithoutLocale,
                    $this->vendorPagesAbsoluteDir
                );

                if ($vendorRoute !== $urlWithoutLocale || $reservedBlendhtmlUrl) {
                    $route = $vendorRoute;
                    $activePagesDir = $this->vendorPagesAbsoluteDir;
                }
            }

            if ($route === null) {
                $this->ensureLocale($initialLocale);
                http_response_code(404);
                return Page404::render();
            }

            $route = $this->stripReservedBlendhtmlPrefix(
                $route
            );

            $page =
                (new PageLocator($this->pagesAbsoluteDir))->findByRoute($route)
                ?? (new PageLocator($activePagesDir))->findByRoute($route);

            // ------------------------------------------------------------
            // Loading proper ENV (nested)
            // ------------------------------------------------------------
            if ($page) {
                Env::rewrite(
                    EnvCascade::resolve(
                        $page->directory,
                        $page->rootDirectory
                    )
                );
            }

            if ($urlWithoutLocale === '/locale') {
                LocaleSwitcher::handle(
                    strtolower(trim($_GET['locale'] ?? '')),
                    $_GET['redirect'] ?? null
                );
            }

            if (!$page) {
                $this->ensureLocale($initialLocale);

                if ($route === '/') {
                    return ComingSoon::render();
                }

                http_response_code(404);
                return Page404::render();
            }

            // ------------------------------------------------------------
            // Process LOCALE
            // ------------------------------------------------------------
            UrlLocale::process($uriWithoutLocale, $initialLocale);

            // ------------------------------------------------------------
            // Set context
            // ------------------------------------------------------------
            Context::setRoot($page->rootDirectory);
            Context::setPage(
                trim(
                    $page->module
                    . '/'
                    . $page->name,
                    '/'
                )
            );
            Context::setLocale($initialLocale ?? getenv('LOCALE') ?: 'en');
            Context::setUri($uriWithoutLocale);
            Context::setLocales(Locales::allowedList());

            // ------------------------------------------------------------
            // Collect META from meta.json chain (cascade)
            // controller.php is executed after this and has priority.
            // ------------------------------------------------------------
            MetaCascade::apply($page);

            // ------------------------------------------------------------
            // Collect TWIG data from controller.php chain (cascade)
            // ------------------------------------------------------------
            $controllerChainData =
                PageController::execute($page);

            if (
                isset($controllerChainData['bhtml'])
                || isset($controllerChainData['_bhtml'])
                || isset($controllerChainData['self'])
            ) {
                throw new \LogicException(
                    "TWIG data keys 'bhtml()', '_bhtml.*' and 'self' are reserved"
                );
            }

            Twig::init(
                $page->rootDirectory
            );

            if (isset($_GET['notrack'])) {
                if ($_GET['notrack'] !== getenv('NOTRACK_TOKEN')) {
                    throw new RuntimeException("Incorrect notrack token");
                }

                $expires = time() + (365 * 24 * 3600);
                setcookie("blendhtml_notrack", "1", [
                    'expires' => $expires,
                    'path' => '/',
                    'secure' => true,
                    'samesite' => 'Lax',
                ]);
                setcookie("blendhtml_cookies_accepted", "0", [
                    'expires' => $expires,
                    'path' => '/',
                    'secure' => true,
                    'samesite' => 'Lax',
                ]);

                $_COOKIE['blendhtml_notrack'] = '1';
                $_COOKIE['blendhtml_cookies_accepted'] = '0';
            }

            return PageView::render(
                $page,
                array_merge(
                    $controllerChainData,
                    [
                        '_GET' => $_GET,
                        '_POST' => $_POST,
                        '_COOKIE' => $_COOKIE,
                        '_bhtml' => Context::instance(),
                    ]
                )
            );

        } catch (Throwable $exception) {

            http_response_code(500);

            Logger::exception($exception);

            if (Context::devMode() === true) {
                throw $exception;
            }

            $this->ensureLocale();

            return Page500::render();
        }
    }
}
