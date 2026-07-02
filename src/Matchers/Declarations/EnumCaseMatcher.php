<?php

namespace Fleet\AstMatcher\Matchers\Declarations;

use Fleet\AstMatcher\Core\Matcher;
use Fleet\AstMatcher\Core\NodeTypes;

class EnumCaseMatcher extends Matcher
{
    private $name;
    private $expr;

    public function __construct($name = null, $expr = null)
    {
        $this->name = $name;
        $this->expr = $expr;
    }

    public function matchValue($node, $keys = []): bool
    {
        if (!NodeTypes::isNode($node) || !NodeTypes::isEnumCase($node)) {
            return false;
        }
        if ($this->name !== null && !$this->name->matchValue($node->name, array_merge($keys, ['name']))) {
            return false;
        }
        if ($this->expr !== null && !$this->expr->matchValue($node->expr, array_merge($keys, ['expr']))) {
            return false;
        }
        return true;
    }
}
