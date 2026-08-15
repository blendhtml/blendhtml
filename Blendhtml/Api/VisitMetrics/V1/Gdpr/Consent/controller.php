<?php

declare(strict_types=1);

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


global $visitorToken;
global $errors;
global $referrer;
global $envDir;
global $inputData;

$action = ($inputData['bool'] ?? null) === true
    ? 'accept'
    : 'reject';

$now = new \DateTimeImmutable(
    'now',
    new \DateTimeZone('UTC')
);

$location = lookupGeo(getClientIp());

$directory = dirname(__DIR__, 9)
    . "/logs/visit-metrics/v1/$envDir/gdpr";

if (
    !is_dir($directory) &&
    !mkdir($directory, 0755, true) &&
    !is_dir($directory)
) {
    echo json_encode([
        'success' => false,
        'errors' => [
            'Unable to create GDPR log directory',
        ],
    ]);

    exit;
}

$file = $directory . '/' . $now->format('Y-m') . '.jsonl';

$record = [
    'action' => $action,
    'referrer' => $referrer,
    'visitor_token' => $visitorToken,
    'location' => $location,
    'errors' => $errors,
    'timestamp' => $now->format(DATE_ATOM),
];

$line = json_encode(
    $record,
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_THROW_ON_ERROR
);

$result = file_put_contents(
    $file,
    $line . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

if ($result === false) {
    echo json_encode([
        'success' => false,
        'errors' => [
            'Unable to write GDPR log',
        ],
    ]);

    exit;
}

echo json_encode([
    'success' => true,
]);

exit;