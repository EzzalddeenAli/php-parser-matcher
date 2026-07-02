<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\ArrayItem;

class ArrayItemMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $value = null,
        private readonly ?Matcher $key   = null,
    ) {}

    protected function nodeClass(): string { return ArrayItem::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->value, $node->value, $keys, 'value')
            && $this->matchField($this->key,   $node->key,   $keys, 'key');
    }
}
