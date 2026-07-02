<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Calls;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use Fleet\AstMatcher\Matchers\Nodes\ArgMatcher;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;

class CallExpressionMatcher extends Matcher
{
    private $callee;
    private $args;

    public function __construct($callee = null, $args = null)
    {
        $this->callee = $callee;
        $this->args = $args;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isCallExpression($node)) {
            return false;
        }
        if ($this->callee !== null && !$this->callee->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->args !== null) {
            if (is_array($this->args)) {
                $args = array_map(fn($a) => $a instanceof ArgMatcher ? $a : new ArgMatcher($a, null), $this->args);
                if (!(new TupleOfMatcher(...$args))->matchValue($node->args, array_merge($keys, ['args']))) {
                    return false;
                }
            } elseif (!$this->args->matchValue($node->args, array_merge($keys, ['args']))) {
                return false;
            }
        }
        return true;
    }
}
