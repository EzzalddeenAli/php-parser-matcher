<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Access;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use PhpParser\Node\Expr\ClassConstFetch;

class ClassConstFetchMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly ?Matcher $class    = null,
        private readonly ?Matcher $property = null,
    ) {}

    protected function nodeClass(): string { return ClassConstFetch::class; }

    protected function matchNode($node, array $keys): bool
    {
        return $this->matchField($this->class,    $node->class, $keys, 'class')
            && $this->matchField($this->property, $node->name,  $keys, 'name');
    }
}
