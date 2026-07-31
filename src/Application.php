<?php

namespace BlendHtml\Core;

use BlendHtml\Assets\AssetProxy;
use BlendHtml\Core\Page\ComingSoon;
use BlendHtml\Core\Page\Page404;
use BlendHtml\Core\Page\Page500;
use Throwable;

class Application
{
    private readonly string $pagesAbsoluteDir;

    public function __construct()
    {
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

        $pagesRelativeDir = getenv('BLENDHTML_PAGES') ?: 'BlendHtml';

        $this->pagesAbsoluteDir =
            dirname(getcwd())
            . '/'
            . $pagesRelativeDir;

        if (Context::devMode() === false) {
            error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        }

        Context::setRoot($this->pagesAbsoluteDir);
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

    private function resolveRoute(string $url): string
    {
        $urlRewritten = false;

        $routesFile =
            $this->pagesAbsoluteDir
            . '/routes.json';

        if (file_exists($routesFile)) {

            $routes = json_decode(
                file_get_contents($routesFile),
                true
            );

            if (
                is_array($routes)
                && isset($routes[$url])
            ) {
                $url = $routes[$url];
                $urlRewritten = true;
            }
        }

        if ($urlRewritten === false) {

            $routerFile =
                $this->pagesAbsoluteDir
                . '/Router.php';

            if (file_exists($routerFile)) {

                require_once $routerFile;

                $url = \BlendHtml\Pages\Router::process($url);
            }
        }

        return $url;
    }

    public function render(string $uri): string
    {
        try {
            $uri = $this->sanitizeUri($uri);

            $url = parse_url($uri, PHP_URL_PATH);

            if ($url === '/proxy') {
                AssetProxy::serve($_GET['src'] ?? '');
            }

            $query = parse_url($uri, PHP_URL_QUERY);

            [$urlWithoutLocale, $initialLocale] = $this->stripLocaleFromUrl($url);

            $uriWithoutLocale = empty($query) ? $urlWithoutLocale : $urlWithoutLocale . '?' . $query;

            $route = $this->resolveRoute($urlWithoutLocale);

            // @todo Should redirect with a proper locale
            if ($route === null) {
                http_response_code(404);
                return Page404::render();
            }

            $page = (new PageLocator($this->pagesAbsoluteDir))
                ->findByRoute($route);

            // ------------------------------------------------------------
            // Loading proper ENV (nested)
            // ------------------------------------------------------------
            if ($page) {
                Env::rewrite(
                    EnvCascade::resolve($page->directory)
                );
            }

            if ($urlWithoutLocale === '/locale') {
                LocaleSwitcher::handle(
                    strtolower(trim($_GET['locale'] ?? '')),
                    $_GET['redirect'] ?? null
                );
            }

            if (!$page) {
                Context::setLocale($initialLocale ?? getenv('LOCALE') ?? 'en');

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
            Context::setPage(
                $page->module
                . '/'
                . $page->name
            );
            Context::setLocale($initialLocale);
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
                $this->pagesAbsoluteDir
            );

            return PageView::render(
                $page,
                array_merge(
                    $controllerChainData,
                    [
                        '_GET' => $_GET,
                        '_POST' => $_POST,
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

            return Page500::render();
        }
    }
}
