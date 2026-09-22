<?php

namespace Blendhtml\Core\Page;

final class Page401
{
    public static function render(): string
    {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Authentication required</title></head><body>'
            . '<main><h1>Authentication required</h1>'
            . '<p>Please sign in to continue.</p></main></body></html>';
    }
}
