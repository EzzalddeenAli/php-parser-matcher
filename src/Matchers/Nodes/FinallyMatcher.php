<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Finally_;

class FinallyMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $body = null,
    ) {}

    protected function nodeClass(): string { return Finally_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->body, $node->stmts, $keys, 'stmts');
    }
}
