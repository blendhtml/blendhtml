#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__, 5) . '/vendor/autoload.php';
require dirname(__DIR__) . '/helpers/readInputFile.php';

use Blendhtml\Core\Auth\AuthAdmin;
use Blendhtml\Core\Auth\BlendhtmlRoles;
use Blendhtml\Core\Auth\Entity\User;
use Blendhtml\Core\Auth\RoleName;
use Blendhtml\Doctrine\Doctrine;

try {
    $entries = readInputFile('auth/syncUsersRoles');

    $entityManager = Doctrine::em();

    foreach ($entries as $entry) {
        if (!str_contains($entry, ':')) {
            throw new RuntimeException(
                "Invalid input line: {$entry}"
            );
        }

        [
            $email,
            $roles
        ] = array_map(
            'trim',
            explode(':', $entry, 2)
        );

        if ($email === '') {
            throw new RuntimeException(
                "Email address is missing: {$entry}"
            );
        }

        $email = strtolower($email);

        $roleNames = [];

        foreach (explode(',', $roles) as $roleName) {
            $roleName = trim($roleName);

            if ($roleName === '') {
                continue;
            }

            $roleName = RoleName::normalize($roleName);
            $roleNames[$roleName] = true;
        }

        $roleNames = array_keys($roleNames);

        $user = $entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'email' => $email,
            ]);

        if (!$user instanceof User) {
            $now = new DateTimeImmutable();

            $user = new User(
                $email,
                $now
            );

            $entityManager->persist($user);
            $entityManager->flush();

            echo "Created user: {$email}" . PHP_EOL;
        }

        $existingRoles = AuthAdmin::getRoles();

        foreach ($roleNames as $roleName) {
            if (in_array($roleName, $existingRoles, true)) {
                continue;
            }

            if (BlendhtmlRoles::isProtected($roleName)) {
                throw new RuntimeException(
                    "Protected Blendhtml role '{$roleName}' does not exist. Run the Blendhtml migrations or seeding."
                );
            }

            echo PHP_EOL;
            echo "New project role detected: {$roleName}" . PHP_EOL;
            echo "Create this role? [y/N]: ";

            $answer = trim(
                fgets(STDIN) ?: ''
            );

            if (
                strtolower($answer) !== 'y'
                && strtolower($answer) !== 'yes'
            ) {
                throw new RuntimeException(
                    "Role creation cancelled: {$roleName}"
                );
            }

            $createdRole = AuthAdmin::createRole($roleName);
            $existingRoles[] = $createdRole->getName();

            echo "Created project role: {$roleName}" . PHP_EOL;
        }

        /*
         * Synchronize roles assigned to this user.
         *
         * This may add/remove role assignments for the user,
         * but it NEVER deletes role definitions.
         */
        AuthAdmin::syncRoles(
            $user->getId(),
            $roleNames
        );

        echo sprintf(
            "Synced: %s [%s]" . PHP_EOL,
            $email,
            $roleNames === []
                ? 'no roles'
                : implode(', ', $roleNames)
        ) .PHP_EOL;
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