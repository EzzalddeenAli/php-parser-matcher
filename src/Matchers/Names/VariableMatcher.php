<?php

namespace Fleet\AstMatcher\Matchers\Names;

use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Expr\Variable;

class VariableMatcher extends NodeMatcher
{
    public function __construct(
        private readonly mixed $name = null,
    ) {}

    protected function nodeClass(): string { return Variable::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->name === null) return true;
        if (is_string($this->name)) return $this->name === $node->name;
        return $this->name->matchValue($node->name, [...$keys, 'name']);
    }
}
