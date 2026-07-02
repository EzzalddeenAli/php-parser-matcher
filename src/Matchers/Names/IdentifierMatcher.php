<?php

namespace Fleet\AstMatcher\Matchers\Names;

use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class IdentifierMatcher extends NodeMatcher
{
    public function __construct(
        private readonly mixed $name = null,
    ) {}

    protected function matchesNodeType(mixed $node): bool
    {
        return $node instanceof Identifier || $node instanceof Name;
    }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->name === null) return true;
        if (is_string($this->name)) {
            return $this->name === $node->name || $this->name === $node->toString();
        }
        return $this->name->matchValue($node->name, [...$keys, 'name']);
    }
}
