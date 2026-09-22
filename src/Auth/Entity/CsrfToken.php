<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth__csrf_tokens')]
class CsrfToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(
        name: 'token_hash',
        type: Types::STRING,
        length: 64,
        unique: true
    )]
    private string $tokenHash;

    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(
        name: 'expires_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(
        name: 'last_used_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $lastUsedAt;

    public function __construct(
        string $tokenHash,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt
    ) {
        $this->tokenHash = $tokenHash;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
        $this->lastUsedAt = $createdAt;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getLastUsedAt(): DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function touch(DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }
}
