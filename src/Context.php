<?php

namespace Blendhtml\Core;

use Blendhtml\Core\Context\Meta;

class Context
{
    private static ?self $instance = null;

    private ?bool $devMode = null;
    private ?string $locale = null;
    private ?array $locales = null;
    private ?string $root = null;
    private ?string $page = null;
    private ?string $uri = null;

    private Meta $meta;

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->meta = new Meta();
        }

        return self::$instance;
    }

    // -------------------
    // SETTERS
    // -------------------

    public static function setDevMode(bool $devMode): void
    {
        if (self::instance()->devMode !== null) {
            throw new \LogicException('Dev mode is already set');
        }

        self::instance()->devMode = $devMode;
    }

    public static function setLocale(string $locale): void
    {
        if (self::instance()->locale !== null) {
            throw new \LogicException('Locale is already set');
        }
        self::instance()->locale = $locale;
    }

    public static function setLocales(array $locales): void
    {
        if (self::instance()->locales !== null) {
            throw new \LogicException('Locales are already set');
        }
        self::instance()->locales = $locales;
    }

    public static function setRoot(string $root): void
    {
        if (self::instance()->root !== null) {
            throw new \LogicException('Root is already set');
        }

        self::instance()->root = $root;
    }

    public static function setPage(string $page): void
    {
        if (self::instance()->page !== null) {
            throw new \LogicException('Page is already set');
        }

        self::instance()->page = $page;
    }

    public static function setUri(string $uri): void
    {
        if (self::instance()->uri !== null) {
            throw new \LogicException(
                'Uri is already set'
            );
        }

        self::instance()->uri = $uri;
    }

    // -------------------
    // GETTERS
    // -------------------

    public static function devMode(): bool
    {
        return self::instance()->devMode;
    }

    public static function locale(): string
    {
        return self::instance()->locale;
    }

    public static function locales(): array
    {
        return self::instance()->locales;
    }

    public static function localeOrNull(): ?string
    {
        return self::instance()->locale;
    }
    
    public static function root(): string
    {
        return self::instance()->root;
    }

    public static function page(): string
    {
        return self::instance()->page;
    }

    public static function pageOrNull(): ?string
    {
        return self::instance()->page;
    }

    public static function uri(): string
    {
        return self::instance()->uri;
    }

    public static function uriOrNull(): ?string
    {
        return self::instance()->uri;
    }

    public static function meta(): Meta
    {
        return self::instance()->meta;
    }
}