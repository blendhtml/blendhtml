#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__, 5) . '/vendor/autoload.php';

use Blendhtml\Core\Auth\AuthAdmin;
use Blendhtml\Core\Auth\Entity\AuthConfig;
use Blendhtml\Doctrine\Doctrine;

$config = AuthAdmin::getConfiguration();

echo PHP_EOL;
echo "Blendhtml Authentication Configuration" . PHP_EOL;
echo str_repeat('=', 42) . PHP_EOL;
echo PHP_EOL;

function printValue(
    string $label,
    mixed $value,
    string $source
): void {
    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    } elseif (is_array($value)) {
        $value = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    } elseif ($value === null) {
        $value = 'null';
    }

    printf(
        "  %-24s %-20s [%s]%s",
        $label . ':',
        (string) $value,
        strtoupper($source),
        PHP_EOL
    );
}

function getConfigSource(string $key): string
{
    $row = Doctrine::em()
        ->getRepository(AuthConfig::class)
        ->findOneBy([
            'configKey' => $key,
        ]);

    return $row !== null ? 'db' : 'default';
}

echo "[ OTP ]" . PHP_EOL;

printValue(
    'Length',
    $config['otp']['length'],
    getConfigSource('otp.length')
);

printValue(
    'Lifetime',
    $config['otp']['lifetime_seconds'],
    getConfigSource('otp.lifetime_seconds')
);

printValue(
    'Max attempts',
    $config['otp']['max_attempts'],
    getConfigSource('otp.max_attempts')
);

echo PHP_EOL;
echo "[ SESSION ]" . PHP_EOL;

printValue(
    'Lifetime',
    $config['session']['lifetime_seconds'],
    getConfigSource('session.lifetime_seconds')
);

echo PHP_EOL;
echo "[ EMAIL ]" . PHP_EOL;

printValue(
    'Access mode',
    $config['email']['access_mode'],
    getConfigSource('email.access_mode')
);

printValue(
    'Whitelist',
    $config['email']['whitelist'],
    getConfigSource('email.whitelist')
);

echo PHP_EOL;
echo "[ COOKIE ]" . PHP_EOL;

printValue(
    'Name',
    $config['cookie']['name'],
    getConfigSource('cookie.name')
);

printValue(
    'SameSite',
    $config['cookie']['same_site'],
    getConfigSource('cookie.same_site')
);

printValue(
    'Secure',
    $config['cookie']['secure'],
    getConfigSource('cookie.secure')
);

printValue(
    'HttpOnly',
    $config['cookie']['http_only'],
    getConfigSource('cookie.http_only')
);

printValue(
    'Path',
    $config['cookie']['path'],
    getConfigSource('cookie.path')
);

echo PHP_EOL;
echo "[ RATE LIMITS ]" . PHP_EOL;

printValue(
    'Request email',
    $config['rate_limits']['request_code']['email'],
    getConfigSource('rate_limits.request_code.email')
);

printValue(
    'Request IP',
    $config['rate_limits']['request_code']['ip'],
    getConfigSource('rate_limits.request_code.ip')
);

printValue(
    'Verify email',
    $config['rate_limits']['verify_code']['email'],
    getConfigSource('rate_limits.verify_code.email')
);

printValue(
    'Verify IP',
    $config['rate_limits']['verify_code']['ip'],
    getConfigSource('rate_limits.verify_code.ip')
);

echo PHP_EOL;
echo "[ TRUSTED PROXIES ]" . PHP_EOL;

printValue(
    'Proxies',
    $config['trusted_proxies'],
    getConfigSource('trusted_proxies')
);

echo PHP_EOL;