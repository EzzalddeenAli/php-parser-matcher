<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class BreakMatcher extends Matcher
{
    private $num;

    public function __construct($num = null)
    {
        $this->num = $num;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isBreak($node)) {
            return false;
        }
        if ($this->num !== null && !$this->num->matchValue($node->num, array_merge($keys, ['num']))) {
            return false;
        }
        return true;
    }
}
