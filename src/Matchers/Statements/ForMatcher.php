<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\For_;

class ForMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $init = null,
        private readonly ?Matcher $cond = null,
        private readonly ?Matcher $loop = null,
        private readonly ?Matcher $body = null,
    ) {}

    protected function nodeClass(): string { return For_::class; }

    // $node->init, ->cond, ->loop are Expr[] arrays; $node->stmts is Stmt[]
    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->init, $node->init,  $keys, 'init')
            && $this->matchField($this->cond, $node->cond,  $keys, 'cond')
            && $this->matchField($this->loop, $node->loop,  $keys, 'loop')
            && $this->matchField($this->body, $node->stmts, $keys, 'stmts');
    }
}
