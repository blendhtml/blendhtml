#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__, 5) . '/vendor/autoload.php';
require dirname(__DIR__) . '/helpers/readInputFile.php';

use Blendhtml\Core\Auth\AuthAdmin;

try {
    $emails = readInputFile('auth/syncWhitelist');

    $emails = array_values(
        array_unique(
            array_map(
                'strtolower',
                array_map('trim', $emails)
            )
        )
    );

    foreach ($emails as $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(
                "Invalid email address: {$email}"
            );
        }
    }

    $currentEmails = AuthAdmin::getEmailWhitelist();

    foreach ($emails as $email) {
        if (in_array($email, $currentEmails, true)) {
            continue;
        }

        AuthAdmin::addEmailToWhitelist($email);

        echo "Added to whitelist: {$email}" . PHP_EOL;
    }

    foreach ($currentEmails as $email) {
        if (in_array($email, $emails, true)) {
            continue;
        }

        AuthAdmin::removeEmailFromWhitelist($email);

        echo "Removed from whitelist: {$email}" . PHP_EOL;
    }

    echo PHP_EOL;
    echo "Whitelist synchronized." . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'ERROR - '
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}