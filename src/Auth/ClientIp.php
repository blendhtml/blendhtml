<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

final class ClientIp
{
    public static function resolve(): string
    {
        $remote = self::validIp(
            $_SERVER['REMOTE_ADDR'] ?? null
        ) ?? '0.0.0.0';

        $trusted = Settings::trustedProxies();

        if (!self::isTrusted($remote, $trusted)) {
            return $remote;
        }

        $forwardedFor =
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;

        if (
            is_string($forwardedFor)
            && strlen($forwardedFor) <= 4096
        ) {
            $chain = [];

            foreach (
                array_slice(
                    explode(',', $forwardedFor),
                    0,
                    32
                )
                as $candidate
            ) {
                $ip = self::validIp(trim($candidate));

                if ($ip !== null) {
                    $chain[] = $ip;
                }
            }

            $chain[] = $remote;

            for ($i = count($chain) - 1; $i >= 0; $i--) {
                if (!self::isTrusted($chain[$i], $trusted)) {
                    return $chain[$i];
                }
            }

            if ($chain !== []) {
                return $chain[0];
            }
        }

        foreach (
            [
                'HTTP_CF_CONNECTING_IP',
                'HTTP_X_REAL_IP',
            ]
            as $header
        ) {
            $ip = self::validIp(
                $_SERVER[$header] ?? null
            );

            if ($ip !== null) {
                return $ip;
            }
        }

        return $remote;
    }

    public static function normalizeTrustedProxies(
        array $proxies
    ): array {
        $normalized = [];

        foreach ($proxies as $proxy) {
            if (!is_string($proxy)) {
                throw new \InvalidArgumentException(
                    'Trusted proxy entries must be strings.'
                );
            }

            $entry = self::normalizeProxy($proxy);
            $normalized[$entry] = true;
        }

        return array_keys($normalized);
    }

    private static function normalizeProxy(
        string $proxy
    ): string {
        $proxy = trim($proxy);

        if ($proxy === '') {
            throw new \InvalidArgumentException(
                'Trusted proxy entries cannot be empty.'
            );
        }

        if (!str_contains($proxy, '/')) {
            $ip = self::validIp($proxy);

            if ($ip === null) {
                throw new \InvalidArgumentException(
                    'Trusted proxy address is invalid.'
                );
            }

            return $ip;
        }

        [$address, $prefix] = explode('/', $proxy, 2);
        $ip = self::validIp($address);

        if (
            $ip === null
            || !ctype_digit($prefix)
        ) {
            throw new \InvalidArgumentException(
                'Trusted proxy CIDR is invalid.'
            );
        }

        $maximum = str_contains($ip, ':')
            ? 128
            : 32;

        $prefixLength = (int)$prefix;

        if (
            $prefixLength < 0
            || $prefixLength > $maximum
        ) {
            throw new \InvalidArgumentException(
                'Trusted proxy CIDR prefix is invalid.'
            );
        }

        return $ip . '/' . $prefixLength;
    }

    private static function isTrusted(
        string $ip,
        array $trusted
    ): bool {
        foreach ($trusted as $proxy) {
            if (!str_contains($proxy, '/')) {
                if ($ip === $proxy) {
                    return true;
                }

                continue;
            }

            if (self::inCidr($ip, $proxy)) {
                return true;
            }
        }

        return false;
    }

    private static function inCidr(
        string $ip,
        string $cidr
    ): bool {
        [$network, $prefix] = explode('/', $cidr, 2);

        $addressBytes = inet_pton($ip);
        $networkBytes = inet_pton($network);

        if (
            $addressBytes === false
            || $networkBytes === false
            || strlen($addressBytes) !== strlen($networkBytes)
        ) {
            return false;
        }

        $prefixLength = (int)$prefix;
        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if (
            $fullBytes > 0
            && substr($addressBytes, 0, $fullBytes)
                !== substr($networkBytes, 0, $fullBytes)
        ) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (
            ord($addressBytes[$fullBytes]) & $mask
        ) === (
            ord($networkBytes[$fullBytes]) & $mask
        );
    }

    private static function validIp(
        mixed $value
    ): ?string {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_IP
            ) === false
        ) {
            return null;
        }

        $packed = inet_pton($value);

        if ($packed === false) {
            return null;
        }

        return inet_ntop($packed) ?: null;
    }
}
