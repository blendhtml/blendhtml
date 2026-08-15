<?php

declare(strict_types=1);

global $inputData;

$event = $inputData['event'] ?? null;

writeToFile([
    'action' => 'timeOnPage',
    'event' => $event,
    'seconds' => $inputData['seconds'] ?? null,
    'event_id' => $inputData['event_id'] ?? null,
    'client_timestamp' => $inputData['ts'] ?? null,
]);

echo json_encode([
    'success' => true,
]);

exit;