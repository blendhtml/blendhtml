<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Exception;

use RuntimeException;
use Throwable;

final class MailDeliveryException extends RuntimeException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct(
            'The verification email could not be delivered: '
            . $previous->getMessage(),
            0,
            $previous
        );
    }
}