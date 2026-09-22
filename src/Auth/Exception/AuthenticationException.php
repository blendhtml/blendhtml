<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Exception;

use RuntimeException;

final class AuthenticationException extends RuntimeException
{
    public const INVALID_EMAIL = 'invalid_email';
    public const EMAIL_NOT_AUTHORIZED = 'email_not_authorized';
    public const RATE_LIMITED = 'rate_limited';
    public const INVALID_CSRF = 'invalid_csrf';
    public const INVALID_CODE = 'invalid_code';
    public const EXPIRED_CODE = 'expired_code';
    public const CONSUMED_CODE = 'consumed_code';
    public const INVALIDATED_CODE = 'invalidated_code';
    public const MAXIMUM_ATTEMPTS = 'maximum_attempts';
    public const MISSING_LOGIN_FLOW = 'missing_login_flow';

    public function __construct(
        private readonly string $reason,
        private readonly string $safeMessage,
        private readonly int $status = 400,
        private readonly ?int $retryAfter = null
    ) {
        parent::__construct($safeMessage);
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function safeMessage(): string
    {
        return $this->safeMessage;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
