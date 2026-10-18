<?php

declare(strict_types=1);

use Blendhtml\Core\Auth\Auth;
use Blendhtml\Core\Auth\BlendhtmlRoles;
use Blendhtml\Core\Auth\Exception\ForbiddenException;

Auth::guard();

if (!Auth::hasRole(BlendhtmlRoles::ADMIN)) {
    throw new ForbiddenException();
}

return [];
