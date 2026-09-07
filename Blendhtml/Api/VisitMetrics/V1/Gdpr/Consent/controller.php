<?php

declare(strict_types=1);

use App\Entity\VisitMetricsConsentV1;
use Blendhtml\Doctrine\Doctrine;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

function getClientIp(): string
{
    foreach ([
                 'HTTP_CF_CONNECTING_IP',
                 'HTTP_X_FORWARDED_FOR',
                 'HTTP_X_REAL_IP',
                 'REMOTE_ADDR',
             ] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];

            return trim($ip);
        }
    }

    return '0.0.0.0';
}

function lookupGeo(string $ip): ?array
{
    $url = 'https://ip-api.com/json/'
        . rawurlencode($ip)
        . '?fields=status,country,regionName,city,timezone';

    $ch = curl_init($url);

    if ($ch === false) {
        return null;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $response = curl_exec($ch);

    curl_close($ch);

    if ($response === false || $response === '') {
        return null;
    }

    $data = json_decode($response, true);

    if (is_array($data) === false) {
        return null;
    }

    if (($data['status'] ?? null) !== 'success') {
        return null;
    }

    return [
        'country' => $data['country'] ?? null,
        'regionName' => $data['regionName'] ?? null,
        'city' => $data['city'] ?? null,
        'timezone' => $data['timezone'] ?? null,
    ];
}

function getLogFile(): string
{
    global $envDir;

    $directory = dirname(__DIR__, 9)
        . "/logs/visit-metrics/v1/$envDir/gdpr";

    if (
        is_dir($directory) === false &&
        mkdir($directory, 0755, true) === false &&
        is_dir($directory) === false
    ) {
        throw new \RuntimeException(
            'Unable to create GDPR log directory'
        );
    }

    $now = new DateTimeImmutable(
        'now',
        new DateTimeZone('UTC')
    );

    return $directory . '/' . $now->format('Y-m') . '.jsonl';
}

global $visitorToken;
global $errors;
global $referrer;
global $inputData;

$action = ($inputData['bool'] ?? null) === true
    ? 'accept'
    : 'reject';

$now = new DateTimeImmutable(
    'now',
    new DateTimeZone('UTC')
);

$location = lookupGeo(getClientIp());

try {
    /*
     * JSONL
     */
    $record = [
        'action' => $action,
        'referrer' => $referrer,
        'visitor_token' => $visitorToken,
        'location' => $location,
        'errors' => $errors,
        'timestamp' => $now->format(DATE_ATOM),
    ];

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
            'Unable to write GDPR log'
        );
    }

    /*
     * SQLite / Doctrine
     */
    $consent = new VisitMetricsConsentV1(
        action: $action,
        referrer: $referrer,
        visitorToken: $visitorToken,
        location: $location,
        errors: $errors,
        timestamp: $now,
    );

    $em = Doctrine::em();

    $em->persist($consent);
    $em->flush();
} catch (Throwable $exception) {
    echo json_encode([
        'success' => false,
        'errors' => [
            'Unable to store GDPR consent',
        ],
    ]);

    exit;
}

echo json_encode([
    'success' => true,
]);

exit;