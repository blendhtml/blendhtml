<?php

declare(strict_types=1);

use Blendhtml\Core\Auth\Auth;
use Blendhtml\Core\Auth\Exception\AuthenticationException;

$method = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');

    return [
        'error' => AuthenticationException::METHOD_NOT_ALLOWED,
    ];
}

if ($method === 'POST') {
    try {
        Auth::assertCsrfToken(
            $_POST['_csrf'] ?? null
        );
    } catch (AuthenticationException $exception) {
        http_response_code($exception->status());

        if ($exception->retryAfter() !== null) {
            header(
                'Retry-After: '
                . $exception->retryAfter()
            );
        }

        return [
            'error' => $exception->reason(),
        ];
    }
}

Auth::logout(
    is_string($_GET['redirect'] ?? null)
        ? $_GET['redirect']
        : null
);