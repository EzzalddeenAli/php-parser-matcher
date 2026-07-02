<?php

namespace Fleet\AstMatcher\Matchers\Expressions;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ThrowExprMatcher extends Matcher
{
    private $expr;

    public function __construct($expr = null)
    {
        $this->expr = $expr;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isThrow($node)) {
            return false;
        }
        if ($this->expr !== null && !$this->expr->matchValue($node->expr, array_merge($keys, ['expr']))) {
            return false;
        }
        return true;
    }
}
