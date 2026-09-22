<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Exception;

use RuntimeException;

final class UnauthorizedException extends RuntimeException
{
    public function __construct(
        private readonly string $redirect
    ) {
        parent::__construct(
            'Authentication is required.'
        );
    }

    public function redirect(): string
    {
        return $this->redirect;
    }
}
