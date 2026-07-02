<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Break_;

class BreakMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $num = null,
    ) {}

    protected function nodeClass(): string { return Break_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->num, $node->num, $keys, 'num');
    }
}
