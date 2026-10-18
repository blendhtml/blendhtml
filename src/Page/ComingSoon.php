<?php

namespace Blendhtml\Core\Page;

use Blendhtml\Core\Context;

class ComingSoon
{
    // @todo Check it! Make it work same way as page 500
    public static function render()
    {
        return require_once dirname(__DIR__, 2) . "/html/comingSoon/page.php";
    }
}