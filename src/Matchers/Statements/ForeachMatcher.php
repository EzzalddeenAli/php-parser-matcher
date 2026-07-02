<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Foreach_;

class ForeachMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $expr     = null,
        private readonly ?Matcher $valueVar = null,
        private readonly ?Matcher $keyVar   = null,
        private readonly ?Matcher $body     = null,
    ) {}

    protected function nodeClass(): string { return Foreach_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->expr,     $node->expr,     $keys, 'expr')
            && $this->matchField($this->valueVar, $node->valueVar, $keys, 'valueVar')
            && $this->matchField($this->keyVar,   $node->keyVar,   $keys, 'keyVar')
            && $this->matchField($this->body,     $node->stmts,    $keys, 'stmts');
    }
}
