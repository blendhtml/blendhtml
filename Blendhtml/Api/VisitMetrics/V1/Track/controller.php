<?php

declare(strict_types=1);

use Blendhtml\Core\VisitMetricsV1\Entity\VisitMetricsV1;
use Blendhtml\Doctrine\Doctrine;
use DateTimeImmutable;
use DateTimeZone;

global $errors;

if (empty($errors) === false) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'errors' => $errors,
    ]);

    exit;
}

function getLogFile(): string
{
    global $envDir;
    global $visitorToken;

    $directory = dirname(__DIR__, 8)
        . "/logs/visit-metrics/v1/$envDir";

    if (
        is_dir($directory) === false &&
        mkdir($directory, 0755, true) === false &&
        is_dir($directory) === false
    ) {
        throw new \RuntimeException(
            'Unable to create visit metrics log directory'
        );
    }

    return $directory . "/$visitorToken.jsonl";
}

function writeToFile(array $data): void
{
    global $referrer;

    $now = new DateTimeImmutable(
        'now',
        new DateTimeZone('UTC')
    );

    /*
     * JSONL
     */
    $record = array_merge(
        $data,
        [
            'referrer' => $referrer,
            'timestamp' => $now->format(DATE_ATOM),
        ]
    );

    $json = json_encode(
        $record,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE |
        JSON_THROW_ON_ERROR
    );

    $file = getLogFile();

    $result = file_put_contents(
        $file,
        $json . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );

    if ($result === false) {
        throw new \RuntimeException(
            'Unable to write visit metrics log'
        );
    }

    /*
     * SQLite / Doctrine
     */
    global $visitorToken;
    $metric = new VisitMetricsV1(
        action: $record['action'],
        event: $record['event'] ?? null,
        seconds: $record['seconds'] ?? null,
        ref: $record['ref'] ?? null,
        eventId: $record['event_id'],
        clientTimestamp: new DateTimeImmutable(
            $record['client_timestamp']
        ),
        referrer: $record['referrer'],
        visitorToken: $visitorToken,
        meta: $record['meta'] ?? null,
        timestamp: $now,
    );

    $em = Doctrine::em();

    $em->persist($metric);
    $em->flush();
}

return [];