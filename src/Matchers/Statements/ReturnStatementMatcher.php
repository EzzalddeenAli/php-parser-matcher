<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Return_;

class ReturnStatementMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $argument = null,
    ) {}

    protected function nodeClass(): string { return Return_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->argument, $node->expr, $keys, 'expr');
    }
}
