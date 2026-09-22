<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth__login_codes')]
class LoginCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 254
    )]
    private string $email;

    #[ORM\Column(
        name: 'active_email',
        type: Types::STRING,
        length: 254,
        nullable: true,
        unique: true
    )]
    private ?string $activeEmail;

    #[ORM\Column(
        name: 'code_hash',
        type: Types::STRING,
        length: 255
    )]
    private string $codeHash;

    #[ORM\Column(
        name: 'code_length',
        type: Types::SMALLINT
    )]
    private int $codeLength;

    #[ORM\Column(
        name: 'max_attempts',
        type: Types::SMALLINT
    )]
    private int $maxAttempts;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $attempts = 0;

    #[ORM\Column(
        name: 'flow_lookup',
        type: Types::STRING,
        length: 64,
        unique: true
    )]
    private string $flowLookup;

    #[ORM\Column(
        name: 'redirect_path',
        type: Types::STRING,
        length: 2048
    )]
    private string $redirectPath;

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
        name: 'consumed_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true
    )]
    private ?DateTimeImmutable $consumedAt = null;

    #[ORM\Column(
        name: 'invalidated_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true
    )]
    private ?DateTimeImmutable $invalidatedAt = null;

    public function __construct(
        string $email,
        string $codeHash,
        int $codeLength,
        int $maxAttempts,
        string $flowLookup,
        string $redirectPath,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt
    ) {
        $this->email = $email;
        $this->activeEmail = $email;
        $this->codeHash = $codeHash;
        $this->codeLength = $codeLength;
        $this->maxAttempts = $maxAttempts;
        $this->flowLookup = $flowLookup;
        $this->redirectPath = $redirectPath;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getActiveEmail(): ?string
    {
        return $this->activeEmail;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getCodeLength(): int
    {
        return $this->codeLength;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getFlowLookup(): string
    {
        return $this->flowLookup;
    }

    public function getRedirectPath(): string
    {
        return $this->redirectPath;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getConsumedAt(): ?DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function getInvalidatedAt(): ?DateTimeImmutable
    {
        return $this->invalidatedAt;
    }

    public function invalidate(DateTimeImmutable $now): void
    {
        if ($this->consumedAt !== null) {
            return;
        }

        $this->invalidatedAt = $now;
        $this->activeEmail = null;
    }

    public function consume(DateTimeImmutable $now): void
    {
        $this->consumedAt = $now;
        $this->activeEmail = null;
    }

    public function fail(DateTimeImmutable $now): bool
    {
        $this->attempts++;

        if ($this->attempts >= $this->maxAttempts) {
            $this->invalidate($now);

            return true;
        }

        return false;
    }
}
