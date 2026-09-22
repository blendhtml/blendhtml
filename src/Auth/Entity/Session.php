<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth__sessions')]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private User $user;

    #[ORM\Column(
        name: 'secret_lookup',
        type: Types::STRING,
        length: 64,
        unique: true
    )]
    private string $secretLookup;

    #[ORM\Column(
        name: 'secret_hash',
        type: Types::STRING,
        length: 255
    )]
    private string $secretHash;

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

    #[ORM\Column(
        name: 'revoked_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true
    )]
    private ?DateTimeImmutable $revokedAt = null;

    public function __construct(
        User $user,
        string $secretLookup,
        string $secretHash,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt
    ) {
        $this->user = $user;
        $this->secretLookup = $secretLookup;
        $this->secretHash = $secretHash;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
        $this->lastUsedAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSecretLookup(): string
    {
        return $this->secretLookup;
    }

    public function getSecretHash(): string
    {
        return $this->secretHash;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getLastUsedAt(): DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function touch(DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }

    public function revoke(DateTimeImmutable $now): void
    {
        $this->revokedAt = $now;
    }
}
