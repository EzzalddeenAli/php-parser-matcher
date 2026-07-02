<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\While_;

class WhileMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $cond = null,
        private readonly ?Matcher $body = null,
    ) {}

    protected function nodeClass(): string { return While_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->cond, $node->cond,  $keys, 'cond')
            && $this->matchField($this->body, $node->stmts, $keys, 'stmts');
    }
}
