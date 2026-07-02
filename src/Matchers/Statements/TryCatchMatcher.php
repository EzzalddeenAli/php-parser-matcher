<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\TryCatch;

class TryCatchMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $body    = null,
        private readonly ?Matcher $catches = null,
        private readonly ?Matcher $finally = null,
    ) {}

    protected function nodeClass(): string { return TryCatch::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->body,    $node->stmts,   $keys, 'stmts')
            && $this->matchField($this->catches, $node->catches,  $keys, 'catches')
            && $this->matchField($this->finally, $node->finally,  $keys, 'finally');
    }
}
