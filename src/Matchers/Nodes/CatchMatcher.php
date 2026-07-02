<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Catch_;

class CatchMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $types = null,
        private readonly ?Matcher $var   = null,
        private readonly ?Matcher $body  = null,
    ) {}

    protected function nodeClass(): string { return Catch_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->types, $node->types, $keys, 'types')
            && $this->matchField($this->var,   $node->var,   $keys, 'var')
            && $this->matchField($this->body,  $node->stmts, $keys, 'stmts');
    }
}
