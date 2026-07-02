<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\TraitUse;

class TraitUseMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $traits = null,
    ) {}

    protected function nodeClass(): string { return TraitUse::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->traits, $node->traits, $keys, 'traits');
    }
}
