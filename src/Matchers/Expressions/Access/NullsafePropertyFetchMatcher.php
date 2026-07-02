<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Access;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\NullsafePropertyFetch;

class NullsafePropertyFetchMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly ?Matcher $object   = null,
        private readonly ?Matcher $property = null,
    ) {}

    protected function nodeClass(): string { return NullsafePropertyFetch::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->object,   $node->var,  $keys, 'var')
            && $this->matchField($this->property, $node->name, $keys, 'name');
    }
}
