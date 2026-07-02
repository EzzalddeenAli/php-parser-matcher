<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class SwitchMatcher extends Matcher
{
    private $cond;
    private $cases;

    public function __construct($cond = null, $cases = null)
    {
        $this->cond  = $cond;
        $this->cases = $cases;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isSwitch($node)) {
            return false;
        }
        if ($this->cond !== null && !$this->cond->matchValue($node->cond, array_merge($keys, ['cond']))) {
            return false;
        }
        if ($this->cases !== null && !$this->cases->matchValue($node->cases, array_merge($keys, ['cases']))) {
            return false;
        }
        return true;
    }
}
