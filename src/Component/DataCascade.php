<?php

namespace Blendhtml\Core\Component;

use Blendhtml\Core\Context;

class DataCascade
{
    private static function loadFile(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $data = match (
        pathinfo(
            $file,
            PATHINFO_EXTENSION
        )
        ) {

            'json' => json_decode(
                file_get_contents($file),
                true,
                512,
                JSON_THROW_ON_ERROR
            ),

            'php' => require $file,

            default => [],
        };

        return is_array($data)
            ? $data
            : [];
    }

    public static function resolve(string $componentRef): array
    {
        $paths = Cascade::paths();

        $data = [];

        foreach ($paths as $base) {

            if (Cascade::vendor($base)) {
                $files = [
                    $base . '/DATA/all.json',
                    $base . '/bhtml/DATA/all.php',

                    $base . '/DATA/' . Context::locale() . '.json',
                    $base . '/DATA/' . Context::locale() . '.php',

                    $base . '/' . $componentRef . '/all.json',
                    $base . '/' . $componentRef . '/all.php',

                    $base . '/' . $componentRef . '/' . Context::locale() . '.json',
                    $base . '/' . $componentRef . '/' . Context::locale() . '.php',
                ];
            } else {
                $files = [
                    $base . '/bhtml/DATA/all.json',
                    $base . '/bhtml/DATA/all.php',

                    $base . '/bhtml/DATA/' . Context::locale() . '.json',
                    $base . '/bhtml/DATA/' . Context::locale() . '.php',

                    $base . '/bhtml/' . $componentRef . '/all.json',
                    $base . '/bhtml/' . $componentRef . '/all.php',

                    $base . '/bhtml/' . $componentRef . '/' . Context::locale() . '.json',
                    $base . '/bhtml/' . $componentRef . '/' . Context::locale() . '.php',
                ];
            }

            foreach ($files as $file) {

                $tmp = self::loadFile($file);

                if ($tmp !== []) {
                    $data = Cascade::merge(
                        $data,
                        $tmp
                    );
                }
            }
        }

        return $data;
    }
}
