<?php

declare(strict_types=1);

namespace Blendhtml\Core\VisitMetricsV1\Entity;

use Blendhtml\Core\VisitMetricsV1\Repository\VisitMetricsV1Repository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitMetricsV1Repository::class)]
#[ORM\Table(name: 'visit_metrics_v1')]
final class VisitMetricsV1
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 32)]
    private string $action;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $event;

    #[ORM\Column(nullable: true)]
    private ?int $seconds;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ref;

    #[ORM\Column(name: 'event_id', length: 255)]
    private string $eventId;

    #[ORM\Column(name: 'client_timestamp', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $clientTimestamp;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $referrer;

    #[ORM\Column(name: 'visitor_token', length: 36, nullable: true)]
    private ?string $visitorToken;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $meta;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $timestamp;

    public function __construct(
        string $action,
        ?string $event,
        ?int $seconds,
        ?string $ref,
        string $eventId,
        DateTimeImmutable $clientTimestamp,
        ?string $referrer,
        ?string $visitorToken,
        ?array $meta,
        DateTimeImmutable $timestamp,
    ) {
        $this->action = $action;
        $this->event = $event;
        $this->seconds = $seconds;
        $this->ref = $ref;
        $this->eventId = $eventId;
        $this->clientTimestamp = $clientTimestamp;
        $this->referrer = $referrer;
        $this->visitorToken = $visitorToken;
        $this->meta = $meta;
        $this->timestamp = $timestamp;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function getSeconds(): ?int
    {
        return $this->seconds;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getClientTimestamp(): DateTimeImmutable
    {
        return $this->clientTimestamp;
    }

    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    public function getVisitorToken(): ?string
    {
        return $this->visitorToken;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }
}