<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'auth__rate_limits',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_auth_rate_limit_subject',
            columns: [
                'action',
                'scope',
                'subject_hash',
            ]
        ),
    ]
)]
class RateLimit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 32
    )]
    private string $action;

    #[ORM\Column(
        type: Types::STRING,
        length: 16
    )]
    private string $scope;

    #[ORM\Column(
        name: 'subject_hash',
        type: Types::STRING,
        length: 64
    )]
    private string $subjectHash;

    #[ORM\Column(
        name: 'window_started_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $windowStartedAt;

    #[ORM\Column(type: Types::INTEGER)]
    private int $attempts;

    #[ORM\Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $updatedAt;
}
