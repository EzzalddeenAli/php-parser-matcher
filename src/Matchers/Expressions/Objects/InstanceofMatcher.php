<?php

namespace Fleet\AstMatcher\Matchers\Expressions\Objects;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class InstanceofMatcher extends Matcher
{
    private $expr;
    private $class;

    public function __construct($expr = null, $class = null)
    {
        $this->expr = $expr;
        $this->class = $class;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isInstanceof($node)) {
            return false;
        }
        if ($this->expr !== null && !$this->expr->matchValue($node->expr, array_merge($keys, ['expr']))) {
            return false;
        }
        if ($this->class !== null && !$this->class->matchValue($node->class, array_merge($keys, ['class']))) {
            return false;
        }
        return true;
    }
}
