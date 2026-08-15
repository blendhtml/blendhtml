<?php

namespace Blendhtml\Core;

class Page
{
    public function __construct(
        public readonly string $module,
        public readonly string $name,
        public readonly string $directory,
        public readonly string $rootDirectory
    ) {
    }
}
