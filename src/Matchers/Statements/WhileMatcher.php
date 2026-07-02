<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class WhileMatcher extends Matcher
{
    private $cond;
    private $body;

    public function __construct($cond = null, $body = null)
    {
        $this->cond = $cond;
        $this->body = $body;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isWhile($node)) {
            return false;
        }
        if ($this->cond !== null && !$this->cond->matchValue($node->cond, array_merge($keys, ['cond']))) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        return true;
    }
}
