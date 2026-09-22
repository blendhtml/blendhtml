#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__, 5) . '/vendor/autoload.php';

use Blendhtml\Core\Auth\Entity\User;
use Blendhtml\Doctrine\Doctrine;

try {
    $users = Doctrine::em()
        ->getRepository(User::class)
        ->findBy(
            [],
            [
                'id' => 'DESC',
            ]
        );

    if ($users === []) {
        echo 'No users found.' . PHP_EOL;
        exit(0);
    }

    foreach ($users as $user) {
        $roles = $user->roleNames();

        echo $user->getId()
            . ' '
            . $user->getEmail();

        if ($roles !== []) {
            echo ' [' . implode(', ', $roles) . ']';
        }

        echo PHP_EOL;
    }
} catch (\Throwable $exception) {
    fwrite(
        STDERR,
        'ERROR - '
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}