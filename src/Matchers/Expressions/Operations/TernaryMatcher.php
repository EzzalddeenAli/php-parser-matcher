<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Operations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class TernaryMatcher extends Matcher
{
    private $cond;
    private $if;
    private $else;

    public function __construct($cond = null, $if = null, $else = null)
    {
        $this->cond = $cond;
        $this->if = $if;
        $this->else = $else;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isTernary($node)) {
            return false;
        }
        if ($this->cond !== null && !$this->cond->matchValue($node->cond, array_merge($keys, ['cond']))) {
            return false;
        }
        if ($this->if !== null && !$this->if->matchValue($node->if, array_merge($keys, ['if']))) {
            return false;
        }
        if ($this->else !== null && !$this->else->matchValue($node->else, array_merge($keys, ['else']))) {
            return false;
        }
        return true;
    }
}
