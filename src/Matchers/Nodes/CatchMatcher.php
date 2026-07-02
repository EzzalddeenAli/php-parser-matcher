<?php

namespace Fleet\AstMatcher\Matchers\Nodes;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class CatchMatcher extends Matcher
{
    private $types;
    private $var;
    private $body;

    public function __construct($types = null, $var = null, $body = null)
    {
        $this->types = $types;
        $this->var   = $var;
        $this->body  = $body;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isCatch($node)) {
            return false;
        }
        // $node->types is Name[] (the caught exception types)
        if ($this->types !== null && !$this->types->matchValue($node->types, array_merge($keys, ['types']))) {
            return false;
        }
        if ($this->var !== null && !$this->var->matchValue($node->var, array_merge($keys, ['var']))) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        return true;
    }
}
