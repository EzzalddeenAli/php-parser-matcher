<?php

namespace Fleet\AstMatcher\Testing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Example
{
    public function __construct(
        public string $description = '',
        public bool $skip = false,
    ) {}
}
