<?php

namespace Blendhtml\Core\Page;

use Blendhtml\Core\Context;

class Page500
{
    public static function render(string $errorId): string
    {
        require_once dirname(__DIR__, 2) . "/html/500/page.php";
        return '';
    }
}