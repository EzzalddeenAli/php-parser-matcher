<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Access;

use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\ConstFetch;

class ConstFetchMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly mixed $name = null,
    ) {}

    protected function nodeClass(): string { return ConstFetch::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->name === null) return true;
        if (is_string($this->name)) {
            return strtolower($node->name->toString()) === strtolower($this->name);
        }
        return $this->name->matchValue($node->name, [...$keys, 'name']);
    }
}
