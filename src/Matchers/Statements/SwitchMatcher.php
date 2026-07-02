<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Switch_;

class SwitchMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $cond  = null,
        private readonly ?Matcher $cases = null,
    ) {}

    protected function nodeClass(): string { return Switch_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->cond,  $node->cond,  $keys, 'cond')
            && $this->matchField($this->cases, $node->cases, $keys, 'cases');
    }
}
