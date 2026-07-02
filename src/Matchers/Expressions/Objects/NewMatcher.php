<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Objects;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use Fleet\AstMatcher\Matchers\Nodes\ArgMatcher;

class NewMatcher extends Matcher
{
    private $class;
    /**
     * @var mixed|Matcher
     */
    private $args;

    public function __construct($class = null, $args = null)
    {
        $this->class = $class;
        $this->args = $args;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isNew($node)) {
            return false;
        }
        if ($this->class !== null && !$this->class->matchValue($node->class, array_merge($keys, ['class']))) {
            return false;
        }
        if ($this->args !== null) {
            $args = is_array($this->args) ? $this->args : null;
            if ($args !== null) {
                $wrapped = array_map(function ($a) {
                    return ($a instanceof ArgMatcher) ? $a : new ArgMatcher($a, null);
                }, $args);
                $tuple = new \Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher(...$wrapped);
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
