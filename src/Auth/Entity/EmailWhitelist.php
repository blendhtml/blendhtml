<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth__email_whitelist')]
class EmailWhitelist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 254,
        unique: true
    )]
    private string $email;

    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $email,
        DateTimeImmutable $createdAt
    ) {
        $this->email = $email;
        $this->createdAt = $createdAt;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
