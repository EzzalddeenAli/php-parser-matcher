<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Interface_;

class InterfaceMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $name    = null,
        private readonly ?Matcher $extends = null,
        private readonly mixed    $body    = null,
    ) {}

    protected function nodeClass(): string { return Interface_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->name,    $node->name,    $keys, 'name')
            && $this->matchField($this->extends, $node->extends, $keys, 'extends')
            && $this->matchArrayField($this->body, $node->stmts, $keys, 'stmts');
    }
}
