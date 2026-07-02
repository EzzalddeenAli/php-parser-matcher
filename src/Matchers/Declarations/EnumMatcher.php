<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use PhpParser\Node\Stmt\Enum_;

class EnumMatcher extends NodeMatcher
{
    public function __construct(
        private readonly ?Matcher $name       = null,
        private readonly ?Matcher $scalarType = null,
        private readonly mixed    $body       = null,
    ) {}

    protected function nodeClass(): string { return Enum_::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->name,       $node->name,       $keys, 'name')
            && $this->matchField($this->scalarType, $node->scalarType, $keys, 'scalarType')
            && $this->matchArrayField($this->body,  $node->stmts,      $keys, 'stmts');
    }
}
