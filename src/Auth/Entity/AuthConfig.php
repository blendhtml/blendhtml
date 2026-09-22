<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth__config')]
class AuthConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(
        name: 'config_key',
        type: Types::STRING,
        length: 64,
        unique: true
    )]
    private string $configKey;

    #[ORM\Column(type: Types::TEXT)]
    private string $value;

    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $updatedAt;

    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
