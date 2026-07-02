<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Expression;

class ExpressionStatementMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $expr = null,
    ) {}

    protected function nodeClass(): string { return Expression::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->expr, $node->expr, $keys, 'expr');
    }
}
