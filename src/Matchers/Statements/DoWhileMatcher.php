<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Do_;

class DoWhileMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $body = null,
        private readonly ?Matcher $cond = null,
    ) {}

    protected function nodeClass(): string { return Do_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->body, $node->stmts, $keys, 'stmts')
            && $this->matchField($this->cond, $node->cond,  $keys, 'cond');
    }
}
