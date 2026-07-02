<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\MatchesArgs;
use PhpParser\Node\Attribute;

class AttributeMatcher extends NodeMatcher
{
    use MatchesArgs;

    public function __construct(
        private readonly ?Matcher $name = null,
        private readonly mixed    $args = null,
    ) {}

    protected function nodeClass(): string { return Attribute::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->name, $node->name, $keys, 'name')
            && $this->matchArgs($this->args, $node->args, $keys);
    }
}
