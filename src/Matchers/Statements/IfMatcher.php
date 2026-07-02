<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class IfMatcher extends Matcher
{
    private $cond;
    private $then;
    private $elseifs;
    private $else;

    public function __construct($cond = null, $then = null, $elseifs = null, $else = null)
    {
        $this->cond    = $cond;
        $this->then    = $then;
        $this->elseifs = $elseifs;
        $this->else    = $else;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isIf($node)) {
            return false;
        }
        if ($this->cond !== null && !$this->cond->matchValue($node->cond, array_merge($keys, ['cond']))) {
            return false;
        }
        if ($this->then !== null && !$this->then->matchValue($node->stmts, array_merge($keys, ['stmts']))) {
            return false;
        }
        if ($this->elseifs !== null && !$this->elseifs->matchValue($node->elseifs, array_merge($keys, ['elseifs']))) {
            return false;
        }
        if ($this->else !== null && !$this->else->matchValue($node->else, array_merge($keys, ['else']))) {
            return false;
        }
        return true;
    }
}
