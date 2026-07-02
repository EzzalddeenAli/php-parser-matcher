<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Calls;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use Fleet\AstMatcher\Matchers\Nodes\ArgMatcher;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;
use PhpParser\Node\Stmt\Expression;

class StaticCallMatcher extends Matcher
{
    private $class;
    private $name;
    private $args;

    public function __construct($class = null, $name = null, $args = null)
    {
        $this->class = $class;
        $this->name = $name;
        $this->args = $args;
    }

    public function matchValue($node, $keys = []): bool
    {
        if ($node instanceof Expression && NodeTypes::isStaticCall($node->expr)) {
            $node = $node->expr;
        }
        if (!NodeTypes::isNode($node) || !NodeTypes::isStaticCall($node)) {
            return false;
        }
        if ($this->class !== null && !$this->class->matchValue($node->class, array_merge($keys, ['class']))) {
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
