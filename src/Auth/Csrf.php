<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

use Blendhtml\Core\Auth\Entity\CsrfToken;
use Blendhtml\Core\Auth\Exception\AuthenticationException;
use Blendhtml\Core\Logger;
use Blendhtml\Doctrine\Doctrine;
use DateTimeImmutable;
use DateTimeZone;

final class Csrf
{
    public static function token(): string
    {
        $now = self::now();
        $cookie = BrowserCookies::csrfSecret();

        if (self::validSecret($cookie)) {
            $lookup = hash('sha256', $cookie);

            $record = Doctrine::em()
                ->getRepository(CsrfToken::class)
                ->findOneBy([
                    'tokenHash' => $lookup,
                ]);

            if (
                $record instanceof CsrfToken
                && hash_equals(
                    $record->getTokenHash(),
                    $lookup
                )
                && $record->getExpiresAt() > $now
            ) {
                return $cookie;
            }
        }

        BrowserCookies::clearCsrf();

        $entityManager = Doctrine::em();
        $connection = $entityManager->getConnection();

        AuthCleanup::removeExpiredCsrfTokens(
            $connection,
            $now
        );

        $secret = self::randomSecret();
        $expiresAt = $now->modify('+1 day');

        $record = new CsrfToken(
            hash('sha256', $secret),
            $now,
            $expiresAt
        );

        $entityManager->persist($record);
        $entityManager->flush();

        BrowserCookies::setCsrf(
            $secret,
            $expiresAt->getTimestamp()
        );

        return $secret;
    }

    public static function assert(
        mixed $submitted
    ): void {
        $cookie = BrowserCookies::csrfSecret();

        if (
            !is_string($submitted)
            || !self::validSecret($submitted)
            || !self::validSecret($cookie)
            || !hash_equals($cookie, $submitted)
        ) {
            self::reject();
        }

        $lookup = hash('sha256', $submitted);

        $record = Doctrine::em()
            ->getRepository(CsrfToken::class)
            ->findOneBy([
                'tokenHash' => $lookup,
            ]);

        $now = self::now();

        if (
            !$record instanceof CsrfToken
            || !hash_equals(
                $record->getTokenHash(),
                $lookup
            )
            || $record->getExpiresAt() <= $now
        ) {
            self::reject();
        }

        if (
            $now->getTimestamp()
            - $record->getLastUsedAt()->getTimestamp()
            >= 900
        ) {
            $record->touch($now);
            Doctrine::em()->flush();
        }
    }

    private static function reject(): never
    {
        Logger::event(
            'auth.csrf.invalid',
            [],
            'warning'
        );

        throw new AuthenticationException(
            AuthenticationException::INVALID_CSRF,
            'The form has expired or is invalid. Please try again.',
            403
        );
    }

    private static function validSecret(
        mixed $secret
    ): bool {
        return is_string($secret)
            && preg_match(
                '/^[A-Za-z0-9_-]{43}$/',
                $secret
            ) === 1;
    }

    private static function randomSecret(): string
    {
        return rtrim(
            strtr(
                base64_encode(random_bytes(32)),
                '+/',
                '-_'
            ),
            '='
        );
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );
    }
}
