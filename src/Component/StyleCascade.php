<?php

namespace Blendhtml\Core\Component;

class StyleCascade
{
    public static function resolve(string $componentRef): array
    {
        $paths = Cascade::paths();

        $data = [];

        foreach ($paths as $base) {

            if (Cascade::vendor($base)) {
                $files = [
                    $base . '/' . $componentRef . '/style.json',
                    $base . '/' . $componentRef . '/style.php',
                ];
            } else {
                $files = [
                    $base . '/bhtml/' . $componentRef . '/style.json',
                    $base . '/bhtml/' . $componentRef . '/style.php',
                ];
            }

            foreach ($files as $file) {

                if (!is_file($file)) {
                    continue;
                }

                $tmp = match (
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

                if (is_array($tmp)) {
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
