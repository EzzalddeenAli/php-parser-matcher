<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Case_;

class CaseMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $cond = null,
        private readonly ?Matcher $body = null,
    ) {}

    protected function nodeClass(): string { return Case_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->cond, $node->cond,  $keys, 'cond')
            && $this->matchField($this->body, $node->stmts, $keys, 'stmts');
    }
}
