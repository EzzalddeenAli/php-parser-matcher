<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Assignment;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class AssignMatcher extends Matcher
{
    private $var;
    private $expr;

    public function __construct($var = null, $expr = null)
    {
        $this->var = $var;
        $this->expr = $expr;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isAssign($node)) {
            return false;
        }
        if ($this->var !== null && !$this->var->matchValue($node->var, array_merge($keys, ['var']))) {
            return false;
        }
        if ($this->expr !== null && !$this->expr->matchValue($node->expr, array_merge($keys, ['expr']))) {
            return false;
        }
        return true;
    }
}
