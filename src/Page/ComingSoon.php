<?php

namespace BlendHtml\Core\Page;

use BlendHtml\Core\Context;

class ComingSoon
{
    public static function render()
    {
        return require_once dirname(__DIR__, 2) . "/html/comingSoon/page.php";
    }
}