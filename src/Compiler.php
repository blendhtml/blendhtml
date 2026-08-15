<?php

namespace Blendhtml\Core;

class Compiler
{
    public static function build(string $root): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $root,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filename = $file->getFilename();

            if (
                $filename !== 'template.html.twig' &&
                $filename !== 'layout.html.twig'
            ) {
                continue;
            }

            self::compile(
                $file->getPathname(),
                $root
            );
        }
    }

    private static function compile(
        string $file,
        string $root
    ): void
    {
        $content = file_get_contents($file);

        if ($content === false) {
            throw new \RuntimeException(
                "Cannot read file: {$file}"
            );
        }

        $relative = str_replace(
            rtrim($root, '/') . '/',
            '',
            $file
        );

        $dir = dirname($relative);

        $filename = basename($file);

        if (
            str_contains($relative, '/bhtml/') ||
            str_starts_with($relative, 'bhtml/')
        ) {
            $parentTemplate =
                self::resolveComponentParent(
                    $file,
                    $root
                );

            $componentRelativePath = Vendor::relativeToComponents(
                $parentTemplate,
                dirname($root)
            );

            if ($componentRelativePath !== null) {
                $parentTemplate =
                    '@components/'
                    . $componentRelativePath;
            }
        } elseif (
            $filename === 'layout.html.twig'
        ) {
            $parentTemplate =
                self::resolvePageParent(
                    $file,
                    $root
                );
        } else {
            $parentTemplate = '';
        }

        $extendsState = 'enabled';

        if (
            preg_match(
                '/\{#-?\s*bhtml:begin\s*-?#\}(.*?)\{#-?\s*bhtml:end\s*-?#\}/is',
                $content,
                $match
            )
        ) {
            $extendsState = 'missing';

            if (
                preg_match(
                    '/\{#\s*\{%\s*extends\b.*?%\}\s*#\}/is',
                    $match[1]
                )
            ) {
                $extendsState = 'disabled';
            } elseif (
                preg_match(
                    '/\{%\s*extends\b.*?%\}/is',
                    $match[1]
                )
            ) {
                $extendsState = 'enabled';
            }
        }

        $lines = [
            '{#- bhtml:begin -#}',
            '{#- Blendhtml compiler: vendor/bin/bhtml -#}',
            '{#- Compiler-managed. Changes will be overwritten. -#}',
            "{%- set __DIR__ = '{$dir}' -%}",
        ];

        if ($parentTemplate !== '') {
            $lines[] =
                '{#- Comment or uncomment on demand. Survives recompilation. -#}';

            $lines[] = $extendsState === 'enabled'
                ? "{% extends '{$parentTemplate}' %}"
                : "{#{% extends '{$parentTemplate}' %}#}";
        }

        $lines[] = '{#- bhtml:end -#}';

        $compilerBlock = implode(
            "\n",
            $lines
        );

        /**
         * Remove previous compiler block
         * and everything before it.
         */
        $content = preg_replace(
            '/^.*?\{#-?\s*bhtml:begin\s*-?#\}.*?\{#-?\s*bhtml:end\s*-?#\}\s*/is',
            '',
            $content
        );

        $content = $compilerBlock
            . "\n\n"
            . ltrim($content);

        file_put_contents(
            $file,
            $content
        );
    }

    private static function resolveComponentParent(
        string $file,
        string $root
    ): string
    {
        $relative = str_replace(
            rtrim($root, '/') . '/',
            '',
            $file
        );

        $parts = explode(
            '/',
            trim($relative, '/')
        );

        $bhtmlIndex = array_search(
            'bhtml',
            $parts,
            true
        );

        if ($bhtmlIndex === false) {
            return '';
        }

        $componentPath = implode(
            '/',
            array_slice(
                $parts,
                $bhtmlIndex + 1
            )
        );

        $pageSegments = array_slice(
            $parts,
            0,
            $bhtmlIndex
        );

        array_pop($pageSegments);

        while (!empty($pageSegments)) {
            $candidate =
                implode('/', $pageSegments)
                . '/bhtml/'
                . $componentPath;

            $candidateFile =
                $root . '/' . $candidate;

            if (
                is_file($candidateFile) &&
                realpath($candidateFile)
                !== realpath($file)
            ) {
                return $candidate;
            }

            array_pop($pageSegments);
        }

        /**
         * Framework root fallback.
         */
        $candidate =
            'bhtml/' . $componentPath;

        $candidateFile =
            $root . '/' . $candidate;

        if (
            is_file($candidateFile) &&
            realpath($candidateFile)
            !== realpath($file)
        ) {
            return $candidate;
        }

        $candidate =
            Vendor::componentsPath(dirname($root))
            . '/'
            . $componentPath;

        $candidateFile = $candidate;
        
        if (
            is_file($candidateFile) &&
            realpath($candidateFile)
            !== realpath($file)
        ) {
            return $candidate;
        }

        return '';
    }

    private static function resolvePageParent(
        string $file,
        string $root
    ): string
    {
        $relative = str_replace(
            rtrim($root, '/') . '/',
            '',
            $file
        );

        $parts = explode(
            '/',
            trim($relative, '/')
        );

        array_pop($parts);

        while (!empty($parts)) {
            array_pop($parts);

            $candidate =
                implode('/', $parts);

            if ($candidate !== '') {
                $candidate .= '/';
            }

            $candidate .=
                'layout.html.twig';

            $candidateFile =
                $root . '/' . $candidate;

            if (
                is_file($candidateFile) &&
                realpath($candidateFile)
                !== realpath($file)
            ) {
                return $candidate;
            }
        }

        /**
         * First project layout extends the framework layout.
         */
        return '@blendhtml/layout.html.twig';
    }
}
