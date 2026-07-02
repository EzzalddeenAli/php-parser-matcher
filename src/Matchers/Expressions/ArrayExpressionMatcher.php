<?php

namespace Fleet\AstMatcher\Matchers\Expressions;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use Fleet\AstMatcher\Matchers\Nodes\ArrayItemMatcher;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;

class ArrayExpressionMatcher extends Matcher
{
    private $elements;

    public function __construct($elements = null)
    {
        $this->elements = $elements;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isArrayExpression($node)) {
            return false;
        }
        if ($this->elements !== null) {
            $wrapped = array_map(function ($el) {
                return ($el instanceof ArrayItemMatcher) ? $el : new ArrayItemMatcher($el);
            }, $this->elements);
            $tuple = new TupleOfMatcher(...$wrapped);
            if (!$tuple->matchValue($node->items, array_merge($keys, ['items']))) {
                return false;
            }
        }
        return true;
    }
}
