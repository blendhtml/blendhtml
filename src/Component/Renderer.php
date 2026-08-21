<?php

namespace Blendhtml\Core\Component;

use Blendhtml\Core\Context;
use Blendhtml\Core\Twig;

class Renderer
{
    public static function render(string $componentRef, array $props = []): string
    {
        $id = str_replace('/', '-', $componentRef);

        if (self::isEntity($componentRef)) {

            if (!array_key_exists('id', $props)) {
                throw new \LogicException(
                    "Entity '{$componentRef}' requires props['id']"
                );
            }

            if (
                $props['id'] === null
                || trim((string)$props['id']) === ''
            ) {
                throw new \LogicException(
                    "Entity '{$componentRef}' received invalid props['id']"
                );
            }

            $id .= '_' . $props['id'];
        }

        return Twig::instance()->render(
            TemplateResolver::resolve($componentRef),
            [
                '_bhtml' => Context::instance(),

                '_GET' => $_GET,
                '_POST' => $_POST,
                '_COOKIE' => $_COOKIE,

                'self' => [
                    'id' => $id,
                    'data' => DataCascade::resolve($componentRef),
                    'style' => StyleCascade::resolve($componentRef),
                ],

                'props' => $props,
            ]
        );
    }

    private static function isEntity(string $componentRef): bool
    {
        return str_starts_with($componentRef, 'entity/');
    }
}