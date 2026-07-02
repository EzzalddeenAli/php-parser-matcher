<?php

namespace Fleet\AstMatcher\Matchers\Scalars;

use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;

class NumberLiteralMatcher extends NodeMatcher
{
    public function __construct(
        private readonly mixed $value = null,
    ) {}

    protected function matchesNodeType(mixed $node): bool
    {
        return $node instanceof LNumber || $node instanceof DNumber;
    }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->value === null) return true;
        // loose comparison intentional: 42 matches both LNumber(42) and DNumber(42.0)
        return $this->value == $node->value;
    }
}
