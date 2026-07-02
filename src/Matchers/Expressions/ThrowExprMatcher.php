<?php

namespace Fleet\AstMatcher\Matchers\Expressions;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\Throw_;

class ThrowExprMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly ?Matcher $expr = null,
    ) {}

    protected function nodeClass(): string { return Throw_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->expr, $node->expr, $keys, 'expr');
    }
}
