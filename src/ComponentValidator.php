<?php

namespace Blendhtml\Core;

class ComponentValidator
{
    private array $errors = [];
    private array $warnings = [];

    public static function scan(string $root): array
    {
        $validator = new self();
        $validator->walk($root);

        // merge everything into single "issue list"
        $issues = array_merge(
            $validator->errors,
            $validator->warnings
        );

        // return EMPTY ARRAY if no issues
        return $issues;
    }

    private function walk(string $root): void
    {
        if (!is_dir($root)) {
            $this->errors[] = [
                'type' => 'root_missing',
                'path' => $root,
                'message' => 'Project root does not exist',
            ];
            return;
        }

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

            $path = $file->getPathname();

            // ONLY scan components inside ANY bhtml folder
            if (strpos($path, '/bhtml/') === false) {
                continue;
            }

            if (str_ends_with($path, '.php')) {
                $this->validatePhpFile($path);
            }

            if (str_ends_with($path, '.twig')) {
                $this->validateTwigFile($path);
            }
        }
    }

    private function validatePhpFile(string $file): void
    {
        $content = file_get_contents($file);

        if ($content === false) {
            $this->errors[] = [
                'type' => 'read_error',
                'path' => $file,
                'message' => 'Cannot read file',
            ];
            return;
        }

        if (!str_contains($content, 'return')) {
            $this->errors[] = [
                'type' => 'missing_return',
                'path' => $file,
                'message' => 'Component must return array',
            ];
        }

        if (preg_match('/return\s+[0-9]+/', $content)) {
            $this->errors[] = [
                'type' => 'invalid_scalar_return',
                'path' => $file,
                'message' => 'Scalar return detected (must be array)',
            ];
        }
    }

    private function validateTwigFile(string $file): void
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return;
        }

        if (str_contains($content, '<?php')) {
            $this->errors[] = [
                'type' => 'php_in_twig',
                'path' => $file,
                'message' => 'PHP inside Twig is not allowed',
            ];
        }

        if (str_contains($content, 'Context::page(')) {
            $this->errors[] = [
                'type' => 'context_violation',
                'path' => $file,
                'message' => 'Context::page() forbidden in components',
            ];
        }
    }
}