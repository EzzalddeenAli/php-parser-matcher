<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Arg;

class ArgMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $value = null,
        private readonly ?Matcher $name  = null,
    ) {}

    protected function nodeClass(): string { return Arg::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->value, $node->value, $keys, 'value')
            && $this->matchField($this->name,  $node->name,  $keys, 'name');
    }
}
