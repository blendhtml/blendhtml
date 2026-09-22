#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__, 5) . '/vendor/autoload.php';
require dirname(__DIR__) . '/helpers/readInputFile.php';

use Blendhtml\Core\Auth\AuthAdmin;
use Blendhtml\Core\Auth\BlendhtmlRoles;
use Blendhtml\Core\Auth\RoleName;

try {
    $inputRoles = readInputFile('auth/syncRoles');

    $desiredRoles = [];

    foreach ($inputRoles as $role) {
        $role = RoleName::normalize($role);

        if (BlendhtmlRoles::isProtected($role)) {
            continue;
        }

        $desiredRoles[$role] = true;
    }

    $desiredRoles = array_keys($desiredRoles);
    sort($desiredRoles, SORT_STRING);

    $existingNames = array_values(
        array_filter(
            AuthAdmin::getRoles(),
            static fn(string $role): bool =>
                !BlendhtmlRoles::isProtected($role)
        )
    );

    $toCreate = array_diff(
        $desiredRoles,
        $existingNames
    );

    $toDelete = array_diff(
        $existingNames,
        $desiredRoles
    );

    foreach ($toCreate as $role) {
        $created = AuthAdmin::createRole($role);

        echo "Added: {$created->getName()}" . PHP_EOL;
    }

    foreach ($toDelete as $role) {
        AuthAdmin::deleteRole($role);

        echo "Removed: {$role}" . PHP_EOL;
    }

    if (
        $toCreate === []
        && $toDelete === []
    ) {
        echo "Project roles already synchronized." . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'ERROR - '
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}