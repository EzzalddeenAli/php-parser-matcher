<?php

namespace Fleet\AstMatcher\Matchers\Expressions;

use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use Fleet\AstMatcher\Matchers\Nodes\ArrayItemMatcher;
use PhpParser\Node\Expr\Array_;

class ArrayExpressionMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly ?array $elements = null,
    ) {}

    protected function nodeClass(): string { return Array_::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->elements === null) return true;

        $wrapped = array_map(
            static fn($el) => $el instanceof ArrayItemMatcher ? $el : new ArrayItemMatcher($el),
            $this->elements
        );
        return (new TupleOfMatcher(...$wrapped))->matchValue($node->items, [...$keys, 'items']);
    }
}
