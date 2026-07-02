<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Access;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class ArrayDimFetchMatcher extends Matcher
{
    private $var;
    private $dim;

    public function __construct($var = null, $dim = null)
    {
        $this->var = $var;
        $this->dim = $dim;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isArrayDimFetch($node)) {
            return false;
        }
        if ($this->var !== null && !$this->var->matchValue($node->var, array_merge($keys, ['var']))) {
            return false;
        }
        if ($this->dim !== null && !$this->dim->matchValue($node->dim, array_merge($keys, ['dim']))) {
            return false;
        }
        return true;
    }
}
