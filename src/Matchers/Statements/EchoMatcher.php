<?php

namespace Fleet\AstMatcher\Matchers\Statements;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class EchoMatcher extends Matcher
{
    private $exprs;

    public function __construct($exprs = null)
    {
        $this->exprs = $exprs;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isEcho($node)) {
            return false;
        }
        if ($this->exprs !== null && !$this->exprs->matchValue($node->exprs, array_merge($keys, ['exprs']))) {
            return false;
        }
        return true;
    }
}
