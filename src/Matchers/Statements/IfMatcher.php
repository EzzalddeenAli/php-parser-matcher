<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\If_;

class IfMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $cond    = null,
        private readonly ?Matcher $then    = null,
        private readonly ?Matcher $elseifs = null,
        private readonly ?Matcher $else    = null,
    ) {}

    protected function nodeClass(): string { return If_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->cond,    $node->cond,    $keys, 'cond')
            && $this->matchField($this->then,    $node->stmts,   $keys, 'stmts')
            && $this->matchField($this->elseifs, $node->elseifs, $keys, 'elseifs')
            && $this->matchField($this->else,    $node->else,    $keys, 'else');
    }
}
