<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Operations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\Ternary;

class TernaryMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly ?Matcher $cond = null,
        private readonly ?Matcher $if   = null,
        private readonly ?Matcher $else = null,
    ) {}

    protected function nodeClass(): string { return Ternary::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->cond, $node->cond, $keys, 'cond')
            && $this->matchField($this->if,   $node->if,   $keys, 'if')
            && $this->matchField($this->else, $node->else, $keys, 'else');
    }
}
