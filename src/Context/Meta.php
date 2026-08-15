<?php

namespace Blendhtml\Core\Context;

class Meta
{
    public function __construct(
        public ?string $title = null,
        public ?string $favicon = null,
        public ?string $charset = 'UTF-8',
        public ?string $viewport = 'width=device-width, initial-scale=1',
        public ?string $description = 'Powered by Blendhtml. Visit blendhtml.com for more info.',
    ) {}
}