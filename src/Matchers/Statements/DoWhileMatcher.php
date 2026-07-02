<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class DoWhileMatcher extends Matcher
{
    private $body;
    private $cond;

    public function __construct($body = null, $cond = null)
    {
        $this->body = $body;
        $this->cond = $cond;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isDoWhile($node)) {
            return false;
        }
        if ($this->body !== null && !$this->body->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        if ($this->cond !== null && !$this->cond->matchValue($node->cond, array_merge($keys, ['cond']))) {
            return false;
        }
        return true;
    }
}
