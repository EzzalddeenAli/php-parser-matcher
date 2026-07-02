<?php

namespace Fleet\AstMatcher\Matchers\Scalars;

use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Scalar\String_;

class StringLiteralMatcher extends NodeMatcher
{
    public function __construct(
        private readonly mixed $value = null,
    ) {}

    protected function nodeClass(): string { return String_::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->value === null) return true;
        if (is_string($this->value)) return $this->value === $node->value;
        return $this->value->matchValue($node->value, [...$keys, 'value']);
    }
}
