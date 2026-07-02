<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Param;

class ParamMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $name = null,
        private readonly ?Matcher $type = null,
    ) {}

    protected function nodeClass(): string { return Param::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->type, $node->type, $keys, 'type')
            && $this->matchField($this->name, $node->var,  $keys, 'var');
    }
}
