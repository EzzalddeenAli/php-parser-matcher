<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\EnumCase;

class EnumCaseMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $name = null,
        private readonly ?Matcher $expr = null,
    ) {}

    protected function nodeClass(): string { return EnumCase::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->name, $node->name, $keys, 'name')
            && $this->matchField($this->expr, $node->expr, $keys, 'expr');
    }
}
