<?php

namespace Fleet\AstMatcher\Matchers\Expressions;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeMatcher;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;
use Fleet\AstMatcher\Matchers\Concerns\UnwrapsExpressionStatement;
use Fleet\AstMatcher\Matchers\Nodes\ArrayItemMatcher;
use PhpParser\Node\Expr\Array_;

class ArrayExpressionMatcher extends NodeMatcher
{
    use UnwrapsExpressionStatement;

    public function __construct(
        private readonly array|Matcher|null $elements = null,
    ) {}

    protected function nodeClass(): string { return Array_::class; }

    protected function matchNode($node, array $keys): bool
    {
        if ($this->elements === null) return true;

        // Single Matcher mode: pass element values directly (unwrapped from ArrayItem).
        // Enables: arrayExpression(anyList(zeroOrMore(chainCall()), oneOrMore(anyNode())))
        if ($this->elements instanceof Matcher) {
            $values = array_map(fn($item) => $item->value, $node->items);
            return $this->elements->matchValue(array_values($values), [...$keys, 'items']);
        }

        // Array mode: each element is auto-wrapped in ArrayItemMatcher if needed.
        // Enables: arrayExpression([arrayItem(value: chainCall(), key: string('k'))])
        $wrapped = array_map(
            static fn($el) => $el instanceof ArrayItemMatcher ? $el : new ArrayItemMatcher($el),
            $this->elements
        );
        return (new TupleOfMatcher(...$wrapped))->matchValue($node->items, [...$keys, 'items']);
    }
}
