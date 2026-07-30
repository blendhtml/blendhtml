# BlendHtml

BlendHtml is a small PHP 8.1+ server-side rendering framework built around Twig, filesystem routing, cascading layouts, cascading component data, and simple multilingual URLs.

It is designed to be easy for both humans and AI agents to work with: pages, layouts, controllers, metadata, environment files, and components all live in predictable folders.

## Features

- Server-side rendering with Twig 3
- Filesystem-based page routing
- Cascading layouts
- Cascading page controllers
- Cascading `meta.json` support
- Localized URLs and locale switcher
- Component rendering through `bhtml()`
- Cascading component data and styles
- Built-in 404, 500, and coming soon pages
- Environment variable cascade through nested `.env` files
- Development helpers for reload, scroll restore, and Eruda
- Simple compiler command for generated Twig inheritance blocks

## Requirements

- PHP 8.1 or newer
- Composer

## Installation

Install the package with Composer:

```bash
composer require blendhtml/blendhtml
```

Create a public entry file, for example `htdocs/index.php`:

```php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use BlendHtml\Core\Application;

echo (new Application())->render($_SERVER['REQUEST_URI'] ?? '/');
```

Use `htdocs` as the web server document root.

## Recommended project structure

```text
project/
├── BlendHtml/
│   ├── Home/
│   │   └── index.html.twig
│   ├── About/
│   │   └── index.html.twig
│   ├── layout.html.twig
│   ├── meta.json
│   ├── routes.json
│   └── controller.php
├── htdocs/
│   └── index.php
├── logs/
├── vendor/
├── .env
└── composer.json
```

By default, BlendHtml looks for pages in `project/BlendHtml` when the web root is `project/htdocs`.

The pages directory can be changed with:

```env
BLENDHTML_PAGES=BlendHtml
```

## Basic page

Create `BlendHtml/Home/index.html.twig`:

```twig
<h1>Hello from BlendHtml</h1>
<p>This page is rendered from Twig.</p>
```

The route for this page is:

```text
/home
```

Filesystem segments are normalized to PascalCase:

```text
/about-us        -> BlendHtml/AboutUs/index.html.twig
/blog/post.html  -> BlendHtml/Blog/Post_html/index.html.twig
```

## Root page

To render `/`, create:

```text
BlendHtml/index.html.twig
```

If no page exists for `/`, BlendHtml renders the built-in coming soon page.

## Layouts

BlendHtml uses cascading layouts. A page is rendered inside the closest `layout.html.twig` found in the page directory or one of its parent directories.

Example `BlendHtml/layout.html.twig`:

```twig
{% extends '@blendhtml/layout.html.twig' %}

{% block body %}
    <main>
        {% include _bhtml.page ~ '/index.html.twig' %}
    </main>
{% endblock %}
```

If no project layout is found, BlendHtml falls back to the framework layout.

## Compiler

BlendHtml includes a compiler command that injects managed inheritance blocks into layouts and component templates.

Run it from the project root:

```bash
vendor/bin/bhtml
```

The compiler scans:

```text
template.html.twig
layout.html.twig
```

It adds a managed block like this:

```twig
{#- bhtml:begin -#}
{#- BlendHtml compiler: vendor/bin/bhtml -#}
{#- Compiler-managed. Changes will be overwritten. -#}
{%- set __DIR__ = 'Home' -%}
{% extends '@blendhtml/layout.html.twig' %}
{#- bhtml:end -#}
```

The generated `extends` line can be commented or uncommented manually. That state survives recompilation.

## Routing

### routes.json

Create `BlendHtml/routes.json`:

```json
{
  "/": "/home",
  "/company": "/about"
}
```

If a route exists in `routes.json`, it is used before the dynamic router.

### Router.php

Create `BlendHtml/Router.php`:

```php
<?php

namespace BlendHtml\Pages;

final class Router
{
    public static function process(string $url): ?string
    {
        if ($url === '/start') {
            return '/home';
        }

        return $url;
    }
}
```

Return `null` to force a 404 response.

## Controllers

Controllers return arrays that become Twig data.

Create `BlendHtml/controller.php`:

```php
<?php

return [
    'siteName' => 'BlendHtml Site',
];
```

Create `BlendHtml/Home/controller.php`:

```php
<?php

return [
    'headline' => 'Welcome',
];
```

Controller data cascades from parent folders to the page folder. Nested arrays are merged.

Use the data in Twig:

```twig
<h1>{{ headline }}</h1>
<p>{{ siteName }}</p>
```

The following Twig data keys are reserved and cannot be returned from controllers:

- `bhtml`
- `_bhtml`
- `self`

## Metadata

Metadata is loaded from cascading `meta.json` files.

Create `BlendHtml/meta.json`:

```json
{
  "title": {
    "en": "BlendHtml Site",
    "et": "BlendHtml Veeb"
  },
  "description": {
    "en": "A server-rendered BlendHtml website.",
    "et": "Serveris renderdatud BlendHtml veebileht."
  }
}
```

Supported metadata fields:

- `title`
- `favicon`
- `charset`
- `viewport`
- `description`

The framework layout renders metadata through `_bhtml.meta`.

Example:

```twig
<title>{{ _bhtml.meta.title }}</title>
<meta name="description" content="{{ _bhtml.meta.description }}">
```

## Environment variables

BlendHtml loads `.env` from the project root and can rewrite environment values per page using nested `.env` files.

Root `.env` example:

```env
DEV_MODE=false
DEV_MODE_TOKEN=local-dev
BLENDHTML_PAGES=BlendHtml
LOCALE=en
LOCALES=et,ru
LOCALE_IN_URL=true
```

Supported framework variables:

| Variable | Default | Description |
| --- | --- | --- |
| `DEV_MODE` | `false` | Enables development behavior when true |
| `DEV_MODE_TOKEN` | empty | Enables development mode through `?dev:token=value` |
| `BLENDHTML_PAGES` | `BlendHtml` | Pages directory relative to the project root |
| `LOCALE` | `en` | Default locale |
| `LOCALES` | empty | Comma-separated additional locales |
| `LOCALE_IN_URL` | `true` | Redirects pages to locale-prefixed URLs when enabled |
| `COMING_SOON_URL` | `blendhtml.com` | Link used by the built-in coming soon page |

Nested `.env` files override parent values for pages inside that folder.

## Locales

Configure locales:

```env
LOCALE=en
LOCALES=et,ru
LOCALE_IN_URL=true
```

Allowed locale URLs:

```text
/en/home
/et/home
/ru/home
```

The URL locale wins over the cookie locale.

Switch locale with:

```text
/locale?locale=et&redirect=/en/home
```

BlendHtml stores the selected locale in the `bhtml_locale` cookie.

Use the current locale in Twig:

```twig
{{ _bhtml.locale }}
```

Use all allowed locales in Twig:

```twig
{% for locale in _bhtml.locales %}
    <a href="/locale?locale={{ locale }}&redirect={{ _bhtml.uri }}">{{ locale }}</a>
{% endfor %}
```

## Components

Render a component with the global Twig function:

```twig
{{ bhtml('card/hero', {
    title: 'Hello',
    text: 'Rendered by a component'
}) }}
```

Project component path:

```text
BlendHtml/bhtml/card/hero/template.html.twig
```

Example component template:

```twig
<section id="{{ self.id }}">
    <h2>{{ props.title }}</h2>
    <p>{{ props.text }}</p>
</section>
```

Component render context:

| Variable | Description |
| --- | --- |
| `props` | Props passed to `bhtml()` |
| `self.id` | Stable component id based on the component reference |
| `self.data` | Cascaded component data |
| `self.style` | Cascaded component style data |
| `_bhtml` | Framework context |

### Entity components

Component references starting with `entity/` require an `id` prop:

```twig
{{ bhtml('entity/product-card', {
    id: product.id,
    title: product.title
}) }}
```

This creates a unique component id containing the entity id.

## Component data cascade

BlendHtml loads component data from JSON and PHP files.

For project components, the cascade checks files such as:

```text
bhtml/DATA/all.json
bhtml/DATA/en.json
bhtml/card/hero/all.json
bhtml/card/hero/en.json
```

PHP data files must return arrays:

```php
<?php

return [
    'button' => [
        'label' => 'Read more',
    ],
];
```

JSON data example:

```json
{
  "button": {
    "label": "Read more"
  }
}
```

Use component data in Twig:

```twig
<a href="{{ self.data.button.url|default('#') }}">
    {{ self.data.button.label }}
</a>
```

Nested arrays are merged. Prefix a key with `!` to replace an inherited key without deep merging:

```json
{
  "!items": [
    "First",
    "Second"
  ]
}
```

## Component style cascade

Component style files are loaded from:

```text
bhtml/card/hero/style.json
bhtml/card/hero/style.php
```

Example:

```json
{
  "root": "rounded-xl bg-white p-6 shadow"
}
```

Use style data in Twig:

```twig
<section class="{{ self.style.root }}">
    {{ props.title }}
</section>
```

## Assets

BlendHtml provides an `asset()` Twig function powered by `blendhtml/assets`.

Example:

```twig
{{ asset('eruda') }}
```

Asset proxy requests are served through:

```text
/proxy?src=asset/path
```

## Tailwind helper

Use the `tailwind()` Twig function:

```twig
{{ tailwind(_bhtml.page) }}
```

BlendHtml first checks for a compiled file at:

```text
htdocs/bhtml/{page}/tailwind.css
```

If the file does not exist, it falls back to the Tailwind CDN script.

## Development mode

Enable development mode globally:

```env
DEV_MODE=true
```

Or enable it per request:

```text
?dev:token=local-dev
```

Development helpers in the default layout:

```text
?dev:reload
?dev:reload=2
?dev:scroll
?dev:scroll=300
```

When development mode is enabled, exceptions are rethrown after logging. When disabled, BlendHtml renders the built-in 500 page.

## Error pages

BlendHtml includes built-in localized pages for:

- 404
- 500
- coming soon

Included locales:

- English: `en`
- Estonian: `et`
- Russian: `ru`

## Logging

Unhandled exceptions are logged to:

```text
logs/YYYY-MM-DD.log
```

Logs include request method, host, URI, page, locale, IP address, referrer, user agent, cookies, GET data, POST data, exception class, message, file, and line.

Stack traces are included when development mode is enabled.

## Default Twig context

The framework passes `_bhtml` to Twig.

Common properties:

```twig
{{ _bhtml.devMode }}
{{ _bhtml.locale }}
{{ _bhtml.locales|join(', ') }}
{{ _bhtml.root }}
{{ _bhtml.page }}
{{ _bhtml.uri }}
{{ _bhtml.meta.title }}
{{ _bhtml.meta.description }}
```

Request data is also available:

```twig
{{ _GET.name|default('') }}
{{ _POST.email|default('') }}
```

## License

BlendHtml is released under the MIT License.
