<?php

declare(strict_types=1);

namespace Blendhtml\Core\VisitMetricsV1\Entity;

use Blendhtml\Core\VisitMetricsV1\Repository\VisitMetricsConsentV1Repository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitMetricsConsentV1Repository::class)]
#[ORM\Table(name: 'visit_metrics_consent_v1')]
final class VisitMetricsConsentV1
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 16)]
    private string $action;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $referrer;

    #[ORM\Column(name: 'visitor_token', length: 36, nullable: true)]
    private ?string $visitorToken;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $location;

    #[ORM\Column(type: Types::JSON)]
    private array $errors;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $timestamp;

    public function __construct(
        string $action,
        ?string $referrer,
        ?string $visitorToken,
        ?array $location,
        array $errors,
        DateTimeImmutable $timestamp,
    ) {
        $this->action = $action;
        $this->referrer = $referrer;
        $this->visitorToken = $visitorToken;
        $this->location = $location;
        $this->errors = $errors;
        $this->timestamp = $timestamp;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    public function getVisitorToken(): ?string
    {
        return $this->visitorToken;
    }

    public function getLocation(): ?array
    {
        return $this->location;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }
}