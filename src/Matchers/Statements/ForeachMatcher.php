<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ForeachMatcher extends Matcher
{
    private $expr;
    private $valueVar;
    private $keyVar;
    private $body;

    public function __construct($expr = null, $valueVar = null, $keyVar = null, $body = null)
    {
        $this->expr     = $expr;
        $this->valueVar = $valueVar;
        $this->keyVar   = $keyVar;
        $this->body     = $body;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isForeach($node)) {
            return false;
        }
        if ($this->expr !== null && !$this->expr->matchValue($node->expr, array_merge($keys, ['expr']))) {
            return false;
        }
        if ($this->valueVar !== null && !$this->valueVar->matchValue($node->valueVar, array_merge($keys, ['valueVar']))) {
            return false;
        }
        if ($this->keyVar !== null && !$this->keyVar->matchValue($node->keyVar, array_merge($keys, ['keyVar']))) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        return true;
    }
}
