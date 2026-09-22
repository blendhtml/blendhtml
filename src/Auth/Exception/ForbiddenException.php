<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Exception;

use RuntimeException;

final class ForbiddenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The authenticated user does not have the required role.'
        );
    }
}
