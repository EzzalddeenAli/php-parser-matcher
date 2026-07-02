<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Echo_;

class EchoMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $exprs = null,
    ) {}

    protected function nodeClass(): string { return Echo_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->exprs, $node->exprs, $keys, 'exprs');
    }
}
