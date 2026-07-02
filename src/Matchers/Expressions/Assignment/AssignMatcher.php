<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Assignment;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\Assign;

class AssignMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly ?Matcher $var  = null,
        private readonly ?Matcher $expr = null,
    ) {}

    protected function nodeClass(): string { return Assign::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->var,  $node->var,  $keys, 'var')
            && $this->matchField($this->expr, $node->expr, $keys, 'expr');
    }
}
