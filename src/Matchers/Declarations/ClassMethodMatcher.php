<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;
use Fleet\AstMatcher\Matchers\Collections\TupleOfMatcher;

class ClassMethodMatcher extends Matcher
{
    private $name;
    private $params;
    private $body;
    private $static;

    public function __construct($name = null, $params = null, $body = null, $static = null)
    {
        $this->name = $name;
        $this->params = $params;
        $this->body = $body;
        $this->static = $static;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isClassMethod($node)) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->params !== null) {
            if (is_array($this->params)) {
                $tuple = new TupleOfMatcher(...$this->params);
                if (!$tuple->matchValue($node->params, array_merge($keys, ['params']))) {
                    return false;
                }
            } elseif (!$this->params->matchValue($node->params, array_merge($keys, ['params']))) {
                return false;
            }
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        if ($this->static !== null && is_bool($this->static) && $this->static !== $node->isStatic()) {
            return false;
        }
        return true;
    }
}
