<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Access;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\ArrayDimFetch;

class ArrayDimFetchMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly ?Matcher $var = null,
        private readonly ?Matcher $dim = null,
    ) {}

    protected function nodeClass(): string { return ArrayDimFetch::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->var, $node->var, $keys, 'var')
            && $this->matchField($this->dim, $node->dim, $keys, 'dim');
    }
}
