<?php

namespace Blendhtml\Core\Page;

use Blendhtml\Core\Context;

class Page500
{
    public static function render()
    {
        return file_get_contents(dirname(__DIR__, 2) . "/html/500/" . Context::locale() . ".html");

    }
}