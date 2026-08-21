<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (isset($_COOKIE['blendhtml_notrack'])) {
    echo json_encode([
        'success' => false,
        'errors' => [
            'blendhtml_notrack COOKIE is set',
        ],
    ]);

    exit;
}

global $inputData;

$inputData = json_decode(
    file_get_contents('php://input'),
    true
);

if (is_array($inputData) === false) {
    $inputData = [];
}

global $visitorToken;

$visitorToken = $_COOKIE['blendhtml_visitor_token'] ?? null;

global $errors;

$errors = [];

if ($visitorToken === null) {
    $errors[] = 'Missing `blendhtml_visitor_token` cookie';
}

$isLive = !filter_var(
    $_ENV['DEV_MODE'] ?? false,
    FILTER_VALIDATE_BOOLEAN
);

global $envDir;

$envDir = $isLive ? 'live' : 'dev';

global $referrer;

$referrer = isset($_SERVER['HTTP_REFERER'])
    ? filter_var(
        $_SERVER['HTTP_REFERER'],
        FILTER_SANITIZE_URL
    )
    : null;

return [];