<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Calls;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use Fleet\AstMatcher\Matchers\Nodes\ArgMatcher;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;

class MethodCallMatcher extends Matcher
{
    private $var;
    private $name;
    private $args;

    public function __construct($var = null, $name = null, $args = null)
    {
        $this->var = $var;
        $this->name = $name;
        $this->args = $args;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isMethodCall($node)) {
            return false;
        }
        if ($this->var !== null && !$this->var->matchValue($node->var, array_merge($keys, ['var']))) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->name, array_merge($keys, ['name']))) {
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
