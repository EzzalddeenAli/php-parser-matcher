<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;

class AttributeMatcher extends Matcher
{
    private $name;
    private $args;

    public function __construct($name = null, $args = null)
    {
        $this->name = $name;
        $this->args = $args;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isAttribute($node)) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->args !== null) {
            if (is_array($this->args)) {
                $wrapped = array_map(function ($a) {
                    return ($a instanceof ArgMatcher) ? $a : new ArgMatcher($a, null);
                }, $this->args);
                $tuple = new TupleOfMatcher(...$wrapped);
                if (!$tuple->matchValue($node->args, array_merge($keys, ['args']))) {
                    return false;
                }
            } elseif (!$this->args->matchValue($node->args, array_merge($keys, ['args']))) {
                return false;
            }
        }
        return true;
    }
}
