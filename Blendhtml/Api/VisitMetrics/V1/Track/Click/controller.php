<?php

declare(strict_types=1);

global $inputData;

writeToFile([
    'action' => 'click',
    'ref' => $inputData['ref'] ?? null,
    'event_id' => $inputData['event_id'] ?? null,
    'client_timestamp' => $inputData['ts'] ?? null,
    'meta' => $inputData['meta'] ?? [],
]);

echo json_encode([
    'success' => true,
]);

exit;