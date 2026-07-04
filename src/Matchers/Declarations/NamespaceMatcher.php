<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Namespace_;

class NamespaceMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $name  = null,
        private readonly array|Matcher|null $stmts = null,
    ) {}

    protected function nodeClass(): string { return Namespace_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->name,  $node->name,  $keys, 'name')
            && $this->matchArrayField($this->stmts, $node->stmts, $keys, 'stmts');
    }
}
