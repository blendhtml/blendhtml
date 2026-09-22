<?php

declare(strict_types=1);

function readInputFile(string $path): array
{
    $projectFile = dirname(__DIR__, 5)
        . '/BlendhtmlSys/cli/'
        . $path
        . '.input';

    if (is_file($projectFile)) {
        $filename = $projectFile;
    } else {
        $vendorFile = dirname(__DIR__, 2)
            . '/cli/'
            . $path
            . '.input';

        if (!is_file($vendorFile)) {
            throw new RuntimeException(
                "Input file not found in project or vendor: {$path}.input"
            );
        }

        echo PHP_EOL;
        echo "Project input file not found:" . PHP_EOL;
        echo "  {$projectFile}" . PHP_EOL;
        echo PHP_EOL;
        echo "Use the vendor input file instead?" . PHP_EOL;
        echo "  {$vendorFile}" . PHP_EOL;
        echo PHP_EOL;
        echo "Continue? [y/N]: ";

        $answer = trim(
            fgets(STDIN) ?: ''
        );

        if (!in_array(
            strtolower($answer),
            ['y', 'yes'],
            true
        )) {
            throw new RuntimeException(
                'Operation cancelled.'
            );
        }

        $filename = $vendorFile;
    }

    $lines = file(
        $filename,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    if ($lines === false) {
        throw new RuntimeException(
            "Unable to read input file: {$filename}"
        );
    }

    $items = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if (
            $line === ''
            || str_starts_with($line, '#')
        ) {
            continue;
        }

        $items[] = $line;
    }

    return $items;
}